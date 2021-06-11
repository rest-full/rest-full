<?php

namespace Restfull\ORM;

use Restfull\Core\Instances;
use Restfull\Datasourse\AssemblyInterface;
use Restfull\Error\Exceptions;

/**
 * Class Assembly
 * @package Restfull\ORM
 */
class Assembly extends Query implements AssemblyInterface
{

    /**
     * @var bool
     */
    private $lastId = false;

    /**
     * @var Generator
     */
    private $generator;

    /**
     * @var InstanceClass
     */
    private $instance;

    /**
     * @var bool
     */
    private $issetUnion = false;

    /**
     * @var bool
     */
    private $issetNested = false;

    /**
     * Assembly constructor.
     * @param array $data
     * @throws \Restfull\Error\Exceptions
     */
    public function __construct(array $data)
    {
        $this->instance = new Instances();
        if (isset($data['query'])) {
            $this->declaretor = $this->instance->namespaceClass($data['query'], [$data[0]['table']['table']]);
            unset($data['query']);
        } else {
            foreach ($data as $key => $values) {
                if (is_string($key)) {
                    if ($key == 'nested') {
                        $this->issetNested = true;
                        $this->nested = $data[$key];
                        if (!isset($data[$key]['fields'])) {
                            for ($a = 0; $a < count($values); $a++) {
                                foreach ($values[$a]['fields'] as $column) {
                                    if (strripos($column, " as ") !== false) {
                                        $column = substr($column, strripos($column, " as ") + 4);
                                    }
                                    if (strripos($column, ".") !== false) {
                                        $column = substr($column, strripos($column, ".") + 1);
                                    }
                                    $this->nested['fields'][] = $column;
                                }
                            }
                        }
                        unset($data[$key]);
                        continue;
                    }
                    if ($key == 'union') {
                        $this->issetUnion = true;
                        $this->union['query'] = $data[$key];
                        unset($data[$key]);
                        continue;
                    }
                }
                if (count($values) > 0) {
                    $this->data[] = $values;
                }
            }
        }
        return $this;
    }

    /**
     * @return int
     */
    public function amountQuery(): int
    {
        return count($this->data);
    }

    /**
     * @param bool|null $activelastid
     * @return Assembly|bool|mixed
     */
    public function lastID(bool $activelastid = null)
    {
        if (is_null($activelastid)) {
            return $this->lastId;
        }
        $this->lastId = $activelastid;
        return $this;
    }

    /**
     * @return Assembly
     */
    public function queryAndBindValues(): Assembly
    {
        if ($this->issetUnion || ($this->issetUnion && $this->issetNested)) {
            $this->setQuery($this->declaretor, 0, ['part' => true, 'sub' => false]);
        } elseif ($this->issetNested) {
            $this->setQuery($this->declaretor, 0, ['part' => false, 'sub' => true]);
        } else {
            $this->setQuery($this->declaretor, 0, ['part' => false, 'sub' => false]);
        }
        if (isset($this->data['conditions'])) {
            $this->bindValue = $this->data['conditions'];
        } else {
            $this->bindValue = [];
        }
        return $this;
    }

    /**
     * @param array $command
     * @param int $countData
     * @param array $limit
     * @return Assembly
     * @throws Exceptions
     */
    public function queryAssembly(array $command, int $countData, array $limit): Assembly
    {
        if ($this->existQuery()) {
            $this->generator = new Generator($this->getQuery());
        } else {
            $this->generator = new Generator();
        }
        $valid = $this->format($command[0], $command[1]);
        if ($limit[$countData]) {
            unset($valid['limit']);
        }
        if ($command[0] == "DML") {
            $querys = $this->amount(
                    (isset($this->data[$countData]['conditions']) ? $this->countConditions(
                            $this->data[$countData]['conditions']
                    ) : 1),
                    $valid,
                    $command[1]
            );
            if ($command[1] == 'select') {
                if ($this->issetUnion || ($this->issetUnion && $this->issetNested)) {
                    $this->setQuery($querys, $countData, ['part' => true, 'sub' => false]);
                } elseif ($this->issetNested) {
                    $this->setQuery($querys, $countData, ['part' => false, 'sub' => true]);
                } else {
                    $this->setQuery($querys, $countData, ['part' => false, 'sub' => false]);
                }
            } else {
                $this->setQuery($querys, $countData, ['part' => false, 'sub' => false]);
            }
        } else {
            $querys = $this->instance->namespaceClass($valid['table'], [$this->data['table']]);
            $this->setQuery($querys, $countData, ['part' => false, 'sub' => false]);
        }
        if (count($this->generator->bindValue) > 0) {
            $this->setBindValues($this->generator->bindValue);
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function existQuery(): bool
    {
        if (count($this->getQuery()) > 0) {
            return false;
        }
        return true;
    }

    /**
     * @param int $count
     * @param array $valid
     * @param string $type
     * @return array
     * @throws Exceptions
     */
    private function amount(int $count, array $valid, string $type): array
    {
        for ($a = 0; $a < $count; $a++) {
            $params = "";
            foreach (array_keys($valid) as $value) {
                if ($value == 'fields') {
                    if (!isset($this->data[$a][$value])) {
                        $this->data[$a][$value] = ['*'];
                    }
                }
                if (isset($this->data[$a][$value])) {
                    if ($value == "join") {
                        $params .= $this->generator->{$value}([$valid[$value], $this->data[$a][$value]]);
                    } else {
                        $newData = in_array($value, ['conditions', 'fields']) ? [
                                $type,
                                $this->data[$a][$value]
                        ] : $this->data[$a][$value];
                        $result = '';
                        if ($value == 'conditions') {
                            $result = $this->generator->{$value}($newData, $a);
                        } else {
                            $result = $this->generator->{$value}($newData);
                        }
                        if (!empty($result)) {
                            $params .= $this->instance->namespaceClass($valid[$value], [$result]);
                        }
                    }
                }
            }
            $querys[] = $params;
            $this->generator->setQuery($params);
        }
        return $querys;
    }

    /**
     * @return Assembly
     * @throws Exceptions
     */
    public function nested(): Assembly
    {
        if ($this->existQuery()) {
            $generator = new Generator($this->getQuery('subQuery'));
        } else {
            $generator = new Generator();
        }
        $newFields = '';
        $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $queries = $this->getQuery('subQuery');
        $query = '';
        for ($a = 0; $a < count($queries); $a++) {
            if (!isset($this->nested['fields'])) {
                $fields = substr(
                        $queries[$a],
                        strlen('select '),
                        stripos($queries[$a], 'from') - (strlen('select ') + 1)
                );
                $fields = stripos($fields, ', ') !== false ? explode(', ', $fields) : [$fields];
                $newFields .= $this->fields($fields, substr($caracteres, $a, 1));
            }
            $query .= '(' . $queries[$a] . ') as ' . substr($caracteres, $a, 1);
            if ($a < (count($queries) - 1)) {
                $newFields .= ', ';
                $query .= ', ';
            }
        }
        $valid = $this->format('DML', 'select');
        $params = '';
        foreach (array_keys($valid) as $value) {
            if (in_array($value, ['fields', 'table']) !== false) {
                if ($value == 'table') {
                    $params .= $this->instance->namespaceClass($valid['table'], [$query]);
                } else {
                    if (isset($this->nested['fields'])) {
                        for ($a = 0; $a < count($this->nested['fields']); $a++) {
                            $newFields .= $generator->fields($this->nested['fields'][$a], substr($caracteres, $a, 1));
                            if ($a < (count($this->nested['fields']) - 1)) {
                                $newFields .= ', ';
                            }
                        }
                    }
                    $params .= $this->instance->namespaceClass($valid['fields'], [$newFields]);
                }
            } else {
                if (isset($this->nested[$value])) {
                    if ($value == 'query') {
                        $param .= $this->instance->namespaceClass($valid['query'], [$query]);
                    } elseif ($value == "join") {
                        $params .= $this->{$value}([$valid[$value], $this->nested[$value]]);
                    } else {
                        if ($value != 'fields') {
                            $newData = in_array($value, ['conditions']) ? [
                                    $command[1],
                                    $this->nested[$value]
                            ] : $this->nested[$value];
                            $result = '';
                            if ($value == 'conditions') {
                                $result = $generator->{$value}($newData, $a);
                            } else {
                                $result = $generator->{$value}($newData);
                            }
                        }
                        if (!empty($result)) {
                            $params .= $this->instance->namespaceClass($valid[$value], [$result]);
                        }
                    }
                }
            }
        }
        $this->setQuery($params, 0, ['part' => false, 'sub' => false]);
        return $this;
    }

    /**
     * @return Assembly
     */
    public function union(): Assembly
    {
        $this->setQuery(
                implode(" union ", $this->getQuery('partQuery', $this->union['query'])),
                $this->union['query'],
                ['part' => false, 'sub' => count($this->nested) > 0]
        );
        return $this;
    }

    /**
     * @return bool
     */
    public function existShow(): bool
    {
        return !empty($this->declaretor);
    }

    /**
     * @param int $count
     * @param array $valid
     * @return string
     * @throws Exceptions
     */
    private function amountInsert(int $count, array $valid): string
    {
        $params = '';
        foreach (array_keys($valid) as $value) {
            if ($value == 'fields') {
                if (!isset($this->data[$countData][$value])) {
                    $this->data[$countData][$value] = ['*'];
                }
            }
            if (isset($this->data[$countData][$value])) {
                if ($value == "join") {
                    $params .= $this->generator->{$value}([$valid[$value], $this->data[$countData][$value]]);
                } else {
                    $newData = in_array($value, ['conditions', 'fields']) ? [
                            $command[1],
                            $this->data[$countData][$value]
                    ] : $this->data[$countData][$value];
                    $result = '';
                    if ($value == 'conditions') {
                        $conditions = '';
                        for ($a = 0; $a < $count; $a++) {
                            $conditions .= $this->generator->{$value}($newData, $a);
                            if ($a < $count) {
                                $conditions .= '),(';
                            }
                        }
                        $result = $conditions;
                    } else {
                        $result = $this->generator->{$value}($newData);
                    }
                    if (!empty($result)) {
                        $params .= $this->instance->namespaceClass($valid[$value], [$result]);
                    }
                }
            }
        }
    }

    /**
     * @param array $valid
     * @return string
     * @throws Exceptions
     */
    private function amountUpdateAndDalete(array $valid): string
    {
        $params = '';
        foreach (array_keys($valid) as $value) {
            if ($value == 'fields') {
                if (!isset($this->data[$countData][$value])) {
                    $this->data[$countData][$value] = ['*'];
                }
            }
            if (isset($this->data[$countData][$value])) {
                if ($value == "join") {
                    $params .= $generator->{$value}([$valid[$value], $this->data[$countData][$value]]);
                } else {
                    $newData = in_array($value, ['conditions', 'fields']) ? [
                            $command[1],
                            $this->data[$countData][$value]
                    ] : $this->data[$countData][$value];
                    $result = '';
                    if ($value == 'conditions') {
                        $result = $generator->{$value}($newData, $a);
                    } else {
                        $result = $generator->{$value}($newData);
                    }
                    if (!empty($result)) {
                        $params .= $this->instance->namespaceClass($valid[$value], [$result]);
                    }
                }
            }
        }
        return $params;
    }

}