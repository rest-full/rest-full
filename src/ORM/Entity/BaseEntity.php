<?php

declare(strict_types=1);

namespace Restfull\ORM\Entity;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;

/**
 *
 */
class BaseEntity extends Entity
{

    /**
     * @var bool
     */
    private $formatFieldsExecuted = false;

    /**
     * @param Instances $instance
     * @param array $config
     * @param array $options
     */
    public function __construct(Instances $instance, array $config, array $options)
    {
        $this->instance = $instance;
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }
        $this->options = $options;
        return $this;
    }

    /**
     * @param array $options
     * @return mixed
     */
    public function options(array $options = [])
    {
        if (count($options) > 0) {
            foreach (array_keys($options) as $key) {
                if ($key == 'result') {
                    $this->countObject($options[$key]);
                }
                $this->options[$key] = $options[$key];
            }
            return $this;
        }
        $this->formatFields();
        return [$this->options['result'], $this->options['fields']];
    }

    /**
     * @return BaseEntity
     */
    private function formatFields(): BaseEntity
    {
        if (!$this->formatFieldsExecuted) {
            $this->formatFieldsExecuted = !$this->formatFieldsExecuted;
            $fields = [];
            foreach ($this->options['fields'] as $value) {
                if (stripos($value, '.') !== false) {
                    $value = substr($value, stripos($value, '.') + 1);
                }
                $field = $value;
                if (stripos($field, ' as ') !== false) {
                    $field = substr($field, stripos($field, ' as ') + 4);
                    $value = substr($value, 0, stripos($value, ' as '));
                }
                $fields[] = [$value, $field];
            }
            $this->options['fields'] = $fields;
        }
        return $this;
    }

    /**
     * @return Entity
     */
    public function entities(bool $join = false): Entity
    {
        $this->formatFields();
        if (in_array($this->repository->typeExecuteQuery, ['query', 'open']) !== false) {
            foreach ($this->options['result'] as $key => $value) {
                $this->{$key} = $this->repository->typeExecuteQuery === 'query' ? $this->utf8Fix($value) : $value;
            }
            $this->unset();
            return $this;
        }
        if ($this->countResult > 0) {
            foreach ($this->options['result'] as $key => $values) {
                if (property_exists($this, $key) !== true) {
                    if (is_numeric($key)) {
                        $this->addAttribute($key);
                    } else {
                        $this->addAttribute();
                    }
                }
            }
        }
        if (!$join) {
            $this->unset();
        }
        return $this;
    }

    /**
     * @param object|null $entity
     * @return object|$this
     */
    public function unset(object $entity = null): object
    {
        if (is_null($entity)) {
            unset($this->countResult, $this->formatFieldsExecuted);
            parent::unset();
            return $this;
        }
        unset($entity->countResult, $entity->formatFieldsExecuted, $entity->repository);
        parent::unset($entity);
        return $entity;
    }

    /**
     * @param string|null $key
     * @return BaseEntity
     * @throws Exceptions
     */
    private function addAttribute(string $key = null): BaseEntity
    {
        if (is_null($key)) {
            $this->entity();
            return $this;
        }
        $nameEntity = ROOT_NAMESPACE[1] . DS_REVERSE . MVC[2][strtolower(
                ROOT_NAMESPACE[1]
            )] . DS_REVERSE . SUBMVC[2][1] . DS_REVERSE . ROOT_NAMESPACE[1] . SUBMVC[2][1];
        if (stripos($this->repository->name, ', ') === false) {
            $nameEntity = $this->repository->entityName($this->repository->name);
        }

        $entity = $this->instance->resolveClass(
            $nameEntity,
            [
                'instance' => $this->instance,
                'options' => [
                    'repository' => $this->repository,
                    'fields' => $this->options['fields'],
                    'result' => $this->options['result']->{$key}
                ]
            ]
        );
        $entity->entity()->unset($entity);
        $this->{$key} = $entity;
        return $this;
    }

    /**
     * @param string $behavior
     * @param string $method
     * @param array $options
     *
     * @return mixed
     * @throws Exceptions
     */
    public function behavior(string $behavior, string $method, array $options = [])
    {
        $behaviors = $this->instance->resolveClass(
            $this->instance->locateTheFileWhetherItIsInTheAppOrInTheFramework(
                ROOT_NAMESPACE[1] . DS_REVERSE . MVC[2][strtolower(
                    ROOT_NAMESPACE[1]
                )] . DS_REVERSE . SUBMVC[2][0] . DS_REVERSE . $behavior . SUBMVC[2][0]
            )
        );
        return $behaviors->methodActive($method, $options);
    }

}