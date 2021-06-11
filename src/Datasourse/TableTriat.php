<?php

namespace Restfull\Datasourse;

use Restfull\Error\Exceptions;
use Restfull\ORM\Assembly;
use Restfull\ORM\TableRegistry;

/**
 * Trait TableTriat
 * @package Restfull\Datasourse
 */
trait TableTriat
{

    /**
     * @var AssemblyInterface
     */
    public $query;
    /**
     * @var array
     */
    public $http;
    /**
     * @var TableRegistry
     */
    protected $tableRegistory;
    /**
     * @var string
     */
    private $queryAssembly = '';
    /**
     * @var InstanceClass
     */
    private $instance;

    /**
     * @return TableTriat
     */
    public function entityShowConstraint(): self
    {
        foreach ($this->getIterator()->itens() as $resultset) {
            $this->foreignKey[] = $resultset['TABLE_NAME'];
        }
        return $this;
    }

    /**
     * @param bool $resultSet
     * @return mixed
     */
    public function getIterator(bool $resultSet = false)
    {
        $result = $this->instance->resolveClass(
                $this->instance->namespaceClass(
                        "%s" . DS_REVERSE . "%s" . DS_REVERSE . "Resultset",
                        [ROOT_NAMESPACE, MVC[2]['restfull']]
                )
        );
        if ($resultSet) {
            return $result->execute($this)->itens();
        }
        return $result->execute($this);
    }

    /**
     * @param array $table
     * @param bool $repository
     * @return TableTriat
     */
    public function editLimit(array $table, bool $repository): self
    {
        $options = $this->query->getData();
        for ($a = 0; $a < count($table); $a++) {
            $this->tableRegistory->rowsCount[$table[$a]] = $this->queryAssembly('countRows', false, true)->excuteQuery(
                    'countRows',
                    $repository
            );
            if ($options[$a]['limit'][0] != 0 && $this->tableRegistory->rowsCount[$table[$a]] < $options[$a]['limit'][0]) {
                $rest = $this->tableRegistory->rowsCount[$table[$a]] % $options['limit'][1];
                $options[$a]['limit'][0] = $this->tableRegistory->rowsCount[$table[$a]] - $rest;
            }
        }
        $this->query->setData($options);
        return $this;
    }

    /**
     * @param string $type
     * @param bool $repository
     * @param array|null $data
     * @return object
     */
    public function excuteQuery(string $type = 'all', bool $repository, array $data = null): object
    {
        if ($type == "open") {
            $entity = $this->entityResult((isset($data['fields']) ? $data['fields'] : null));
            if (!$repository) {
                unset($entity->repository);
            }
            return $entity;
        } elseif (in_array($type, ['create', 'update', 'delete', 'countRows'])) {
            return $this->getIterator()->itens();
        } else {
            $isset = 0;
            for ($a = 0; $a < count($data); $a++) {
                if (isset($data[$a]['fields'])) {
                    $newData['fields'][] = $data[$a]['fields'];
                    $isset++;
                }
            }
            if ($isset != 0) {
                $entity = $this->typeQuery($type)->entityResult($newData['fields']);
                if (!$repository) {
                    unset($entity->repository);
                }
                return $entity;
            }
            $entity = $this->typeQuery($type)->entityResult();
            if (!$repository) {
                unset($entity->repository);
            }
            return $entity;
        }
    }

    /**
     * @param array $fields
     * @return object
     */
    public function entityResult(array $fields = null): object
    {
        $entities = $this->instance->resolveClass(
                $this->instance->namespaceClass(
                        "%s" . DS_REVERSE . "%s" . DS_REVERSE . "%s" . DS_REVERSE . "App%s",
                        [
                                substr(ROOT_APP, 0, -1),
                                MVC[2]['app'],
                                SUBMVC[2][1],
                                SUBMVC[2][1]
                        ]
                ),
                ['table' => $this->tableRegistory]
        );
        unset($entities->option, $entities->type);
        if ($this->typeQuery != 'open') {
            $datas = $this->getIterator()->itens();
            if (in_array("array", array_map('gettype', $datas)) === false) {
                $datas = [$datas];
            }
        } else {
            $datas = [['']];
        }
        $show = false;
        if ($this->query->existShow()) {
            $show = true;
        }
        $tables = $this->tableRegistory->name;
        $tables = stripos($tables, ', ') !== false ? explode(', ', $tables) : [$tables];
        for ($a = 0; $a < count($tables); $a++) {
            $entitiesData = $this->identifyTheDataInThatTable(
                    $this->tableRegistory->entityName($tables[$a]),
                    [
                            'table' => $this->tableRegistory->tablesColumnsAndJoinsColumns($tables[$a]),
                            'fields' => $this->fieldsSelect($show, $datas, $a)
                    ],
                    $datas
            );
            if (count($entitiesData) == 1) {
                foreach ($entitiesData[0] as $key => $value) {
                    $entities->$key = $value;
                }
            } else {
                for ($a = 0; $a < count($entitiesData); $a++) {
                    $entities->$a = $entitiesData[$a];
                }
            }
        }
        return $entities;
    }

    /**
     * @param string $table
     * @param array $columns
     * @param array $data
     * @return array
     */
    public function identifyTheDataInThatTable(string $table, array $columns, array $data): array
    {
        if (!isset($columns['fields']['Field'])) {
            foreach ($columns['table'] as $values) {
                $newColumns[] = $values['name'];
            }
        } else {
            $columns = $columns['fields'];
        }
        if (count($data) > 1) {
            $newDatas = [];
            for ($a = 0; $a < count($data); $a++) {
                if (count($data[$a]) == 0) {
                    $newDatas = [$data[$a]];
                } else {
                    foreach ($data[$a] as $key => $value) {
                        if (in_array($key, $newColumns) !== false) {
                            $newDatas[$key] = $value;
                        } elseif ($key == 'count') {
                            $newDatas[$key] = $value;
                        }
                    }
                }
                if (count($newDatas) > 0) {
                    $entities[$a] = $this->initializeEntity(
                            $this->tableRegistory,
                            $table,
                            $this->typeQuery,
                            ['result' => $data, 'fields' => $columns['fields']]
                    );
                }
            }
        } else {
            $entities[0] = $this->initializeEntity(
                    $this->tableRegistory,
                    $table,
                    $this->typeQuery,
                    ['result' => $data, 'fields' => $columns['fields']]
            );
        }
        return $entities;
    }

    /**
     * @param TableRegistry $registory
     * @param string $table
     * @param string $type
     * @param array $options
     * @return object
     */
    public function initializeEntity(TableRegistry $registory, string $table, string $type, array $options): object
    {
        if ($table != 'Entity') {
            $entity = $this->instance->resolveClass(
                    $this->instance->namespaceClass(
                            "%s" . DS_REVERSE . "%s" . DS_REVERSE . "%s" . DS_REVERSE . "%s",
                            [
                                    substr(ROOT_APP, 0, -1),
                                    MVC[2]['app'],
                                    SUBMVC[2][1],
                                    $table
                            ]
                    ),
                    ['table' => $registory, 'config' => array_merge(['type' => $type], $options)]
            );
            return $entity;
        }
        $entity = $this->instance->resolveClass(
                $this->instance->namespaceClass(
                        "%s" . DS_REVERSE . "%s" . DS_REVERSE . "%s" . DS_REVERSE . "App%s",
                        [
                                substr(ROOT_APP, 0, -1),
                                MVC[2]['app'],
                                SUBMVC[2][1],
                                SUBMVC[2][1]
                        ]
                ),
                ['table' => $registory, 'config' => array_merge(['type' => $type], $options)]
        );
        return $entity;
    }

    /**
     * @param bool $show
     * @param array $datas
     * @param int $number
     * @param array $fields
     * @return array|mixed
     */
    public function fieldsSelect(bool $show, array $datas, int $number)
    {
        $columns = $this->query->getData($number, 'fields');
        if ($show) {
            foreach (array_keys($datas[0]) as $key) {
                if (in_array($key, ['Field', 'Comment']) !== false) {
                    $newFields[] = $key;
                }
            }
            $columns = $newFields;
        }
        $b = 0;
        $alias = [];
        foreach ($columns as $value) {
            if (strripos($value, " as ") !== false) {
                $alias[] = substr($value, strripos($value, " as ") + 4);
                $value = substr($value, 0, strripos($value, " as "));
            }
            if (strripos($value, ".") !== false) {
                if (strripos($value, " as ") === false && count($alias) == 0) {
                    $alias[] = substr($value, strripos($value, ".") + 1);
                }
                $value = substr($value, strripos($value, ".") + 1);
            }
            $columns[$b] = ['column' => $value, 'alias' => $alias];
            $b++;
            $alias = [];
        }
        return $columns;
    }

    /**
     * @param string $type
     * @param bool $lastId
     * @param bool $deleteLimit
     * @return TableTriat
     */
    public function queryAssembly(string $type, bool $lastId, bool $deleteLimit = false): self
    {
        $options = $this->query->getData();
        if (in_array($type, ['create', 'update', 'delete'])) {
            $this->keysTables($options[0]['conditions'], $type);
            if ($lastId && in_array($type, ['create', 'update']) !== false) {
                $this->lastId($lastId);
            }
        }
        for ($a = 0; $a < count($options); $a++) {
            $newDeleteLimit[] = $deleteLimit ? $deleteLimit : isset($options[$a]['limit']);
        }
        $this->eventProcessVerification('beforeFind', [$this->query]);
        $this->find($type, $newDeleteLimit);
        $this->eventProcessVerification('afterFind', [$this]);
        return $this;
    }

    /**
     * @param array $data
     * @return TableTriat
     * @throws Exceptions
     */
    public function dataQuery(array $data): self
    {
        if (isset($data['query'])) {
            $options['query'] = $data['query'];
        }
        foreach (['name', 'alias'] as $value) {
            if (isset($this->$value)) {
                $names = $this->{$value};
            } else {
                $names = $this->tableRegistory->{$value};
            }
            ${$value} = stripos($names, ', ') ? explode(', ', $names) : [$names];
        }
        for ($a = 0; $a < count($name); $a++) {
            $options[$a] = [];
            $options[$a]['table']['table'] = $name[$a];
            $options[$a]['table']['alias'] = $alias[$a];
            if (isset($data[$a])) {
                foreach ($data[$a] as $key => $value) {
                    $options[$a][strtolower($key)] = $value;
                }
            }
        }
        if (isset($data['union'])) {
            $options['union'] = $data['union'];
        }
        if (isset($data['nested'])) {
            $options['nested'] = $data['nested'];
        }
        $this->query = new Assembly($options);
        return $this;
    }

    /**
     * @param string $assembly
     * @return TableTriat
     */
    public function assembly(string $assembly): self
    {
        $this->queryAssembly = $assembly;
        return $this;
    }

}
