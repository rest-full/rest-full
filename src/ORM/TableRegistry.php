<?php

namespace Restfull\ORM;

use Restfull\Core\Instances;
use Restfull\Database\Database;
use Restfull\Error\Exceptions;
use Restfull\Filesystem\Folder;

/**
 * Class TableRegistry
 * @package Restfull\ORM
 */
class TableRegistry extends Table
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
    private $entityName = [];

    /**
     * @param string $table
     * @param string $active
     * @return TableRegistry
     * @throws Exceptions
     */
    public function registory(string $table, string $active = 'primary'): TableRegistry
    {
        $dbname = $this->http['request']->bootstrap('database')->dbname();
        if (stripos($table, ' as ') !== false) {
            list($table, $alias) = explode(" as ", $table);
        }
        $activeJoin = true;
        if ($active != 'join') {
            $activeJoin = false;
            $this->name = empty($this->name) ? $table : $this->name . ', ' . $table;
            if (!empty($alias)) {
                $this->alias = empty($this->alias) ? $alias : $this->alias . ', ' . $alias;
            }
        }
        $options = [
                'fields' => [
                        'count(distinct a.ORDINAL_POSITION) as count'
                ],
                'join' => [
                        [
                                'table' => 'INFORMATION_SCHEMA.TABLES',
                                'alias' => 'b',
                                'type' => 'inner',
                                'conditions' => 'b.TABLE_NAME=a.TABLE_NAME'
                        ]
                ],
                'table' => ['table' => 'INFORMATION_SCHEMA.COLUMNS', 'alias' => 'a'],
                'conditions' => [
                        'a.TABLE_NAME & ' => $table,
                        'a.TABLE_SCHEMA & ' => $dbname
                ]
        ];
        if ($this->dataQuery([$options])->find('first', [false])->getIterator(true)['count'] == 0) {
            throw new Exceptions('This ' . $table . ' table does not exist in the ' . $dbname . ' database.', 404);
        }
        $options['fields'] = [
                'distinct a.ORDINAL_POSITION',
                'a.COLUMN_NAME',
                'a.IS_NULLABLE',
                'a.COLUMN_TYPE',
                'b.TABLE_ROWS'
        ];
        $this->dataQuery([$options])->find('all', [false])->entityShow($table, $activeJoin);
        return $this;
    }

    /**
     * @param string $name
     * @param bool $join
     * @return object
     */
    public function entityShow(string $name, bool $join): object
    {
        foreach ($this->getIterator()->itens() as $resultset) {
            if ($this->rowsCount == 0) {
                $this->rowsCount[$name] = $resultset['TABLE_ROWS'];
            }
            if (!$join) {
                if ($resultset['ORDINAL_POSITION'] == 1) {
                    $this->primaryKey = $resultset['COLUMN_NAME'];
                } elseif (substr($resultset['COLUMN_NAME'], 0, 2) == "id") {
                    $this->foreignKey[] = substr($resultset['COLUMN_NAME'], 2);
                }
                $this->columns[$name][$resultset['ORDINAL_POSITION'] - 1] = [
                        'required' => $resultset['IS_NULLABLE'] == "NO" ? true : false,
                        'name' => $resultset['COLUMN_NAME'],
                        'type' => $resultset['COLUMN_TYPE']
                ];
            } else {
                $this->join[$name][$resultset['ORDINAL_POSITION'] - 1] = [
                        'required' => $resultset['IS_NULLABLE'] == "NO" ? true : false,
                        'name' => $resultset['COLUMN_NAME'],
                        'type' => $resultset['COLUMN_TYPE']
                ];
            }
        }

        if (!$join) {
            ksort($this->columns[$name]);
        } else {
            ksort($this->join[$name]);
        }
        unset($this->query);
        return $this;
    }

    /**
     * @param string $column
     * @return TableRegistry
     * @throws Exceptions
     */
    public function constraint(string $column)
    {
        $this->dataQuery(
                [
                        [
                                'fields' => [
                                        'TABLE_NAME'
                                ],
                                'table' => ['table' => 'INFORMATION_SCHEMA.COLUMNS'],
                                'conditions' => [
                                        'COLUMN_NAME & ' => $column,
                                        'TABLE_SCHEMA & ' => Database::getBancos()['dbname'],
                                        'IS_NULLABLE & ' => 'YES'
                                ]
                        ]
                ]
        );
        $this->find('all', [false])->entityShowConstraint();
        return $this;
    }

    /**
     * @param string $name
     * @return TableRegistry
     * @throws Exceptions
     */
    public function entityColumns(string $name): TableRegistry
    {
        $instance = (new Instances());
        $class = $instance->namespaceClass(
                "%s" . DS_REVERSE . "%s" . DS_REVERSE . "%s",
                [substr(ROOT_APP, 0, -1), MVC[2]['app'], SUBMVC[2][1]]
        );
        $files = (new Folder(str_replace(substr(ROOT_APP, -4, -1), 'src', $class)))->read();
        if (count($files['file']) > 0) {
            $exist = false;
            foreach ($files['file'] as $file) {
                if (stripos($name, substr($file, 1, 3)) !== false) {
                    $this->entityName[$name] = substr($file, 0, stripos($file, '.'));
                    $exist = true;
                    break;
                }
            }
            if ($exist) {
                $entity = $instance->resolveClass(
                        $instance->namespaceClass(
                                "%s" . DS_REVERSE . "%s",
                                [$class, $this->entityName[$name]]
                        )
                );
                for ($a = 0; $a < count($this->columns[$name]); $a++) {
                    $column = $this->columns[$name][$a];
                    if (in_array($column['type'], $instance->getMethods($entity)) !== false) {
                        foreach ($column as $key => $value) {
                            if ($key == 'type') {
                                $this->entity[$name][$function] = $value;
                            } elseif ($key == 'name') {
                                $function = $value;
                            }
                        }
                    }
                }
            } else {
                $this->entityName[$name] = 'Entity';
            }
        } else {
            $this->entityName[$name] = 'Entity';
        }
        return $this;
    }

    /**
     * @param string $key
     * @return string
     */
    public function entityName(string $key): string
    {
        return $this->entityName[$key];
    }

    /**
     * @param string $name
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

}
