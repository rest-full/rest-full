<?php

namespace Restfull\Database;

use Restfull\Datasourse\QueryInterface;

/**
 * Class Table
 * @package Restfull\Database
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
     * Table constructor.
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
     * @return int
     */
    public function countRows(bool $active): int
    {
        if ($active) {
            return count($this->result);
        }
        return $this->result['count'];
    }

    /**
     * @param QueryInterface $execution
     * @param string $execute
     * @param bool $last
     * @return Table
     */
    public function execute(QueryInterface $execution, string $execute, bool $last): Table
    {
        if (substr(strtolower($this->query), 0, 4) == 'show') {
            $this->query = strtolower($this->query);
            $execute = 'all';
        }
        if (in_array($execute, ['countRows', 'union', 'nested', 'union and nested']) !== false) {
            $execute = 'all';
        }
        if (stripos($this->query, ' table ') !== false || stripos($this->query, ' database ') !== false) {
            $this->result = $execution->$query($this);
        } else {
            $method = substr($this->query, 0, stripos($this->query, " "));
            if (in_array($method, ['select', 'show'])) {
                if (stripos($this->query, 'union') === false) {
                    $count = stripos($this->query, " from") - strlen($method . ' ');
                    $fields = explode(", ", substr($this->query, stripos($this->query, " ") + 1, $count));
                    if (count($fields) == 1) {
                        if (stripos($fields[0], 'count(') !== false) {
                            $execute = 'first';
                        }
                    } else {
                        if (stripos($this->query, "limit") !== false && substr(
                                        $this->query,
                                        stripos($this->query, "limit") + strlen("limit") + 1
                                ) == "0, 1") {
                            $execute = "first";
                        }
                    }
                }
                $this->result = $execution->select($this)->$execute();
            } else {
                if ($method == "insert") {
                    $method = 'save';
                }
                $this->result = $execution->$method($this);
            }
        }
        if ($last) {
            $this->result = $execution->lastPrimaryKey();
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
     * @return array
     */
    public function result(): array
    {
        return $this->result;
    }

}
