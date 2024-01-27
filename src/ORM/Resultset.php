<?php

declare(strict_types=1);

namespace Restfull\ORM;

/**
 *
 */
class Resultset
{

    /**
     * @var mixed
     */
    private $item;

    /**
     * @param object $table
     *
     * @return Resultset
     */
    public function execute(baseTable $table): Resultset
    {
        $http = $table->http;
        if (!isset($http)) {
            $http = $table->metadataScanningExecuted()->http;
        }
        list($query, $bindValue) = $table->query();
        $command = in_array(substr($query, 0, stripos($query, ' ')), ['select', 'insert', 'update', 'delete']
        ) !== false ? 'manipulation' : 'definition';
        $queryExecute = $table->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Database' . DS_REVERSE . SUBMVC[2][5],
            ['command' => $command, 'database' => $http['request']->bootstrap('database')]
        );
        if ($command === 'manipulation') {
            $execution = $table->instance->resolveClass(
                ROOT_NAMESPACE[0] . DS_REVERSE . 'Database' . DS_REVERSE . SUBMVC[2][2],
                ['query' => $query, 'bindValue' => $bindValue]
            );
            $execution->manipulation($queryExecute, $table->executionTypeForQuery(), $table->lastId());
            $this->item = $table->countRows() ? $execution->countRows(
                stripos($query, "as count") !== false || $table->typeExecuteQuery === 'countRows'
            ) : $execution->result();
        } else {
            $execution = $table->instance->resolveClass(
                ROOT_NAMESPACE[0] . DS_REVERSE . 'Database' . DS_REVERSE . SUBMVC[2][3],
                ['query' => $query]
            );
            $execution->definition($queryExecute, $table->executionTypeForQuery());
            $this->item = $execution->result();
        }
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
