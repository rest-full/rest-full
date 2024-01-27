<?php

declare(strict_types=1);

namespace Restfull\Database;

/**
 *
 */
class Migration
{

    /**
     * @var object
     */
    protected $result;

    /**
     * @var string
     */
    private $query = '';

    /**
     * @param string $query
     * @param array $bindValue
     */
    public function __construct(string $query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @param Query $execution
     * @param string $execute
     * @param bool $last
     *
     * @return Table
     */
    public function definition(Query $execute, string $executionTypeForQuery): Migration
    {
        switch (substr($this->query, 0, stripos($this->query, ' '))) {
            case 'alter':
                if ($executionTypeForQuery !== 'update') {
                    $executionTypeForQuery = 'update';
                }
                break;
            case 'drop':
                if ($executionTypeForQuery !== 'delete') {
                    $executionTypeForQuery = 'delete';
                }
                break;
            default:
                if ($executionTypeForQuery !== 'create') {
                    $executionTypeForQuery = 'create';
                }
                break;
        }
        $this->result = $execute->{$executionTypeForQuery}($this);
        return $this;
    }

    /**
     * @return string
     */
    public function query(): string
    {
        return $this->query;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return 0;
    }

    /**
     * @return object
     */
    public function result()
    {
        return $this->result;
    }

}
