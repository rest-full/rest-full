<?php

namespace Restfull\ORM;

use Restfull\Core\Instances;
use Restfull\Error\Exceptions;

/**
 * Class Generator
 * @package Restfull\ORM
 */
class Generator
{

    /**
     * @var array
     */
    public $bindValue = [];

    /**
     * @var array
     */
    public $query = [];

    /**
     * @var int
     */
    private $count = 0;

    /**
     * @var InstanceClass
     */
    private $instance;

    /**
     * @var array
     */
    private $comparation = [
            ' between ' => ' <==> ',
            ' not between ' => ' !<==> ',
            ' like ' => ' % ',
            ' not like ' => ' !% ',
            ' = ' => ' & ',
            ' <> ' => ' +- ',
            ' >= ' => ' +& ',
            ' > ' => ' + ',
            ' <= ' => ' -& ',
            ' < ' => ' - ',
            ' in ' => ' () ',
            ' not in ' => ' !() ',
            ' is ' => ' )( ',
            ' is not ' => ' !)( '
    ];

    /**
     * Generator constructor.
     * @param array $query
     */
    public function __construct(array $query = [])
    {
        if (count($query) > 0) {
            $this->query = $query;
        }
        $this->instance = new Instances();
        return $this;
    }

    /**
     * @param array $data
     * @param string $caracter
     * @return string
     */
    public function fields(array $data = [], string $caracter = ''): string
    {
        if (!empty($caracter)) {
            $this->count = 0;
            $newData = '';
            foreach ($data as $value) {
                if (stripos($value, ' as ') !== false) {
                    $value = substr($value, stripos($value, ' as ') + 4);
                }
                if (stripos($value, '.') !== false) {
                    $caracters = substr($value, 0, stripos($value, '.') + 1);
                    $newData .= str_replace($caracters, $caracter . '.', $value);
                } else {
                    $newData .= $caracter . '.' . $value;
                }
                $this->count++;
                if ($this->count == count($data)) {
                    break;
                }
                $newData .= ", ";
            }
            return $newData;
        }
        if (!isset($data[1][0])) {
            $newData = '';
            foreach ($data[1] as $key => $value) {
                if (is_numeric($key) && is_int($key)) {
                    $newData .= $value;
                } else {
                    $newData .= $key . " = :c" . $this->count;
                    $this->bind_values[":c" . $this->count] = $value;
                    $this->count++;
                }
                if ($this->count == count($data[1])) {
                    break;
                }
                $newData .= ", ";
            }
            return $newData;
        }
        if (count($data[1]) > 1) {
            return implode(", ", $data[1]);
        }
        return $data[1][0];
    }

    /**
     * @param array $data
     * @return string
     * @throws Exceptions
     */
    public function table(array $data): string
    {
        if (stripos($data['table'], ".") === false) {
            foreach ($data as $key => $value) {
                $data[$key] = strtolower($value);
            }
            $ascii = [
                    'a' => [192, 193, 194, 195, 196, 197, 224, 225, 226, 227, 228, 229],
                    'e' => [200, 201, 202, 203, 232, 233, 234, 235],
                    'i' => [204, 205, 206, 207, 236, 237, 238, 239],
                    'o' => [210, 211, 212, 213, 214, 242, 243, 244, 245, 246, 240, 248],
                    'u' => [217, 218, 219, 220, 249, 250, 251, 252],
                    'c' => [199, 231],
                    'n' => [209, 241],
                    '_' => [32]
            ];

            foreach ($ascii as $key => $item) {
                $acentos = [];
                foreach ($item as $codigo) {
                    $acentos[] = utf8_encode(chr($codigo));
                }
                $data = str_replace(array_values($acentos), $key, $data);
            }
        }
        if (!empty($data['alias'])) {
            return str_replace(
                    DS,
                    ' as ',
                    $this->instance->namespaceClass('%s' . DS . '%s', [$data['table'], $data['alias']])
            );
        }
        return $data['table'];
    }

    /**
     * @param array $data
     * @return string
     * @throws Exceptions
     */
    public function join(array $data): string
    {
        $params = ' ';
        $data[0] = str_replace([' on ', ' join '], DS, $data[0]);
        for ($a = 0; $a < count($data[1]); $a++) {
            if (isset($data[1][$a]['alias'])) {
                $data[1][$a]['table'] = str_replace(
                        DS,
                        ' as ',
                        $this->instance->namespaceClass(
                                '%s' . DS . '%s',
                                [$data[1][$a]['table'], $data[1][$a]['alias']]
                        )
                );
            }
            unset($data[1][$a]['alias']);
            $keys = ['type', 'table', 'conditions'];
            for ($b = 0; $b < count($keys); $b++) {
                $params .= $data[1][$a][$keys[$b]];
                if ($b == 0) {
                    $params .= ' join ';
                } elseif ($b == 1) {
                    $params .= ' on ';
                }
            }
            if ($a < (count($data[1]) - 1)) {
                $params .= ' ';
            }
        }
        return $params;
    }

    /**
     * @param array $data
     * @param int $a
     * @return string
     */
    public function conditions(array $data, int $a): string
    {
        $count = 0;
        foreach ($data[1] as $key => $value) {
            if (is_string($key)) {
                if (in_array($key, ['and', 'or']) !== false) {
                    foreach ($value as $keys => $values) {
                        if (is_array($values)) {
                            $data[1][$key][$keys] = $values[$a];
                        }
                    }
                } else {
                    if (is_array($value)) {
                        $data[1][$key] = $value[$a];
                    }
                }
            }
        }
        if (!isset($this->bindValue)) {
            $this->bindValue = [];
        }
        if (count($this->query) != 0) {
            foreach ($this->query as $value) {
                $count += substr_count($value, ":c");
            }
        }
        if ($data[0] == 'insert') {
            foreach ($data[1] as $key => $value) {
                $bindValue = ":c" . $this->count;
                $newData = isset($newData) ? $newData . ", " . $bindValue : $bindValue;
                $this->bindValue[$bindValue] = $value;
                $this->count++;
            }
            return $newData;
        }
        if (array_key_exists('and', $data[1]) || array_key_exists('or', $data[1])) {
            if (count(array_keys($data[1])) == 2) {
                $operator_logical = ['and', 'or'];
            } else {
                $operator_logical = [array_keys($data[1])[0]];
            }
        } else {
            $operator_logical = ['and'];
        }
        $key_comparation = array_keys($this->comparation);
        for ($b = 0; $b < count($operator_logical); $b++) {
            $datas = (in_array("array", array_map('gettype', $data[1]))) ? $data[1][$operator_logical[$b]] : $data[1];
            foreach ($datas as $key => $value) {
                for ($c = 0; $c < count($key_comparation); $c++) {
                    if (stripos(
                                    (is_numeric($key) && is_int($key) ? $value : $key),
                                    $this->comparation[$key_comparation[$c]]
                            ) !== false) {
                        $newComparation = $key_comparation[$c];
                        $oldComparation = $this->comparation[$key_comparation[$c]];
                    }
                }
                if (is_numeric($key) && is_int($key)) {
                    $data[$operator_logical[$b]][] = str_replace($oldComparation, $newComparation, $value);
                } else {
                    $cind_values = ":c" . $this->count;
                    list($column, $operator) = explode(" ", $key);
                    $this->bindValue[$cind_values] = $value;
                    $data[$operator_logical[$b]][] = str_replace($oldComparation, $newComparation, $key) . $cind_values;
                    $this->count++;
                }
            }
        }
        unset($data[1]);
        $operator_logical = ['and', 'or'];
        for ($b = 0; $b < count($operator_logical); $b++) {
            if (array_key_exists($operator_logical[$b], $data)) {
                if (count($data[$operator_logical[$b]]) > 1) {
                    $newData[$b] = implode(
                            ' ' . $operator_logical[$b] . ' ',
                            $data[$operator_logical[$b]]
                    );
                } else {
                    $newData[$b] = $data[$operator_logical[$b]][0];
                }
                $resp[] = 'true';
            } else {
                $resp[] = 'false';
            }
        }
        if (!isset(array_count_values($resp)['false'])) {
            return $newData[0] . ' ' . $operator_logical[0] . " (" . $newData[1] . ")";
        } else {
            if (isset($newData)) {
                if (isset($newData[1])) {
                    return $newData[1];
                }
                return $newData[0];
            }
            return '';
        }
    }

    /**
     * @param array $data
     * @return string
     */
    public function order(array $data): string
    {
        foreach ($data['fields'] as $key => $value) {
            if (stripos($value, "&&")) {
                $data['fields'][$key] = str_replace("&&", "DESC", $value);
            } else {
                if (stripos($value, "&")) {
                    $data['fields'][$key] = str_replace("&", "ASC", $value);
                }
            }
        }

        if (is_array($data['fields'])) {
            return implode(", ", $data['fields']);
        }

        return $data['fields'];
    }

    /**
     * @param array $data
     * @return string
     */
    public function limit(array $data): string
    {
        if (count($data) == '1') {
            $data[1] = $data[0];
            $data[0] = '0';
        }
        return implode(", ", $data);
    }

    /**
     * @param array $data
     * @return string
     */
    public function group(array $data): string
    {
        if (count($data) > "1") {
            return implode(", ", $data);
        }
        return $data[0];
    }

    /**
     * @param array $data
     * @return string
     */
    public function having(array $data): string
    {
        foreach ($data as $key => $value) {
            $newdata[] = $key . $value;
        }
        if (count($newdata) > 1) {
            return implode(", ", $newdata);
        }
        return $newdata[0];
    }

    /**
     * @param $query
     * @return Generator
     */
    public function setQuery($query): Generator
    {
        $this->query = $query;
        return $this;
    }
}