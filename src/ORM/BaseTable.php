<?php

declare(strict_types=1);

namespace Restfull\ORM;

use App\Model\AppModel;
use Restfull\Container\Instances;
use Restfull\Error\Exceptions;
use Restfull\Event\Event;

/**
 *
 */
class BaseTable extends Table
{

    /**
     * @var int
     */
    private $queryAssembly = 1;

    /**
     *
     */
    public function __construct(Instances $instance, array $http)
    {
        $this->http = $http;
        $this->instanceClass($instance);
        if (stripos($this->instance->name($this), 'TableRegistry') === false) {
            $this->tableRegistory = $this->instance->resolveClass(
                ROOT_NAMESPACE[0] . DS_REVERSE . MVC[2][strtolower(
                    ROOT_NAMESPACE[0]
                )] . DS_REVERSE . SUBMVC[2][2] . 'Registry',
                ['instance' => $this->instance, 'http' => $this->http]
            );
            $this->tableRegistory->http = $this->http;
        }
        return $this;
    }

    /**
     * @return BaseTable
     */
    public function initialize(): BaseTable
    {
        return $this;
    }

    /**
     * @param array $delete
     * @return BaseTable
     *
     * @throws Exceptions
     */
    public function changeFind(array $delete): BaseTable
    {
        if (in_array($this->executionTypeForQuery, ['create', 'update', 'delete']) === false) {
            for ($a = 0; $a < $this->queryAssembly; $a++) {
                $this->query->queryAssemblyChange(['DML', 'select'], $a, $delete);
            }
            $this->query->queryAssemblyUnionOrbuilt($this->executionTypeForQuery, $delete);
        }
        return $this;
    }

    /**
     * @param int $countTable
     *
     * @return BaseTable
     */
    public function assembly(int $countTable): Table
    {
        if ($countTable !== 1) {
            $this->queryAssembly = $countTable;
        }
        return $this;
    }

    /**
     * @param array $delete
     *
     * @return BaseTable
     */
    public function find(array $delete, string $type = ''): Table
    {
        switch ($this->executionTypeForQuery) {
            case "create":
                $this->query->queryAssembly(['DML', 'insert'], 0, $delete);
                break;
            case "update":
                $this->query->queryAssembly(['DML', 'update'], 0, $delete);
                break;
            case "delete":
                $this->query->queryAssembly(['DML', 'delete'], 0, $delete);
                if ($this->query->checkIfTheJoinExistsInTheOptionsData(0)) {
                    $this->query->insertTableAfterDelete(0);
                }
                break;
            case "truncate":
                $this->query->queryAssembly(["DDL", 'truncate'], 0, $delete);
                break;
            case "query" :
                $this->query->queryAndBindValues();
                break;
            default:
                for ($a = 0; $a < $this->queryAssembly; $a++) {
                    $this->query->queryAssembly(['DML', 'select'], $a, $delete);
                }
                $this->query->queryAssemblyUnionOrbuilt(!empty($type) ? $type : $this->executionTypeForQuery, $delete);
        }
        $this->query->counts($this->executionTypeForQuery === "countRows" ? true : false);
        return $this;
    }

    /**
     * @return bool
     */
    public function columnsIdsRegistory(): bool
    {
        $columns = [];
        $tables = explode(',', $this->tableRegistory->name);
        $count = count($tables);
        for ($a = 0; $a < $count; $a++) {
            foreach ($this->tableRegistory->columns[$tables[$a]] as $column) {
                if (in_array($column['name'], ['idCreated', 'created', 'idUpdated', 'updated', 'idDeleted', 'deleted']
                    ) !== false) {
                    $columns[] = $column['name'];
                }
            }
        }
        return count($columns) > 0;
    }

    /**
     * @param bool $delete
     *
     * @return BaseTable
     */
    public function deletesValidsAuth(bool $delete): BaseTable
    {
        $rules = $this->validate->rules();
        foreach (array_keys($rules) as $key) {
            if (in_array(
                    $key,
                    [
                        'id',
                        'idAdministrator',
                        'idEmployee',
                        'idResponsible',
                        'idStatus',
                        'idStudent',
                        'idTeacher',
                        'idSituation',
                        'passencrypt'
                    ]
                ) !== false) {
                unset($rules[$key]);
            }
        }
        if ($delete) {
            foreach (array_keys($rules) as $key) {
                if ($key === 'idLevel') {
                    unset($rules[$key]);
                }
            }
            $this->validate->addNewRules($rules);
        }
        return $this;
    }

    /**
     * @param array $datas
     *
     * @return array
     */
    public function rules(array $datas): array
    {
        $names = explode(', ', $this->tableRegistory->name);
        $keys = ['idCreated', 'created', 'idUpdated', 'updated', 'idDeleted', 'deleted'];
        foreach ($keys as $key) {
            if (isset($datas[$key])) {
                unset($datas[$key]);
            }
        }
        if (in_array(
                $this->executionTypeForQuery,
                ['create', 'open', 'all', 'first', 'countRows', 'union', 'built', 'union and built', 'count']
            ) !== false) {
            $keys[] = 'id';
        }
        $count = count($names);
        for ($a = 0; $a < $count; $a++) {
            foreach ($this->tableRegistory->columns[$names[$a]] as $column) {
                if (in_array($column['name'], $keys) === false) {
                    $rules[$column['name']] = [];
                    if ($column['required']) {
                        $rules[$column['name']][] = 'required';
                    }
                    $rules[$column['name']] = count($rules[$column['name']]) > 0 ? array_merge(
                        $rules[$column['name']],
                        [$column['type']]
                    ) : [$column['type']];
                }
            }
            if ($this->tableRegistory->connectColumnNameWithTableName) {
                foreach (array_keys($this->tableRegistory->join[$names[$a]]) as $joinTable) {
                    foreach ($this->tableRegistory->join[$names[$a]][$joinTable] as $column) {
                        $name = $column['name'] . (!empty($joinTable) ? ucfirst($joinTable) : '');
                        if (!isset($rules[$name])) {
                            if (in_array($name, $datas) !== false) {
                                $rules[$name] = [];
                                if ($column['required']) {
                                    $rules[$name][] = 'required';
                                }
                                $rules[$name] = count($rules[$name]) > 0 ? array_merge($rules[$name], [$column['type']]
                                ) : [$column['type']];
                            }
                        }
                    }
                }
            }
        }
        return $rules;
    }

    /**
     * @param array $options
     *
     * @return bool
     * @throws Exceptions
     */
    public function validation(): bool
    {
        $options = $this->validate->datas();
        $this->eventProcessVerification('beforeValidator', [$options], $this->validate);
        $result = $this->validating();
        $this->eventProcessVerification('afterValidator', [], $this->validate);
        if (!$result && count($this->tableRegistory->join) > 0 && in_array(
                $this->executionTypeForQuery,
                ['create', 'update', 'delete']
            ) !== false) {
            $result = $this->checkConstraint($options);
        }
        return $result;
    }

    /**
     * @param string $behavior
     * @param array $options
     *
     * @return Mixed
     */
    public function behaviors(string $behavior, string $method, array $options = [])
    {
        $this->behaviors = $this->instance->resolveClass(
            $this->instance->locateTheFileWhetherItIsInTheAppOrInTheFramework(
                ROOT_NAMESPACE[1] . DS_REVERSE . MVC[2][strtolower(
                    ROOT_NAMESPACE[1]
                )] . DS_REVERSE . SUBMVC[2][0] . DS_REVERSE . $behavior . SUBMVC[2][0]
            ),
            ['instance' => $this->instance]
        );
        return $this->behaviors->methodActive($method, $options);
    }

    /**
     * @param string $control
     * @param string $table
     *
     * @return BaseTable
     */
    public function businessRules(): BaseTable
    {
        $tables = [];
        $names = stripos($this->tableRegistory->name, ', ') !== false ? explode(
            ', ',
            $this->tableRegistory->name
        ) : [$this->tableRegistory->name];
        $count = count($names);
        for ($a = 0; $a < $count; $a++) {
            if (isset($this->tableRegistory->join[$names[$a]])) {
                $tables['foreignKey'][$names[$a]] = array_keys($this->tableRegistory->join[$names[$a]]);
            }
            $tables['main'][] = $names[$a];
        }
        foreach ($tables['main'] as $table) {
            if (isset($tables['foreignKey'][$table])) {
                foreach ($tables['foreignKey'][$table] as $tableFk) {
                    $this->instancietedTableBussinessRules($tableFk, 'foreignkey');
                }
            }
            $this->instancietedTableBussinessRules($table, 'main');
        }
        return $this;
    }

    /**
     * @param string $table
     *
     * @return object
     * @throws Exceptions
     */
    public function newInstance(string $table = ''): object
    {
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
        if (!empty($table)) {
            return $classIntancieted->scannigTheMetadataOfTheseTables(
                ['main' => ['table' => $table]],
                false
            )->metadataScanningExecuted();
        }
        return $classIntancieted;
    }

    /**
     * @param string $class
     *
     * @return BaseTable
     */
    public function validate(array $datas): BaseTable
    {
        $this->validate = $this->instance->resolveClass(
            ROOT_NAMESPACE[1] . DS_REVERSE . MVC[2][strtolower(
                ROOT_NAMESPACE[1]
            )] . DS_REVERSE . SUBMVC[2][4] . DS_REVERSE . ROOT_NAMESPACE[1] . SUBMVC[2][4],
            ['instance' => $this->instance, 'rules' => $this->rules($datas)]
        );
        $this->validate->executionTypeForQuery($this->executionTypeForQuery)->datas($datas);
        return $this;
    }

    /**
     * @param array $tables
     *
     * @return mixed
     * @throws Exceptions
     */
    public function scannigTheMetadataOfTheseTables(array $tables, bool $returnArray = true)
    {
        $result = [];
        $main = $table = $tables['main']['table'];
        if (isset($tables['main']['alias'])) {
            $table .= ' as ' . $tables['main']['alias'];
        }
        $result[$main] = $this->tableRegistory->registory($table);
        if (isset($tables['join'][$main])) {
            $count = count($tables['join'][$main]);
            for ($a = 0; $a < $count; $a++) {
                $table = $tables['join'][$main][$a]['table'];
                if (!is_null($table)) {
                    if (isset($tables['join'][$main][$a]['alias'])) {
                        $table .= ' as ' . $tables['join'][$main][$a]['alias'];
                    }
                    $result[$tables['join'][$main][$a]['table']] = $this->tableRegistory->registory($table, 'join');
                }
            }
        }
        if ($returnArray) {
            return $result;
        }
        $this->tableRegistory->typeExecuteQuery = $this->executionTypeForQuery;
        $this->startClassTableRegistory($result, $main, true);
        return $this;
    }

    /**
     * @param array $details
     * @param string $main
     *
     * @return BaseTable
     */
    public function startClassTableRegistory(array $details, string $main, bool $identifyEntity = false): BaseTable
    {
        $inflector = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Utility' . DS_REVERSE . 'Inflector'
        );
        $options = ['fields' => ['*']];
        $this->tableRegistory->joinName = $main;
        $this->tableRegistory = $this->tableRegistory->entityShow($details[$main], $main);
        if ($identifyEntity) {
            $this->tableRegistory->entityColumns($main);
        }
        unset($details[$main]);
        $count = count($details);
        foreach ($details as $table => $result) {
            $options['join'][] = [
                'table' => $table,
                'type' => 'inner',
                'conditions' => $main . '.id' . ucfirst($inflector->singularize($table)) . ' = ' . $table . '.id'
            ];;
            $this->tableRegistory = $this->tableRegistory->entityShow($result, $table, true);
            if ($identifyEntity) {
                $this->tableRegistory->entityColumns($table);
            }
        }
        if (!$identifyEntity) {
            $this->datasinsertingDatatoExecuteTheAssembledQuery(
                [[$options], [['table' => $main]]],
                ['deleteLimit' => [false], 'returnResult' => false]
            );
        }
        return $this;
    }

    /**
     * @param array $options
     * @param array $assembly
     * @return $this
     * @throws Exceptions
     */
    public function datasInsertingDataToExecuteTheAssembledQuery(array $options, array $assembly): BaseTable
    {
        $this->dataQuery($options[0], $options[1])->queryAssembly($assembly);
        return $this;
    }

    /**
     * @param Event $event
     * @param BaseQuery $assembly
     *
     * @return null
     */
    public function beforeFind(Event $event, BaseQuery $assembly)
    {
        return null;
    }

    /**
     * @param Event $event
     * @param AppModel $appModel
     *
     * @return null
     */
    public function afterFind(Event $event, AppModel $appModel)
    {
        return null;
    }

    /**
     * @param array $datas
     * @param array $attributes
     * @param string $table
     *
     * @return array
     * @throws Exceptions
     */
    protected function thereIsThisColumnForCreateOrChangeTheTable(array $datas, array $attributes, string $table): array
    {
        $values = $datas;
        $type = 'conditions';
        if ($this->executionTypeForQuery === 'update') {
            $type = 'fields';
        }
        $newValues = $newDatas = [];
        if ($type === 'conditions') {
            $exist = false;
            foreach (['or', 'and'] as $keys) {
                if (isset($values[$type][$keys])) {
                    $newDatas = count($newDatas) > 0 ? array_merge(
                        $newDatas,
                        $values[$type][$keys]
                    ) : $values[$type][$keys];
                    if (!$exist) {
                        $exist = !$exist;
                    }
                }
            }
            if (!$exist) {
                $newDatas = $values[$type];
            }
            $values = $newDatas;
        } else {
            $values = $values[$type];
        }
        $keys = $newValues;
        foreach (array_keys($values) as $key) {
            $newKey = $key;
            if (stripos($newKey, '.') !== false) {
                $newKey = substr($key, stripos($key, '.') + 1);
                if ($type === 'conditions') {
                    $newKey = substr($newKey, 0, stripos($newKey, ' '));
                }
            }
            if (in_array($newKey, $attributes) !== false) {
                if (in_array($key, $keys) === false) {
                    $keys[] = $key;
                }
            }
        }
        foreach ($keys as $key) {
            $newValues[$key] = $values[$key];
        }
        return $newValues;
    }

}
