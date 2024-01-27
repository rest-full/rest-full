<?php

namespace Restfull\ORM;

use Restfull\Container\Instances;

/**
 *
 */
class Assembly
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
     * @param array $query
     */
    public function __construct(Instances $instance, string $query = '')
    {
        $this->query[] = $query;
        $this->instance = $instance;
        return $this;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public function fields(array $data = []): string
    {
        if (!isset($data[1][0])) {
            $newData = '';
            foreach ($data[1] as $key => $value) {
                if (is_numeric($key) && is_int($key)) {
                    $newData .= $value;
                } else {
                    $newData .= $key . " = :c" . $this->count;
                    $this->bindValue[":c" . $this->count] = $value;
                }
                $this->count++;
                if ($this->count === count($data[1])) {
                    break;
                }
                $newData .= ", ";
            }
            return $newData;
        }
        $count = count($data[1]);
        if ($count > 1) {
            $datas = '';
            for ($a = 0; $a < $count; $a++) {
                $datas .= $data[1][$a];
                if ($a < ($count - 1)) {
                    $datas .= ', ';
                }
            }
            return $datas;
        }
        return $data[1][0];
    }

    /**
     * @param array $data
     *
     * @return string
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
            return "{$data['table']} as {$data['alias']}";
        }
        return $data['table'];
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public function join(array $datas): array
    {
        $count = count($datas);
        $newDatas = [];
        for ($a = 0; $a < $count; $a++) {
            if (isset($datas[$a]['alias'])) {
                $datas[$a]['table'] = "{$datas[$a]['table']} as {$datas[$a]['alias']}";
            }
            unset($datas[$a]['alias']);
            foreach (['type', 'table', 'conditions'] as $key) {
                $newDatas[] = $datas[$a][$key];
            }
        }
        return $newDatas;
    }

    /**
     * @param array $data
     * @param int $a
     *
     * @return mixed
     */
    public function conditions(array $data, int $a)
    {
        if ($data[0] === 'insert') {
            $count = 0;
            if ($a > 1) {
                for ($b = 0; $b < $a; $b++) {
                    foreach ($data[1] as $value) {
                        $bindValue = ":c" . $count;
                        $newData[$b] = isset($newData[$b]) ? $newData[$b] . ", " . $bindValue : $bindValue;
                        $this->bindValue[$bindValue] = is_array($value) ? $value[$b] : $value;
                        $count++;
                    }
                }
            } else {
                foreach ($data[1] as $value) {
                    $bindValue = ":c" . $count;
                    $newData[0] = isset($newData[0]) ? $newData[0] . ", " . $bindValue : $bindValue;
                    $this->bindValue[$bindValue] = $value;
                    $count++;
                }
            }
            $this->count = $count;
            return $newData;
        }
        $count = 0;
        $operator_logical = [];
        foreach ($data[1] as $key => $value) {
            if (is_string($key)) {
                if (in_array($key, ['and', 'or']) !== false) {
                    foreach ($value as $newKeys => $newValues) {
                        if (is_array($newValues)) {
                            $data[1][$key][$newKeys] = $newValues[$a];
                        }
                    }
                    $operator_logical[] = $key;
                } else {
                    if (is_array($value)) {
                        $data[1][$key] = $value[$a];
                    }
                }
            }
        }
        if (!isset($this->bindValue)) {
            $this->bindValue = [];
        } else {
            if ($data[0] === 'select') {
                if ($a == 0 && count($this->bindValue) > 0) {
                    $this->bindValue = [];
                }
            }
        }
        if (count($this->query) != 0) {
            foreach ($this->query as $value) {
                $count += substr_count($value, ":c");
            }
        }
        if (count($operator_logical) === 0) {
            $operator_logical = array_key_exists('or', $data[1]) !== false ? ['or'] : ['and'];
        }
        $key_comparation = array_keys($this->comparation);
        $computo = count($operator_logical);
        for ($a = 0; $a < $computo; $a++) {
            $datas = $data[1][$operator_logical[$a]] ?? $data[1];
            $newComparation = $oldComparation = '';
            foreach ($datas as $key => $value) {
                if (substr($key, (stripos($key, '!') !== false ? -5 : -4)) !== $oldComparation) {
                    $countComputo = count($key_comparation);
                    for ($c = 0; $c < $countComputo; $c++) {
                        if (stripos($key, $this->comparation[$key_comparation[$c]]) !== false) {
                            $newComparation = $key_comparation[$c];
                            $oldComparation = $this->comparation[$key_comparation[$c]];
                            break;
                        }
                    }
                }
                if ($newComparation === ' in ') {
                    $text = ':c';
                    $values = explode('", "', substr($value, 2, -2));
                    for ($b = $this->count; $b < ($this->count + count($values)); $b++) {
                        $this->bindValue[$text . $b] = $values[($b - $this->count)];
                        $cind_values[] = $text . $b;
                    }
                    $data[$operator_logical[$a]][] = str_replace(
                            $oldComparation,
                            $newComparation,
                            $key
                        ) . '(' . implode(', ', $cind_values) . ')';
                    $this->count += count($values);
                } elseif ($newComparation === ' between ') {
                    $values = explode(' and ', $value);
                    $counts = $this->count;
                    $computo = count($values);
                    for ($count = 0; $count < $computo; $count++) {
                        $this->bindValue[":c" . ($this->count + $count)] = $values[$count];
                    }
                    $data[$operator_logical[$a]][] = str_replace(
                            $oldComparation,
                            $newComparation,
                            $key
                        ) . ":c" . $this->count . ' and :c' . ($this->count + 1);
                    $this->count += 2;
                } else {
                    $this->bindValue[":c" . $this->count] = $value;
                    $data[$operator_logical[$a]][] = str_replace(
                            $oldComparation,
                            $newComparation,
                            $key
                        ) . ":c" . $this->count;
                    $this->count++;
                }
            }
        }
        unset($data[1]);
        $operator_logical = ['and', 'or'];
        $count = count($operator_logical);
        for ($a = 0; $a < $count; $a++) {
            if (array_key_exists($operator_logical[$a], $data)) {
                if (count($data[$operator_logical[$a]]) > 1) {
                    $newData[$a] = implode(' ' . $operator_logical[$a] . ' ', $data[$operator_logical[$a]]);
                } else {
                    $newData[$a] = $data[$operator_logical[$a]][0];
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
     *
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
     *
     * @return string
     */
    public function limit(array $data): string
    {
        if (count($data) === '1') {
            $data[1] = $data[0];
            $data[0] = '0';
        }
        return implode(", ", $data);
    }

    /**
     * @param array $data
     *
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
     *
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
     *
     * @return Assembly
     */
    public function setQuery(string $query): Assembly
    {
        $this->query[] = $query;
        return $this;
    }

}
