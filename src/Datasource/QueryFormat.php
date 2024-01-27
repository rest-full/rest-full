<?php

namespace Restfull\Datasource;

/**
 *
 */
abstract class QueryFormat
{

    /**
     * @var string
     */
    private $command = 'select';

    /**
     * @return array
     */
    protected function ddl(): array
    {
        if ($this->command == 'create') {
            return [
                'table' => 'create table %t (',
                'columns' => '%c %d %o',
                'index' => '%i key %ni (%c %o)',
                'constraint' => 'FOREIGN KEY (%c) REFERENCES %t (%tc) %o',
                'conditions' => ') %o'
            ];
        }
        if ($this->command == 'update') {
            return [
                'table' => 'alter table %t',
                'columns' => [
                    'create' => 'ADD COLUMN %c %d %o',
                    'change' => 'CHANGE COLUMN %c %d %o',
                    'delete' => 'DROP COLUMN %c'
                ],
                'index' => ['create' => 'ADD %i key %ni (%c %o)', 'delete' => 'DROP %ni'],
                'constraint' => [
                    'create' => 'ADD %n FOREIGN KEY (%c) REFERENCES %t (%tc) %o',
                    'delete' => 'DROP %n FOREIGN KEY (%c)'
                ]
            ];
        }
        if ($this->command == 'delete') {
            return [
                'table' => 'drop table %t'
            ];
        }
        return [
            'table' => 'truncate table %t'
        ];
    }

    /**
     * @return array
     */
    protected function dql(): array
    {
        return [
            'fields' => "select %f from",
            'table' => ' %t',
            'join' => ' %jy join %jt on %jc',
            'conditions' => ' where %c',
            'group' => ' group by %g',
            'having' => ' having %h',
            'order' => ' order by %o',
            'limit' => ' limit %l'
        ];
    }

    /**
     * @param string $type
     * @param string $command
     *
     * @return array
     */
    protected function dml(): array
    {
        if ($this->command === "insert") {
            return [
                'table' => 'insert into %t',
                'fields' => ' (%f) values ',
                'conditions' => '(%v)'
            ];
        }
        if ($this->command === "update") {
            return [
                'table' => 'update %t',
                'join' => ' %jy join %jt on %jc',
                'fields' => ' set %f',
                'conditions' => ' where %c',
                'group' => ' group by %g',
                'having' => ' having %h',
                'order' => ' order by %o',
                'limit' => ' limit %l'
            ];
        }
        return [
            'table' => 'delete from %t',
            'join' => ' %jy join %jt on %jc',
            'conditions' => ' where %c'
        ];
    }

    /**
     * @param string $command
     * @return mixed
     */
    public function command(string $command = '')
    {
        if (empty($command)) {
            return $this->command;
        }
        $this->command = $command;
        return $this;
    }

}