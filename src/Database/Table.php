<?php

declare(strict_types=1);

namespace Restfull\Database;

/**
 *
 */
class Table
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
     * @var array
     */
    private $bindValue = [];

    /**
     * @param string $query
     * @param array $bindValue
     */
    public function __construct(string $query, array $bindValue = [])
    {
        $this->query = $query;
        if (isset($bindValue)) {
            $this->bindValue = $bindValue;
        }
        return $this;
    }

    /**
     * @param bool $active
     *
     * @return int
     */
    public function countRows(bool $active): int
    {
        if ($active) {
            return count($this->result);
        }
        if (in_array("array", array_map('gettype', $this->result)) !== false) {
            if (isset($this->result[0]['count'])) {
                return $this->result[0]['count'];
            }
            return count($this->result[0]);
        }
        if (isset($this->result['count'])) {
            return $this->result['count'];
        }
        return 0;
    }

    /**
     * @param Query $execution
     * @param string $execute
     * @param bool $last
     *
     * @return Table
     */
    public function manipulation(Query $execute, string $executionTypeForQuery, bool $last): Table
    {
        if (substr(strtolower($this->query), 0, 4) === 'show') {
            $this->query = strtolower($this->query);
            $executionTypeForQuery = 'all';
        }
        if (in_array($executionTypeForQuery, ['countRows', 'union', 'built', 'union and built']) !== false) {
            $executionTypeForQuery = 'all';
        }
        $method = substr($this->query, 0, stripos($this->query, " "));
        if (in_array($method, ['select', 'show'])) {
            if (stripos($this->query, 'union') === false) {
                $count = stripos($this->query, " from") - strlen($method . ' ');
                $fields = explode(", ", substr($this->query, stripos($this->query, " ") + 1, $count));
                if (count($fields) === 1) {
                    if (stripos($fields[0], 'count(') !== false) {
                        $executionTypeForQuery = 'first';
                    }
                }
            }
            if ($executionTypeForQuery === 'open') {
                $executionTypeForQuery = 'all';
            }
            $this->result = $execute->select($this)->{$executionTypeForQuery}();
        } else {
            if ($method === "insert") {
                $method = 'save';
            }
            $this->result = $execute->{$method}($this);
        }
        if ($last) {
            $this->result = $execute->lastPrimaryKey();
        }
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
     * @return array
     */
    public function bindValues(): array
    {
        if (count($this->bindValue) > 0) {
            return array_reverse($this->bindValue);
        }
        return [];
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->bindValue);
    }

    /**
     * @return object
     */
    public function result()
    {
        return $this->result;
    }

}
