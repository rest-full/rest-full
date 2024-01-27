<?php

declare(strict_types=1);

namespace Restfull\ORM;

use Restfull\Error\Exceptions;

/**
 *
 */
class TableRegistry extends BaseTable
{

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $alias = '';

    /**
     * @var array
     */
    public $foreignKey = [];

    /**
     * @var string
     */
    public $primaryKey = '';

    /**
     * @var array
     */
    public $columns = [];

    /**
     * @var array
     */
    public $join = [];

    /**
     * @var array
     */
    public $rowsCount = [];

    /**
     * @var array
     */
    public $entity = [];

    /**
     * @var array
     */
    public $increment = [];

    /**
     * @var bool
     */
    public $connectColumnNameWithTableName = false;

    /**
     * @var string
     */
    public $joinName = '';

    /**
     * @var array
     */
    private $check = [];

    /**
     * @var array
     */
    private $entityName = [];

    /**
     * @var array
     */
    private $entities = [];

    /**
     * @var array
     */
    private $namesColumnsJoin = [];

    /**
     * @param string $table
     * @param string $active
     *
     * @return array
     * @throws Exceptions
     */
    public function registory(string $table, string $active = 'main'): object
    {
        $dbname = $this->http['request']->bootstrap('database')->dbname();
        if (stripos($table, ' as ') !== false) {
            list($table, $alias) = explode(" as ", $table);
        }
        if ($active != 'join') {
            $this->name = empty($this->name) ? $table : $this->name . ', ' . $table;
            $this->joinName = $table;
            if (!empty($alias)) {
                $this->alias = empty($this->alias) ? $alias : $this->alias . ', ' . $alias;
            }
        }
        $options = [
            'fields' => [
                'distinct a.ORDINAL_POSITION',
                'a.COLUMN_NAME',
                'a.IS_NULLABLE',
                'a.COLUMN_TYPE',
                'b.TABLE_ROWS',
                'b.AUTO_INCREMENT',
                'a.COLUMN_COMMENT'
            ],
            'join' => [
                [
                    'table' => 'INFORMATION_SCHEMA.TABLES',
                    'alias' => 'b',
                    'type' => 'inner',
                    'conditions' => 'b.TABLE_NAME = a.TABLE_NAME'
                ]
            ],
            'conditions' => [
                'a.TABLE_NAME & ' => $table,
                'b.TABLE_SCHEMA & ' => $dbname,
                'a.TABLE_SCHEMA & ' => $dbname
            ]
        ];
        $result = $this->dataQuery([$options], [['table' => 'INFORMATION_SCHEMA.COLUMNS', 'alias' => 'a']])->find(
            ['deleteLimit' => [false]]
        )->getIterator(true);
        if ($this->countObject($result) === 0) {
            throw new Exceptions('This ' . $table . ' table does not exist in the ' . $dbname . ' database.', 404);
        }
        $this->entities[] = $table;
        return $result;
    }

    /**
     * @param object $result
     * @return int
     */
    private function countObject(object $result): int
    {
        $count = 0;
        foreach ($result as $object) {
            $count++;
        }
        return $count;
    }

    /**
     * @param object $resultset
     * @param string $name
     * @param bool $join
     *
     * @return object
     */
    public function entityShow(object $resultset, string $name, bool $join = false): object
    {
        foreach ($resultset as $result) {
            if (!$join) {
                if (array_key_exists($name, $this->rowsCount) === false) {
                    $this->rowsCount[$name] = $result->TABLE_ROWS;
                }
                if (array_key_exists($name, $this->increment) === false) {
                    $this->increment[$name] = $result->AUTO_INCREMENT;
                }
                if ($result->ORDINAL_POSITION === 1) {
                    $this->primaryKey = $result->COLUMN_NAME;
                } elseif (substr($result->COLUMN_NAME, 0, 2) === "id") {
                    if ($result->IS_NULLABLE === "NO") {
                        $this->foreignKey[$name][] = $result->COLUMN_NAME;
                    }
                }
                $this->columns[$name][$result->ORDINAL_POSITION - 1] = [
                    'required' => $result->IS_NULLABLE === "NO" ? true : false,
                    'name' => $result->COLUMN_NAME,
                    'type' => $result->COLUMN_TYPE,
                    'comment' => $result->COLUMN_COMMENT
                ];
            } else {
                $this->join[$this->joinName][$name][$result->ORDINAL_POSITION - 1] = [
                    'required' => $result->IS_NULLABLE === "NO" ? true : false,
                    'name' => $result->COLUMN_NAME,
                    'type' => $result->COLUMN_TYPE,
                    'comment' => $result->COLUMN_COMMENT
                ];
                $this->namesColumnsJoin[$this->joinName][$name][$result->ORDINAL_POSITION - 1] = $result->COLUMN_NAME;
            }
        }
        if (!$join) {
            ksort($this->columns[$name]);
        } else {
            ksort($this->join[$this->joinName][$name]);
        }
        unset($this->query, $this->result);
        return $this;
    }

    /**
     * @return array
     */
    public function tableCheck(): array
    {
        return $this->check;
    }

    /**
     * @param string $table
     *
     * @return array
     */
    public function constraint(string $table): array
    {
        $options = [
            'fields' => ['COLUMN_NAME'],
            'table' => ['table' => 'INFORMATION_SCHEMA.COLUMNS'],
            'conditions' => [
                'TABLE_NAME & ' => $join,
                'TABLE_SCHEMA & ' => $this->http['request']->bootstrap('database')->dbname(),
                'COLUMN_KEY & ' => 'PRI'
            ]
        ];
        $this->executionTypeForQuery = 'all';
        return $this->dataQuery([$options])->find([false])->getIterator(true)['COLUMN_NAME'];
    }

    /**
     * @param string $name
     *
     * @return TableRegistry
     */
    public function entityColumns(string $name): TableRegistry
    {
        $classPath = RESTFULL . MVC[2][strtolower(ROOT_NAMESPACE[1])] . DS_REVERSE . SUBMVC[2][1];
        $files = $this->instance->read(str_replace(DS_REVERSE, DS, $classPath), 'folder')['files'];
        if (count($files) > 0) {
            $exist = false;
            foreach ($files as $file) {
                if (strtolower(substr($file, 0, stripos($file, '.'))) === $name . 'entity') {
                    $this->entityName[$name] = substr($file, 0, stripos($file, '.'));
                    $exist = true;
                    break;
                }
            }
            if ($exist) {
                $columns = '';
                if (isset($this->columns[$name])) {
                    $columns = $this->columns[$name];
                }
                if (empty($columns)) {
                    $tables = stripos($this->name, ', ') !== false ? explode(', ', $this->name) : [$this->name];
                    $count = count($tables);
                    for ($a = 0; $a < $count; $a++) {
                        if (isset($this->join[$tables[$a]][$name])) {
                            $columns = $this->join[$tables[$a]][$name];
                            break;
                        }
                    }
                }
            }
            if (isset($columns)) {
                $methods = $this->instance->methods($classPath . DS_REVERSE . $this->entityName[$name]);
                $count = count($columns);
                for ($a = 0; $a < $count; $a++) {
                    if (in_array($columns[$a]['name'], $methods) !== false) {
                        $this->entity[$name][] = $columns[$a]['name'];
                    }
                }
                $this->entityName[$name] = ROOT_NAMESPACE[1] . DS_REVERSE . MVC[2][strtolower(
                        ROOT_NAMESPACE[1]
                    )] . DS_REVERSE . SUBMVC[2][1] . DS_REVERSE . $this->entityName[$name];
            } else {
                $this->entityName[$name] = ROOT_NAMESPACE[1] . DS_REVERSE . MVC[2][strtolower(
                        ROOT_NAMESPACE[1]
                    )] . DS_REVERSE . SUBMVC[2][1] . DS_REVERSE . ROOT_NAMESPACE[1] . SUBMVC[2][1];
            }
        } else {
            $this->entityName[$name] = ROOT_NAMESPACE[1] . DS_REVERSE . MVC[2][strtolower(
                    ROOT_NAMESPACE[1]
                )] . DS_REVERSE . SUBMVC[2][1] . DS_REVERSE . ROOT_NAMESPACE[1] . SUBMVC[2][1];
        }
        return $this;
    }

    /**
     * @param string $name
     *
     * @return TableRegistry
     */
    public function tableRule(string $name): TableRegistry
    {
        $classPath = ROOT_NAMESPACE[1] . DS . MVC[2][strtolower(ROOT_NAMESPACE[1])] . DS . SUBMVC[2][2];
        $files = $this->instance->read(ROOT . str_replace(ROOT_NAMESPACE[1], 'src', $classPath), 'folder')['files'];
        if (count($files) > 0) {
            $exist = false;
            foreach ($files as $file) {
                if (ucfirst($name) . 'Table.php' === $file) {
                    $this->check[] = substr($file, 0, stripos($file, '.'));
                    break;
                }
            }
        } else {
            $this->check[] = '';
        }
        return $this;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function entityName(string $key): string
    {
        return $this->entityName[$key];
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function TablesColumnsAndJoinsColumns(string $name): array
    {
        $tableColumns = $this->columns[$name];
        if (count($this->join) > 0) {
            foreach ($this->join as $joinsColumns) {
                $tableColumns = array_merge($tableColumns, $joinsColumns);
            }
        }
        return $tableColumns;
    }

    /**
     * @return array
     */
    public function entities(): array
    {
        return $this->entities;
    }

}
