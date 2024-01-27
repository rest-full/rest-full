<?php

declare(strict_types=1);

namespace Restfull\ORM;

use App\Model\Entity\AppEntity;
use stdClass;

/**
 *
 */
class EntityFactory
{

    /**
     * @var TableRegistry
     */
    private $table;

    /**
     * @var BaseQuery
     */
    private $query;

    /**
     * @var string
     */
    private $typeExecuteQuery = '';

    /**
     * @var AppEntity
     */
    private $entity;

    /**
     * @param Table $table
     */
    public function __construct(Table $table)
    {
        $this->table = $table->metadataScanningExecuted();
        $this->query = $table->query;
        $this->executionTypeForQuery = $table->typeExecuteQuery;
        return $this;
    }

    /**
     * @return object
     */
    public function initializingEntity(): object
    {
        if (count($this->table->join) > 0) {
            $entity = $this->entity;
            list($datasResult, $datasFields) = $entity->options();
            $tables = explode(', ', $this->table->name);
            $count = count($tables);
            for ($a = 0; $a < $count; $a++) {
                $positions = $newFields = [];
                $result = new stdClass();
                foreach ($this->table->join[$tables[$a]] as $table => $column) {
                    $field = $column['name'];
                    if (in_array($field, $datasFields) !== false) {
                        $newFields[] = $field;
                        foreach ($datasResult as $number => $data) {
                            $positions[$field][] = $number;
                            $result->{$number}->{$field} = $data->{$field};
                        }
                    }
                    $registory = $this->table->newInstance($table);
                    $this->entity = $this->table->instance->resolveClass(
                        $entity->repository->entityName($registory->name),
                        [
                            'instance' => $registory->instance,
                            'options' => ['registory' => $registory, 'fields' => $newFields]
                        ]
                    );
                    $this->treatDataResult($result);
                    foreach ($this->entity->entities(true) as $key => $value) {
                        foreach ($positions[$key] as $position) {
                            $datasResult->{$position}->{$key} = $value->{$position}->{$key};
                        }
                    }
                }
            }
            $this->entity = $entity->options(['fields' => $datasFields, 'result' => $datasResult]);
        }
        $this->entity->entities();
        return $this->entity;
    }

    /**
     * @param object $datas
     * @return EntityFactory
     */
    private function treatDataResult(object $datas): EntityFactory
    {
        if ($this->entity->repository->typeExecuteQuery === 'errorValidate') {
            $datas->result = false;
        } elseif ($this->entity->repository->typeExecuteQuery === 'open') {
            foreach ($this->entity->options()[1] as $field) {
                $datas->{$field[1]} = '';
            }
        }
        return $this;
    }

    /**
     * @param object $datas
     * @param array $fields
     * @param string $typeExecuteQuery
     * @return $this
     * @throws \Restfull\Error\Exceptions
     */
    public function treatDataForEntityClassCalled(object $datas, array $fields, string $typeExecuteQuery): EntityFactory
    {
        $this->table->typeExecuteQuery = $typeExecuteQuery;
        $nameEntity = ROOT_NAMESPACE[1] . DS_REVERSE . MVC[2][strtolower(
                ROOT_NAMESPACE[1]
            )] . DS_REVERSE . SUBMVC[2][1] . DS_REVERSE . ROOT_NAMESPACE[1] . SUBMVC[2][1];
        if (stripos($this->table->name, ', ') === false) {
            $nameEntity = $this->table->entityName($this->table->name);
        }
        $this->entity = $this->table->instance->resolveClass(
            $nameEntity,
            [
                'instance' => $this->table->instance,
                'options' => [
                    'repository' => $this->table,
                    'fields' => $fields
                ]
            ]
        );
        $this->treatDataResult($datas);
        $this->entity->options(['result' => $datas]);
        return $this;
    }

}
