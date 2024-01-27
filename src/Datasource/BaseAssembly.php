<?php

declare(strict_types=1);

namespace Restfull\Datasource;

/**
 *
 */
class BaseAssembly extends Assembly
{

    /**
     * @var array
     */
    public $query = [];


    /**
     * @param array $query
     */
    public function __construct(string $query = '')
    {
        $this->query[] = $query;
        return $this;
    }

    /**
     * @param array $formats
     * @param string $typeExecute
     * @param string $query
     * @param string $typeQuery
     * @return array
     */
    public function identificationDatasInQuery(
        array $formats,
        string $typeExecute,
        string $query,
        string $typeQuery = 'table'
    ): array {
        if ($typeExecute === 'create') {
            $options = substr($query, stripos($query, $formats['conditions'][0]));
            if ($typeQuery == 'database') {
                $database = susbtr($query, stlen('create database '), stripos($query, $options) - 1);
                $datas['options'] = $this->informationSchemaTable(
                    $options,
                    [$formats['conditions'][0]],
                    [$formats['conditions'][1]]
                );
                return [$formats[$typeQuery][1][0] => $database, $database => $datas];
            }
            $table = substr($query, stlen('create table '));
            $table = substr($table, 1, stripos($query, '(') - 2);
            $datas = $this->informationSchemaTable(
                substr($query, strlen('create table ' . $table . ' ('), stripos($query, $options) - 1),
                [
                    'columns' => $formats['columns'][0],
                    'index' => $formats['index'][0],
                    'constraint' => $formats['constraint'][0]
                ],
                [
                    'columns' => $formats['columns'][1],
                    'index' => $formats['index'][1],
                    'constraint' => $formats['constraint'][1]
                ]
            );
            unset($formats['columns'], $formats['index'], $formats['constraint']);
            $datas['options'] = $this->informationSchemaTable(
                $options,
                [$formats['conditions'][0]],
                [$formats['conditions'][1]]
            );
            return [$formats[$typeQuery][1][0] => $table, $table => $datas];
        }
        $table = substr($query, stlen('insert into '), stripos(substr($query, stlen('insert into ')), ' '));
        $datas['options'] = $this->informationSchemaTable(
            $options,
            [$formats['conditions'][0]],
            [$formats['conditions'][1]]
        );
        return [$table => array_merge(['options' => $this->optionsDefine($formats['conditions'])], $datas)];
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
    public function conditions(array $datas, int $a)
    {
        if ($datas[0] === 'insert') {
            $count = 0;
            if ($a > 1) {
                for ($b = 0; $b < $a; $b++) {
                    foreach ($datas[1] as $value) {
                        $bindValue = ":c" . $count;
                        $newData[$b] = isset($newData[$b]) ? $newData[$b] . ", " . $bindValue : $bindValue;
                        $this->bindValue[$bindValue] = is_array($value) ? $value[$b] : $value;
                        $count++;
                    }
                }
            } else {
                foreach ($datas[1] as $value) {
                    $bindValue = ":c" . $count;
                    $newData[0] = isset($newData[0]) ? $newData[0] . ", " . $bindValue : $bindValue;
                    $this->bindValue[$bindValue] = $value;
                    $count++;
                }
            }
            $this->count = $count;
            return $newData;
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
                $this->count += substr_count($value, ":c");
            }
        }
        if (!isset($datas[1]['and'])) {
            $conditions['and'] = $datas[1];
            unset($datas[1]);
            $datas[1] = $conditions;
            unset($conditions);
        }
        foreach ($datas[1] as $key => $values) {
            $before = $after = '';
            $existOr = false;
            if ($key == 'or') {
                $before = ' and (';
                $after = ')';
                $existOr = !$existOr;
            }
            $key = 'concat' . ucfirst($key);
            $newDatas = $existOr ? $newDatas . $before . $this->{$key}($values) . $after : $before . $this->{$key}(
                    $values
                ) . $after;
        }
        return $newDatas;
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
     * @return BaseAssembly
     */
    public function setQuery(string $query): BaseAssembly
    {
        $this->query[] = $query;
        return $this;
    }

}