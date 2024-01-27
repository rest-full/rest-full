<?php

namespace Restfull\Datasource;

/**
 *
 */
trait QueryTrait
{

    /**
     * @var int
     */
    protected $count = 0;

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
     * @param string $type
     * @param string $command
     *
     * @return array
     */
    protected function format(string $type, string $command): array
    {
        if ($type === "DML") {
            switch ($command) {
                case "insert":
                    $valid = ['table' => 'insert into %s', 'fields' => ' (%s) values ', 'conditions' => '(%s)'];
                    break;
                case "update":
                    $valid = [
                        'table' => 'update %s',
                        'join' => ' %s join %s on %s',
                        'fields' => ' set %s',
                        'conditions' => ' where %s',
                        'group' => ' group by %s',
                        'having' => ' having %s',
                        'order' => ' order by %s',
                        'limit' => ' limit %s'
                    ];
                    break;
                case "delete":
                    $valid = ['table' => 'delete from %s', 'join' => ' %s join %s on %s', 'conditions' => ' where %s'];
                    break;
                case "select":
                    $valid = [
                        'fields' => "select %s from",
                        'table' => ' %s',
                        'join' => ' %s join %s on %s',
                        'conditions' => ' where %s',
                        'group' => ' group by %s',
                        'having' => ' having %s',
                        'order' => ' order by %s',
                        'limit' => ' limit %s'
                    ];
                    break;
            }
        } elseif ($type === "DDL") {
            $valid = ['table' => 'truncate %s'];
        }
        return $valid;
    }
}