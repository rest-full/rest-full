<?php

namespace Restfull\ORM;

use Restfull\Datasourse\QueryTrait;

/**
 * Class Query
 * @package Restfull\ORM
 */
abstract class Query
{
    use QueryTrait;

    /**
     * @var int
     */
    protected $union = [];

    /**
     * @var string
     */
    protected $declaretor = '';

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var array
     */
    protected $nested = [];

    /**
     * @var array
     */
    protected $bindValue = [];

    /**
     * @var array
     */
    private $query = [];

    /**
     * @var array
     */
    private $subQuery = [];

    /**
     * @var array
     */
    private $partQuery = [];

    /**
     * @param int|null $count
     * @param string|null $key
     * @return array
     */
    public function getData(int $count = null, string $key = null): array
    {
        if (isset($count)) {
            if (isset($key)) {
                $exist = false;
                if (isset($this->data[$count])) {
                    foreach (array_keys($this->data[$count]) as $value) {
                        if ($key == $value) {
                            $exist = true;
                            break;
                        }
                    }
                }
                if ($exist) {
                    return $this->data[$count][$key];
                }
                return [];
            } else {
                return $this->data[$count];
            }
        }
        return $this->data;
    }

    /**
     * @param array $data
     * @return Query
     */
    public function setData(array $data): Query
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getBindValues(): array
    {
        if (isset($this->bindValue)) {
            return $this->bindValue;
        }
        return [];
    }

    /**
     * @param array $bindValue
     * @return Query
     */
    public function setBindValues(array $bindValue): Query
    {
        $this->bindValue = $bindValue;
        return $this;
    }

    /**
     * @param array $conditions
     * @return int
     */
    public function countConditions(array $conditions): int
    {
        $count = 1;
        if (isset($conditions['and'])) {
            $newconditions = $conditions['and'];
        }
        if (isset($conditions['or'])) {
            $newconditions = isset($newconditions) ? array_merge($newconditions, $conditions['or']) : $conditions['or'];
        } else {
            if (!isset($newconditions)) {
                $newconditions = $conditions;
            }
        }
        foreach ($newconditions as $key => $value) {
            if (is_array($value)) {
                if (count($value) > 1) {
                    $count = count($value);
                }
                $newkey = $key;
            }
            if ($count > 1) {
                break;
            }
        }
        return $count;
    }

    /**
     * @param string $name
     * @param int|null $count
     * @return mixed
     */
    public function getQuery(string $name = 'query', int $count = null)
    {
        if (isset($count)) {
            return $this->{$name}[$count];
        }
        return $this->{$name};
    }

    /**
     * @param $query
     * @param int $count
     * @param array $options
     * @return Query
     */
    public function setQuery($query, int $count = 0, array $options = []): Query
    {
        if ($options['part']) {
            $this->partQuery[$this->union['query']][] = $query;
            return $this;
        }
        if ($options['sub']) {
            $this->subQuery[$count] = $query;
            return $this;
        }
        $this->query[$count] = $query[0];
        return $this;
    }

    /**
     * @return array
     */
    public function options(): array
    {
        return $this->data;
    }
}
