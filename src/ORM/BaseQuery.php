<?php

declare(strict_types=1);

namespace Restfull\ORM;

use Restfull\Container\Instances;
use Restfull\Datasource\BaseQueryFormat;
use Restfull\Error\Exceptions;

/**
 *
 */
class BaseQuery extends Query
{
    /**
     * @var BaseQueryFormat
     */
    private $queryFormat;

    /**
     * @var bool
     */
    private $lastId = false;

    /**
     * @param array $data
     */
    public function __construct(Instances $instance, array $data, string $type)
    {
        $this->queryFormat = $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Datasource' . DS_REVERSE . 'BaseQueryFormat'
        );
        $this->instance = $instance;
        foreach ($data as $key => $values) {
            if (isset($data[$key]['query'])) {
                $this->declaretor = $this->instance->assemblyClassOrPath(
                    $data[$key]['query'], [$data[$key]['table']['table']]
                );
                break;
            } else {
                if (is_string($key)) {
                    if ($key === 'built') {
                        $this->issetBuilt = true;
                        if (!isset($values['fields'])) {
                            foreach ($values['fields'] as $column) {
                                if (strripos($column, " as ") !== false) {
                                    $column = substr($column, strripos($column, " as ") + 4);
                                }
                                if (strripos($column, ".") !== false) {
                                    $column = substr($column, strripos($column, ".") + 1);
                                }
                                $this->built['fields'][] = $column;
                            }
                            unset($values['fields']);
                            $this->built = $values;
                        }
                    } elseif ($key === 'union') {
                        $this->issetUnion = true;
                        foreach ($values as $key => $value) {
                            $this->union[$key] = stripos($value, ' e ') !== false ? explode(' e ', $value) : $value;
                        }
                    }
                } else {
                    if (count($values) > 0) {
                        $this->optionsData[] = $values;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @param array $command
     * @param int $countDatas
     * @param array $limit
     *
     * @return BaseQuery
     * @throws Exceptions
     */
    public function queryAssemblyChange(array $command, int $countDatas, array $limit): BaseQuery
    {
        $this->assembly(['instance' => $this->instance]);
        $this->queryAssemblySelectChange($countDatas, $this->queryFormat->formatselected($command[0], $command[1]));
        return $this;
    }

    /**
     * @param TableRegistry $table
     * @param object $resultset
     *
     * @return TableRegistry
     */
    public function editLimit(TableRegistry $table, object $resultset): object
    {
        $tables = [];
        $count = count($this->optionsData);
        for ($a = 0; $a < $count; $a++) {
            if (in_array($this->optionsData[$a]['table'], $tables) === false) {
                $tables[] = $this->optionsData[$a]['table'];
            }
        }
        $limit = $oldLimit = $this->issetBuilt ? $this->built['limit'] : $this->optionsData[0]['limit'];
        $count = count($tables);
        for ($a = 0; $a < $count; $a++) {
            if (count($resultset) != $table->rowsCount[$tables[$a]['table']]) {
                $table->rowsCount[$tables[$a]['table']] = count($resultset);
            }
            if ($oldLimit[0] != 0 && $table->rowsCount[$tables[$a]['table']] < $oldLimit[0]) {
                $limit = [
                    $table->rowsCount[$tables[$a]['table']] - ($table->rowsCount[$tables[$a]['table']] % $oldLimit[1]),
                    $oldLimit[1]
                ];
            }
        }
        if ($oldLimit[0] != $limit[0] || $oldLimit[1] != $limit[1]) {
            for ($cunt = $limit[0]; $count < ($limit[0] + $limit[1]); $count++) {
                $result[] = $resultset[$count];
            }
        }
        return $result;
    }

    /**
     * @param bool|null $activelastid
     *
     * @return mixed
     */
    public function lastID(bool $activelastid = null)
    {
        if (is_null($activelastid)) {
            return $this->lastId;
        }
        $this->lastId = $activelastid;
        return $this;
    }

    /**
     * @return BaseQuery
     */
    public function queryAndBindValues(): BaseQuery
    {
        if ($this->issetUnion || ($this->issetUnion && $this->issetBuilt)) {
            $this->setQuery($this->declaretor, ['part' => true, 'sub' => false]);
        } elseif ($this->issetBuilt) {
            $this->setQuery($this->declaretor, ['part' => false, 'sub' => true]);
        } else {
            $this->setQuery([$this->declaretor], ['part' => false, 'sub' => false]);
        }
        $this->bindValue = [];
        return $this;
    }

    /**
     * @param array $command
     * @param int $countData
     * @param array $limit
     *
     * @return BaseQuery
     */
    public function queryAssembly(array $command, int $countData, array $limit): BaseQuery
    {
        $depedencies = ['instance' => $this->instance];
        if ($this->validQuery($command[1])) {
            $depedencies['query'] = $this->getQuery(0);
        }
        $this->assembly($depedencies);
        $format = $this->queryFormat->formatselected($command[1], 'query');
        if ($limit[0]) {
            unset($format['limit']);
        }
        if ($command[0] === "DML") {
            $querys = $this->queryAssemblySelectOrInsertOrUpdateOrDelete(
                $countData,
                $format,
                $command[1]
            );
            if ($command[1] === 'select') {
                if ($this->issetUnion || ($this->issetUnion && $this->issetBuilt)) {
                    $this->setQuery($querys, ['part' => true, 'sub' => false], $countData);
                } elseif ($this->issetBuilt) {
                    $this->setQuery($querys, ['part' => false, 'sub' => true], $countData);
                } else {
                    $this->setQuery($querys, ['part' => false, 'sub' => false], $countData);
                }
            } else {
                $this->setQuery($querys, ['part' => false, 'sub' => false], $countData);
            }
        } else {
            $querys = $this->instance->assemblyClassOrPath(
                $valid['table'], [$this->optionsData['table']]
            );
            $this->setQuery($querys, ['part' => false, 'sub' => false], $countData);
        }
        if (count($this->assembly->bindValue) > 0) {
            $this->bindValue = $this->assembly->bindValue;
        } else {
            if (count($this->bindValue) > 0) {
                $this->bindValue = [];
            }
        }
        return $this;
    }

    /**
     * @param int $countData
     *
     * @return bool
     */
    public function checkIfTheJoinExistsInTheOptionsData(int $countData): bool
    {
        return isset($this->optionsData[$countData]['join']);
    }

    /**
     * @param int $countDatas
     *
     * @return BaseQuery
     */
    public function insertTableAfterDelete(int $countDatas): BaseQuery
    {
        $main = $this->optionsData[$countDatas]['table'];
        $query[$countDatas] = str_replace(
            'delete from',
            'delete ' . $main['table'] . ' from',
            $this->getQuery($countDatas)
        );
        $this->setQuery($query, ['part' => false, 'sub' => false], $countDatas);
        return $this;
    }

    /**
     * @param string $type
     *
     * @return Table
     * @throws Exceptions
     */
    public function queryAssemblyUnionOrbuilt(string $type, array $deleteLimit = []): BaseQuery
    {
        $format = $this->queryFormat->formatselected('select', 'query');
        if ($deleteLimit[0]) {
            unset($format['limit']);
        }
        $type = stripos($type, 'and') ? explode(' and ', $type) : [$type];
        $count = 0;
        foreach (['union', 'built'] as $key) {
            if (in_array($key, $type) !== false) {
                $count++;
            }
        }
        if ($count > 0) {
            if ($count === 2) {
                $computo = count($type);
                for ($a = 0; $a < $computo; $a++) {
                    if ($a === 0 && $type[$a] === 'union') {
                        $this->queryAssemblyUnion(true);
                    } else {
                        if ($type[$a] === 'union') {
                            $this->queryAssemblyUnion();
                        } else {
                            $this->queryAssemblyBuilt($deleteLimit, $format);
                        }
                    }
                }
            } else {
                if ($type === 'built') {
                    $this->queryAssemblyBuilt($deleteLimit, $format);
                } else {
                    $this->queryAssemblyUnion();
                }
            }
        }
        return $this;
    }

    /**
     * @return BaseQuery
     */
    public function queryAssemblyWithJoinLimit(int $countData): BaseQuery
    {
        $query = $this->query[0];
        $options = $this->optionsData;
        $limit = implode(', ', $this->issetBuilt ? $this->built['limit'] : $options[0]['limit']);
        $this->setQuery([$query[$countDatas] . ' limit ' . $limit], ['part' => false, 'sub' => false]);
        return $this;
    }

}
