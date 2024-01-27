<?php

declare(strict_types=1);

namespace Restfull\ORM;

use Restfull\Container\Instances;
use Restfull\Datasource\BaseAssembly;

/**
 *
 */
abstract class Query
{

    /**
     * @var mixed
     */
    protected $declaretor;

    /**
     * @var array
     */
    protected $optionsData = [];

    /**
     * @var array
     */
    protected $built = [];

    /**
     * @var array
     */
    protected $union = [];

    /**
     * @var array
     */
    protected $bindValue = [];

    /**
     * @var bool
     */
    protected $issetUnion = false;

    /**
     * @var bool
     */
    protected $issetBuilt = false;

    /**
     * @var BaseAssembly
     */
    protected $assembly;

    /**
     * @var Instances
     */
    protected $instance;

    /**
     * @var array
     */
    protected $recovery = [];

    /**
     * @var int
     */
    protected $count = 0;

    /**
     * @var array
     */
    private $query = [];

    /**
     * @var array
     */
    private $subQuery = [];

    /**
     * @var array
     */
    private $partQuery = [];

    /**
     * @param bool|null $bool
     *
     * @return bool
     */
    public function counts(bool $bool = null): bool
    {
        if (isset($bool)) {
            $this->countRows = $bool;
        }
        return $this->countRows;
    }

    /**
     * @param int|null $count
     * @param string|null $key
     *
     * @return array
     */
    public function getData(int $count = null, string $key = null): array
    {
        if (isset($count)) {
            if ($this->issetBuilt) {
                if (isset($key)) {
                    return $this->built[$count][$key];
                }
                return $this->built[$count];
            }
            if (isset($key)) {
                return $this->optionsData[$count][$key];
            }
            return $this->optionsData[$count];
        }
        return $this->optionsData;
    }

    /**
     * @param array $data
     *
     * @return Query
     */
    public function setDatas(array $datas): Query
    {
        if (array_key_exists('union', $datas) !== false) {
            $this->issetUnion = true;
            if (stripos($datas['union'], ' e ') !== false) {
                $datas['union'] = explode(' e ', $datas['union']);
            }
            $this->union = $datas['union'];
            unset($datas['union']);
        }
        if (array_key_exists('built', $datas) !== false) {
            $this->issetBuilt = true;
            if (!isset($datas['built']['fields'])) {
                foreach ($datas['built']['fields'] as $column) {
                    if (strripos($column, " as ") !== false) {
                        $column = substr($column, strripos($column, " as ") + 4);
                    }
                    if (strripos($column, ".") !== false) {
                        $column = substr($column, strripos($column, ".") + 1);
                    }
                    $this->built['fields'][] = $column;
                }
                unset($datas['built']['fields']);
                $this->built = $datas['built'];
                unset($datas['built']);
            }
        }
        $this->optionsData = $datas;
        return $this;
    }

    /**
     * @return array
     */
    public function getBindValues(): array
    {
        if (isset($this->bindValue)) {
            return $this->bindValue;
        }
        return [];
    }

    /**
     * @param array $bindValue
     *
     * @return Query
     */
    public function setBindValues(array $bindValue): Query
    {
        $this->bindValue = $bindValue;
        return $this;
    }

    /**
     * @return array
     */
    public function options(): array
    {
        return count($this->optionsData) > 0 ? $this->optionsData : [['query' => $this->declaretor]];
    }

    /**
     * @return Query
     */
    public function recoveryDatas(): Query
    {
        $this->recovery = $this->optionsData;
        return $this;
    }

    /**
     * @param int $countDatas
     * @param array $format
     * @return Query
     */
    protected function queryAssemblySelectChange(int $countDatas, array $format): Query
    {
        $join = $format['join'];
        $typeOldQueryAssembled = substr($this->query[$countDatas], 0, stripos($this->query[$countDatas], ' '));
        $keys = array_keys($format);
        $count = count($keys);
        for ($a = 0; $a < $count; $a++) {
            if (isset($this->optionsData[$countDatas][$keys[$a]])) {
                $newDatas = in_array($keys[$a], ['conditions', 'fields']) ? [
                    'select',
                    $this->optionsData[$countDatas][$keys[$a]]
                ] : $this->optionsData[$countDatas][$keys[$a]];
                if ($keys[$a] == 'conditions') {
                    $newResult = $this->assembly->{$keys[$a]}($newDatas, $countDatas);
                    $this->bindValue = $this->assembly->bindValue;
                } else {
                    if ($keys[$a] == 'join') {
                        $countNewDataJoin = count($newDatas);
                        if ($countNewDataJoin > 1) {
                            for ($b = 1; $b < $countNewDataJoin; $b++) {
                                $format[$keys[$a]] .= ' ' . $join;
                            }
                        }
                    }
                    $newResult = $this->assembly->{$keys[$a]}($newDatas);
                    if (is_array($newResult)) {
                        $newResult = $this->instance->assemblyClassOrPath($format[$keys[$a]], $newResult);
                    }
                }
                if (isset($this->recovery[$countDatas][$keys[$a]])) {
                    $new = true;
                    if (in_array($typeOldQueryAssembled, ['update', 'create', 'delete']) === false) {
                        $replace = true;
                        $oldDatas = in_array($keys[$a], ['conditions', 'fields']) ? [
                            'select',
                            $this->recovery[$countDatas][$keys[$a]]
                        ] : $this->recovery[$countDatas][$keys[$a]];
                        if ($keys[$a] == 'conditions') {
                            $oldResult = substr(
                                $this->query[$countDatas],
                                stripos(
                                    $this->query[$countDatas],
                                    substr($format[$keys[$a]], 0, stripos($format[$keys[$a]], ' '))
                                )
                            );
                            if (stripos($oldResult, $format[$keys[$a + 1]]) !== false) {
                                $oldResult = substr($oldResult, 0, stripos($oldResult, $format[$key[$a + 1]]));
                            }
                        } else {
                            if ($keys[$a] == 'join') {
                                $format[$keys[$a]] = $join;
                                $countOldDataJoin = count($oldDatas);
                                if ($countOldDataJoin > 1) {
                                    for ($b = 1; $b < $countOldDataJoin; $b++) {
                                        $format[$keys[$a]] .= $join;
                                    }
                                }
                            }
                            $oldResult = $this->assembly->{$keys[$a]}($oldDatas);
                            if (is_array($oldResult)) {
                                $oldResult = $this->instance->assemblyClassOrPath($format[$keys[$a]], $oldResult);
                            }
                            $replace = $oldResult !== $newResult;
                        }
                        if ($replace) {
                            $this->query[$countDatas] = str_replace($oldResult, $newResult, $this->query[$countDatas]);
                            $new = !$new;
                        } else {
                            $new = stripos($this->query[$countDatas], $oldResult) === false;
                        }
                    }
                    if ($new) {
                        if (!empty($this->query[$countDatas]) && $a === 0) {
                            $this->query[$countDatas] = '';
                        }
                        $this->query[$countDatas] .= $this->instance->assemblyClassOrPath(
                            $format[$keys[$a]],
                            is_array($newResult) ? $newResult : [$newResult]
                        );
                    }
                } else {
                    if (!empty($this->query[$countDatas]) && $a === 0) {
                        $this->query[$countDatas] = '';
                    }
                    $this->query[$countDatas] .= $this->instance->assemblyClassOrPath(
                        $format[$keys[$a]],
                        is_array($newResult) ? $newResult : [$newResult]
                    );
                }
            }
        }
        return $this;
    }

    /**
     * @param int $count
     * @param array $format
     * @param string $type
     *
     * @return array
     */
    protected function queryAssemblySelectOrInsertOrUpdateOrDelete(int $countData, array $format, string $type): array
    {
        $countDataWhere = 1;
        $typeAssemblyInsert = false;
        if (in_array($type, ['select', 'insert']) !== false) {
            if ($type === 'insert') {
                $typeAssemblyInsert = !$typeAssemblyInsert;
            }
            $countDataWhere = $this->countConditions($countData);
        }
        for ($count = 0; $count < $countDataWhere; $count++) {
            $params = "";
            foreach (array_keys($format) as $key) {
                if (isset($this->optionsData[$countData][$key])) {
                    $newData = in_array($key, ['conditions', 'fields']) ? [
                        $type,
                        $this->optionsData[$countData][$key]
                    ] : $this->optionsData[$countData][$key];
                    if ($key === 'conditions') {
                        if ($typeAssemblyInsert) {
                            if ($countDataWhere > 1) {
                                $conditions = $format[$key];
                                for ($a = 0; $a < $countDataWhere; $a++) {
                                    if ($a != 0) {
                                        $format[$value] .= ', ' . $conditions;
                                    }
                                }
                                unset($conditions);
                            }
                        }
                        $result = $this->assembly->{$key}($newData, $typeAssemblyInsert ? $countData : $count);
                    } else {
                        if ($key === 'join') {
                            $countDataJoin = count($newData);
                            if ($countDataJoin > 1) {
                                $join = $format['join'];
                                for ($a = 1; $a < $countDataJoin; $a++) {
                                    $format[$key] .= $join;
                                }
                            }
                        }
                        $result = $this->assembly->{$key}($newData);
                    }
                    if (!empty($result)) {
                        $params .= $this->instance->assemblyClassOrPath(
                            $format[$key],
                            is_array($result) ? $result : [$result]
                        );
                    }
                }
            }
            $querys[] = $params;
            $this->assembly->setQuery($params);
        }
        return $querys;
    }

    /**
     * @param array $conditions
     * @param int $countData
     *
     * @return int
     */
    private function countConditions(int $countData): int
    {
        if (isset($this->optionsData[$countData]['conditions'])) {
            $conditions = $this->optionsData[$countData]['conditions'];
            $count = 0;
            $keysOperations = [];
            $operations = false;
            if (isset($conditions['and'])) {
                $keysOperations['and'] = array_keys($conditions['and']);
                $newconditions = $conditions['and'];
            }
            if (isset($conditions['or'])) {
                $keysOperations['or'] = array_keys($conditions['or']);
                $newconditions = isset($newconditions) ? array_merge(
                    $newconditions,
                    $conditions['or']
                ) : $conditions['or'];
            }
            if (!isset($newconditions)) {
                $newconditions = $conditions;
            }
            foreach ($newconditions as $key => $value) {
                if (is_array($value)) {
                    if (count($value) > 1) {
                        $count = count($value);
                    } else {
                        if (!$operations) {
                            $operations = !$operations;
                        }
                        $newconditions[$key] = $value[0];
                    }
                    if ($count > 0) {
                        break;
                    }
                }
            }
            if ($operations) {
                if (count($keysOperations) > 0) {
                    foreach ($keysOperations as $key => $values) {
                        $computo = count($values);
                        for ($a = 0; $a < $computo; $a++) {
                            $this->optionsData[$countData]['conditions'][$key][$values[$a]] = $newconditions[$values[$a]];
                        }
                    }
                } else {
                    $this->optionsData[$countData]['conditions'] = $newconditions;
                }
            }
            return $count === 0 ? 1 : $count;
        }
        return 1;
    }

    /**
     * @return Query
     */
    protected function queryAssemblyUnion(bool $sub = false): BaseQuery
    {
        $querys = $this->getQuery('partQuery');
        $excuted = false;
        foreach ($this->union as $values) {
            if (is_array($values)) {
                $excuted = true;
                $unions = [];
                $count = count($values);
                for ($a = 0; $a < $count; $a++) {
                    $unions[$a] = $querys[$values[$a]];
                    $numbersQuerys[] = $values[$a];
                }
                $newQuerys[] = implode(' union ', $unions);
            }
        }
        if ($excuted) {
            foreach ($querys as $key => $value) {
                if (in_array($key, $numbersQuerys) === false) {
                    $newQuerys[] = $value;
                }
            }
        } else {
            $newQuerys[] = implode(' union ', $querys);
        }
        $this->setQuery($newQuerys, ['part' => false, 'sub' => $sub]);
        return $this;
    }

    /**
     * @param string $name
     * @param int|null $count
     *
     * @return mixed
     */
    public function getQuery(int $count = null, string $name = 'query')
    {
        if (isset($count)) {
            return $this->{$name}[$count];
        }
        return $this->{$name};
    }

    /**
     * @param array $query
     * @param int $count
     * @param array $options
     *
     * @return Query
     */
    public function setQuery(array $query, array $options = [], int $count = 0): Query
    {
        if ($options['part']) {
            $this->partQuery[$count] = $query[0];
            return $this;
        }
        if ($options['sub']) {
            $this->subQuery[$count] = $query[0];
            return $this;
        }
        $this->query[$count] = $query[0];
        return $this;
    }

    /**
     * @return void
     */
    protected
    function builtConditions()
    {
    }

    /**
     * @return $Query
     */
    protected function queryAssemblyBuilt(array $limit, array $format): BaseQuery
    {
        $queries = $this->getQuery('subQuery')->assembly(['instance' => $this->instance, 'query' => $queries]);
        $method = 'built' . ucfirst($this->builtType);
        $partOfTheQuery = $this->{$method}($queries);
        if ($limit[0]) {
            unset($format['limit']);
        }
        $params = '';
        foreach (array_keys($format) as $key) {
            if (in_array($key, ['fields', 'group', 'having', 'order', 'limit']) !== false) {
                if (isset($this->built[$key])) {
                    $params .= $this->instance->assemblyClassOrPath(
                        $format[$key],
                        [
                            $this->assembly->{$key}(
                                $key === 'fields' ? ['built', $this->built[$key]] : $this->built[$key]
                            )
                        ]
                    );
                }
            } elseif ($value === 'table') {
                $params .= $this->instance->assemblyClassOrPath($format[$key], [$query]);
            }
        }
        $this->setQuery([$params], ['part' => false, 'sub' => false]);
        return $this;
    }

    protected function assembly(array $dependecies): Query
    {
        $this->assembly = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Datasource' . DS_REVERSE . 'BaseAssembly',
            $dependecies
        );
        return $this;
    }

    /**
     * @return bool
     */
    protected
    function existQuery(): bool
    {
        if (count($this->query) > 0) {
            return false;
        }
        return true;
    }

    /**
     * @param array $queries
     * @return string
     */
    protected
    function builtTable(
        array $queries
    ): string {
        $newFields = '';
        $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $query = '';
        $count = count($queries);
        for ($a = 0; $a < $count; $a++) {
            if (!isset($this->built['fields'])) {
                $fields = substr(
                    $queries[$a],
                    strlen('select '),
                    stripos($queries[$a], 'from') - (strlen('select ') + 1)
                );
                $fields = stripos($fields, ', ') !== false ? explode(', ', $fields) : [$fields];
                $newFields .= $this->fields($fields, substr($caracteres, $a, 1));
            }
            $query .= '(' . $queries[$a] . ') as ' . substr($caracteres, $a, 1);
            if ($a < (count($queries) - 1)) {
                $newFields .= ', ';
                $query .= ', ';
            }
        }
        return $query;
    }

    protected function validQuery(string $command,int $count = 0): bool
    {
        if (count($this->query) > 0) {
            return stripos($this->query[$count], $command) !== false;
        }
        return false;
    }
}
