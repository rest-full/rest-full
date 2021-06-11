<?php

namespace Restfull\ORM;

use Restfull\Core\Instances;
use Restfull\Error\Exceptions;

/**
 * Class Resultset
 * @package Restfull\ORM
 */
class Resultset
{

    /**
     * @var mixed
     */
    private $item;

    /**
     * @var Table
     */
    private $repository;

    /**
     * @param object $table
     * @return Resultset
     * @throws Exceptions
     */
    public function execute(object $table): Resultset
    {
        $this->repository = $table;
        $instance = new Instances();
        list($query, $bindValue) = $table->query($table->checkArrayQuery());
        $activeRows = (stripos($query, "as count") !== false) ? false : true;
        $execution = $instance->resolveClass(
                $instance->namespaceClass(
                        "%s" . DS_REVERSE . "Database" . DS_REVERSE . "%s",
                        [ROOT_NAMESPACE, SUBMVC[2][2]]
                ),
                ['query' => $query, 'bindValue' => $bindValue]
        );
        $execution->execute(
                $instance->resolveClass(
                        $instance->namespaceClass(
                                "%s" . DS_REVERSE . "Database" . DS_REVERSE . "%s",
                                [ROOT_NAMESPACE, SUBMVC[2][5]]
                        ),
                        ['http' => $table->http]
                ),
                $table->type(),
                $table->lastId()
        );
        $this->item = $table->countRows() ? $execution->countRows($activeRows) : $execution->result();
        return $this;
    }

    /**
     * @return mixed
     */
    public function itens()
    {
        return $this->item;
    }

}
