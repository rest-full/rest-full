<?php

declare(strict_types=1);

namespace Restfull\Datasource;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;
use stdClass;

/**
 *
 */
trait TableTriat
{

    /**
     * @var string
     */
    protected $executionTypeForQuery = 'all';

    /**
     * @param Instances $instances
     *
     * @return TableTriat
     */
    public function instanceClass(Instances $instances): self
    {
        $this->instance = $instances;
        return $this;
    }

    /**
     * @param bool $data
     * @param array $fields
     *
     * @return object
     */
    public function excuteQuery(bool $repository, array $fields): object
    {
        $entity = $this->entityResult($fields);
        if (!$repository) {
            unset($entity->repository);
        }
        return $entity;
    }

    public function executeTheQueryAfterItIsAssembled(array $datas)
    {
        if (in_array($this->executionTypeForQuery, ['create', 'update', 'delete']) === false) {
            $this->query->recoveryDatas()->setDatas($datas);
            $details = ['deleteLimit' => [false]];
            $count = count($datas);
            if ($count > 1) {
                for ($a = 1; $a < $count; $a++) {
                    $details['deleteLimit'][$a] = $details['deleteLimit'][0];
                }
            }
            $this->changeFind($details);
        } else {
            $this->query->setDatas($datas);
            $this->find(['deleteLimit' => [false]]);
        }
        return $this->getIterator()->itens();
    }

    /**
     * @param bool $resultSet
     *
     * @return mixed
     */
    public function getIterator(bool $resultSet = false)
    {
        $result = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . MVC[2][strtolower(ROOT_NAMESPACE[0])] . DS_REVERSE . 'Resultset'
        );
        if ($resultSet) {
            return $result->execute($this)->itens();
        }
        return $result->execute($this);
    }

    /**
     * @param array $data
     * @param array $join
     *
     * @return TableTriat
     */
    public function dataQuery(array $data, array $tables): self
    {
        $a = 0;
        if ($this->executionTypeForQuery === 'query') {
            foreach ($tables as $table) {
                $options[$a] = [];
                $options[$a]['query'] = $data[$a]['query'];
                $options[$a]['table']['table'] = $table['table'];
                if (isset($table['alias'])) {
                    $options[$a]['table']['alias'] = $table['alias'];
                }
                $a++;
            }
            $this->query = $this->instance->resolveClass(
                ROOT_NAMESPACE[0] . DS_REVERSE . MVC[2][strtolower(ROOT_NAMESPACE[0])] . DS_REVERSE . 'BaseQuery',
                ['data' => $options, 'type' => $this->executionTypeForQuery]
            );
            return $this;
        }
        foreach (['built', 'union'] as $key) {
            if (isset($data[$key])) {
                $options[$key] = $data[$key];
                unset($data[$key]);
            }
        }
        foreach ($tables as $table) {
            $options[$a] = [];
            $options[$a]['table']['table'] = $table['table'];
            if (isset($table['alias'])) {
                $options[$a]['table']['alias'] = $table['alias'];
            }
            if (isset($data[$a])) {
                foreach ($data[$a] as $key => $value) {
                    $options[$a][strtolower($key)] = $value;
                }
            }
            $a++;
        }
        $this->query = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . MVC[2][strtolower(ROOT_NAMESPACE[0])] . DS_REVERSE . 'BaseQuery',
            ['instance' => $this->instance, 'data' => $options, 'type' => $this->executionTypeForQuery]
        );
        return $this;
    }

    /**
     * @param array $table
     * @param array $datas
     *
     * @return TableTriat
     */
    public function editLimit(array $deleteLimit, array $datas, string $type): self
    {
        $this->queryAssembly(['deleteLimit' => $deleteLimit, 'type' => $type, 'returnResult' => true]);
        $this->query->editLimit($this->tableRegistory, $this->result);
        return $this;
    }

    /**
     * @param bool $optionsFind
     * @param bool $lastId
     *
     * @return TableTriat
     * @throws Exceptions
     */
    public function queryAssembly(array $optionsFind, bool $lastId = false): self
    {
        $this->result = new stdClass();
        if (!isset($optionsFind['joinLimit'])) {
            $this->eventProcessVerification('beforeFind', [$this->query]);
            if (isset($optionsFind['type'])) {
                $this->find($optionsFind['deleteLimit'], $optionsFind['type'] ?? $this->executionTypeForQuery);
            } else {
                $this->find($optionsFind['deleteLimit']);
            }
            $this->eventProcessVerification('afterFind', [$this]);
        } else {
            $options = $this->query->getData();
            if (in_array($this->executionTypeForQuery, ['create', 'update', 'delete']) !== false) {
                $count = count($options);
                for ($a = 0; $a < $count; $a++) {
                    $this->keysTables($options[$a]['conditions']);
                }
                if ($lastId && in_array($this->executionTypeForQuery, ['create', 'update']) !== false) {
                    $this->lastId($lastId);
                }
            }
            if ($optionsFind['joinLimit']) {
                $this->limitQueryAssembly($optionsFind['joinLimit']);
            } else {
                $this->eventProcessVerification('beforeFind', [$this->query]);
                if (isset($optionsFind['type'])) {
                    $this->find($optionsFind['deleteLimit'], $optionsFind['type']);
                } else {
                    $this->find($optionsFind['deleteLimit']);
                }
                $this->eventProcessVerification('afterFind', [$this]);
            }
        }
        if ($optionsFind['returnResult']) {
            $this->result = $this->getIterator()->itens();
        }
        return $this;
    }

}
