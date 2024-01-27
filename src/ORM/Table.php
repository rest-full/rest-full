<?php

declare(strict_types=1);

namespace Restfull\ORM;

use Restfull\Container\Instances;
use Restfull\Datasource\TableTriat;
use Restfull\Error\Exceptions;
use Restfull\Event\EventDispatcherTrait;
use Restfull\Event\EventManager;
use Restfull\ORM\Behavior\Behavior;
use stdClass;

/**
 *
 */
abstract class Table
{

    use TableTriat;
    use EventDispatcherTrait;

    /**
     * @var string
     */
    public $place = '';

    /**
     * @var array
     */
    public $http;

    /**
     * @var BaseQuery
     */
    public $query;

    /**
     * @var Instances
     */
    public $instance;

    /**
     * @var Behavior
     */
    protected $behaviors;

    /**
     * @var TableRegistry
     */
    protected $tableRegistory;

    /**
     * @var object
     */
    protected $validate;

    /**
     * @var mixed
     */
    protected $result;

    /**
     * @return mixed
     */
    public function executionTypeForQuery(string $executionTypeForQuery = '')
    {
        if (!empty($executionTypeForQuery)) {
            $this->executionTypeForQuery = $executionTypeForQuery;
            return $this;
        }
        return $this->executionTypeForQuery;
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws Exceptions
     */
    public function keysTables(array $data): bool
    {
        if ($this->executionTypeForQuery != 'create') {
            $throws = false;
            foreach ($data as $key => $value) {
                if (is_numeric($key)) {
                    $primaryKey = substr($value, 0, stripos($value, ' '));
                } else {
                    $primaryKey = substr($key, 0, stripos($key, ' '));
                }
                if ($this->tableRegistory->primaryKey != $primaryKey) {
                    $throws = true;
                    break;
                }
            }
            if ($throws) {
                throw new Exceptions("the primary key does not exist in the variable data to register in the table.");
            }
        }
        $exception = false;
        if ($this->executionTypeForQuery === 'create') {
            foreach (array_keys($data) as $key) {
                if (strlen($key) > 2) {
                    if (substr($key, 0, 2) === "id") {
                        $keys[] = $key;
                    }
                } else {
                    $keys[] = $key;
                }
            }
            foreach (explode(', ', $this->tableRegistory->name) as $name) {
                $foreignKey = false;
                if (isset($this->tableRegistory->foreignKey[$name])) {
                    $count = count($this->tableRegistory->foreignKey[$name]);
                    for ($a = 0; $a < $count; $a++) {
                        $resp = false;
                        if (in_array($this->tableRegistory->foreignKey[$name][$a], $keys) === false) {
                            $resp = !$resp;
                        }
                        if ($resp) {
                            $foreignKey = !$foreignKey;
                            break;
                        }
                    }
                }
                if ($foreignKey) {
                    $exception = !$exception;
                    break;
                }
            }
            if ($exception) {
                throw new Exceptions("the foreing key does not exist in the variable data to register in the table.");
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    public function validating(): bool
    {
        $this->validate->dataType()->validations();
        return $this->validate->check();
    }

    /**
     * @param string $event
     * @param array $data
     * @param object|null $object
     *
     * @return mixed|EventManager|null
     */
    public function eventProcessVerification(string $event, array $data = [], object $object = null)
    {
        $event = $this->dispatchEvent(
            $this->instance,
            MVC[2][strtolower(ROOT_NAMESPACE[0])] . "." . $event,
            $data,
            $object
        );
        return $event->result();
    }

    /**
     * @return array
     */
    public function getErrorValidate()
    {
        return $this->validate->error();
    }

    /**
     * @param bool $joinLimit
     *
     * @return Table
     */
    public function limitQueryAssembly(bool $joinLimit): Table
    {
        if ($joinLimit) {
            $this->query->queryAssemblyWithJoinLimit(0);
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function countRows(): bool
    {
        if ($this->executionTypeForQuery === 'countRows') {
            return $this->query->counts();
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function optionsQuery(array $options = [])
    {
        if (count($options) > 0) {
            $this->query->setDatas($options);
            return $this;
        }
        return $this->query->options();
    }

    /**
     * @param int $count
     *
     * @return array
     */
    public function query(int $count = 0): array
    {
        $query = $this->query->getQuery($count);
        if (is_array($query)) {
            $query = $query[$count];
        }
        return [$query, $this->query->getBindValues()];
    }

    /**
     * @param array $options
     *
     * @return bool
     * @throws Exceptions
     */
    public function checkConstraint(array $options): bool
    {
        $exist = 0;
        foreach ($this->tableRegistory->constraint() as $columnJoin) {
            if (isset($options['fields'])) {
                if (array_key_exists($columnJoin, $options['fields'])) {
                    $exist++;
                }
            }
            if (array_key_exists($columnJoin, $options['conditions'])) {
                $exist++;
            }
        }
        return $exist == 0;
    }

    /**
     * @return TableRegistry
     */
    public function metadataScanningExecuted(): TableRegistry
    {
        return $this->tableRegistory;
    }

    /**
     * @param array $fields
     *
     * @return object
     */
    public function entityResult(array $fields = []): object
    {
        $entity = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . MVC[2][strtolower(
                ROOT_NAMESPACE[0]
            )] . DS_REVERSE . SUBMVC[2][1] . 'Factory',
            ['table' => $this]
        );
        if (in_array($this->executionTypeForQuery, ['erroValidate', 'count', 'countRows', 'create', 'update', 'delete']
            ) !== false) {
            if ($this->executionTypeForQuery === 'create') {
                $fields = $this->lastId() ? ['lastId'] : ['result'];
            } else {
                $fields = in_array($this->executionTypeForQuery, ['count', 'countRows']
                ) !== false ? [$this->executionTypeForQuery] : ['result'];
                if ($this->executionTypeForQuery === 'erroValidate') {
                    $this->result = new stdClass();
                }
            }
        }
        $entity->treatDataForEntityClassCalled($this->result, $fields, $this->executionTypeForQuery);
        return $entity->initializingEntity();
    }

    /**
     * @param bool|null $identify
     *
     * @return mixed
     */
    public function lastId(bool $identify = null)
    {
        if (!is_null($identify)) {
            return $this->query->lastID($identify);
        }
        return $this->query->lastID();
    }

    /**
     * @param string $table
     * @param string $type
     * @param array $datas
     *
     * @return mixed
     */
    public function instancietedTableBussinessRules(string $table, string $type)
    {
        $tablechecks = $this->tableRegistory->tableRule($table)->tableCheck();
        if (count($tablechecks) > 0) {
            foreach ($tablechecks as $rule) {
                $classPath = ROOT_NAMESPACE[1] . DS_REVERSE . MVC[2][strtolower(
                        ROOT_NAMESPACE[1]
                    )] . DS_REVERSE . SUBMVC[2][2] . DS_REVERSE . $rule;
                if ($this->instance->validate(
                    str_replace(ROOT_NAMESPACE[1], PATH_NAMESPACE, $classPath) . '.php',
                    'file'
                )) {
                    $tableRegistory = $this->tableRegistory;
                    if ($type === 'foreignkey') {
                        $classBaseTable = ROOT_NAMESPACE[1] . DS_REVERSE . MVC[2][strtolower(
                                ROOT_NAMESPACE[1]
                            )] . DS_REVERSE . ROOT_NAMESPACE[1] . MVC[2][strtolower(ROOT_NAMESPACE[1])];
                        if (!$this->instance->validate($classBaseTable, 'file')) {
                            $classBaseTable = ROOT_NAMESPACE[0] . DS_REVERSE . MVC[2][strtolower(
                                    ROOT_NAMESPACE[0]
                                )] . DS_REVERSE . 'Base' . SUBMVC[2][2];
                        }
                        $classIntancieted = $this->instance->resolveClass(
                            $classBaseTable,
                            ['instance' => $this->instance, 'http' => $this->http]
                        );
                        $options = $this->query->getData();
                        $count = count($options);
                        for ($a = 0; $a < $count; $a++) {
                            unset($options[$a]['table']);
                        }
                        $classIntancieted->scannigTheMetadata(['main' => [['table' => $table]]], ['datas' => $options]);
                        $class = $this->instance->resolveClass(
                            $classPath,
                            ['baseTable' => $classIntancieted, 'table' => $table]
                        );
                    } else {
                        $class = $this->instance->resolveClass($classPath, ['baseTable' => $this, 'table' => $table]);
                    }
                    $class->attributes();
                }
            }
        }
        return $this;
    }

}
