<?php

declare(strict_types=1);

namespace Restfull\ORM\Entity;

use Restfull\Container\Instances;
use Restfull\ORM\TableRegistry;

/**
 *
 */
abstract class Entity
{

    /**
     * @var TableRegistry
     */
    public $repository;

    /**
     * @var string
     */
    protected $nameEntity = '';

    /**
     * @var Instances
     */
    protected $instance;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var int
     */
    protected $countResult = 0;
    /**
     * @var bool
     */
    private $identifyEntity = false;

    /**
     * @return Entity
     */
    public function unset(object $entity = null): object
    {
        if (is_null($entity)) {
            unset($this->options, $this->executionTypeForQuery, $this->nameEntity, $this->identifyEntity, $this->instance);
            return $this;
        }
        unset($entity->options, $entity->typeExecuteQuery, $entity->nameEntity, $entity->identifyEntity, $entity->instance);
        return $entity;
    }

    /**
     * @param object $result
     * @return $this
     */
    protected function countObject(object $result): Entity
    {
        foreach ($result as $object) {
            $this->countResult++;
        }
        return $this;
    }

    /**
     * @return $this
     * @throws \Restfull\Error\Exceptions
     */
    protected function entity(): Entity
    {
        foreach ($this->options['fields'] as $field) {
            if (property_exists($this, $field[1]) !== true) {
                $this->{$field[1]} = '';
                if (!is_null($this->options['result']->{$field[1]})) {
                    $nameEntity = $this->repository->entityName($this->repository->name);
                    if (substr($nameEntity, strripos($nameEntity, DS_REVERSE)) !== ROOT_NAMESPACE[1] . SUBMVC[2][2]) {
                        if (in_array($field[0], $this->instance->methods($nameEntity)) !== false) {
                            $this->options['result']->{$field[1]} = $this->{$field[0]}(
                                $this->options['result']->{$field[1]}
                            );
                        }
                    }
                    $this->{$field[1]} = $this->utf8Fix($this->options['result']->{$field[1]});
                }
            }
        }
        return $this;
    }

    /**
     * @param mixed $msg
     *
     * @return string
     */
    public function utf8Fix($msg): string
    {
        if (!is_string($msg)) {
            $msg = (string)$msg;
        }
        $notUtf8 = [
            'À' => 'Ã€',
            'Á' => 'Ã',
            'Â' => 'Ã‚',
            'Ã' => 'Ãƒ',
            'Ä' => 'Ã„',
            'Å' => 'Ã…',
            'Æ' => 'Ã†',
            'Ç' => 'Ã‡',
            'È' => 'Ãˆ',
            'É' => 'Ã‰',
            'Ê' => 'ÃŠ',
            'Ë' => 'Ã‹',
            'Ì' => 'ÃŒ',
            'Í' => 'Ã',
            'Î' => 'ÃŽ',
            'Ï' => 'Ã',
            'Ð' => 'Ã',
            'Ñ' => 'Ã‘',
            'Ò' => 'Ã’',
            'Ó' => 'Ã“',
            'Ô' => 'Ã”',
            'Õ' => 'Ã•',
            'Ö' => 'Ã–',
            '×' => 'Ã—',
            'Ø' => 'Ã˜',
            'Ù' => 'Ã™',
            'Ú' => 'Ãš',
            'Û' => 'Ã›',
            'Ü' => 'Ãœ',
            'Ý' => 'Ã',
            'Þ' => 'Ãž',
            'ß' => 'ÃŸ',
            'à' => 'Ã',
            'á' => 'Ã¡',
            'â' => 'Ã¢',
            'ã' => 'Ã£',
            'ä' => 'Ã¤',
            'å' => 'Ã¥',
            'æ' => 'Ã¦',
            'ç' => 'Ã§',
            'è' => 'Ã¨',
            'é' => 'Ã©',
            'ê' => 'Ãª',
            'ë' => 'Ã«',
            'ì' => 'Ã¬',
            'í' => 'Ã­',
            'î' => 'Ã®',
            'ï' => 'Ã¯',
            'ð' => 'Ã°',
            'ñ' => 'Ã±',
            'ò' => 'Ã²',
            'ó' => 'Ã³',
            'ô' => 'Ã´',
            'õ' => 'Ãµ',
            'ö' => 'Ã¶',
            '÷' => 'Ã·',
            'ø' => 'Ã¸',
            'ù' => 'Ã¹',
            'ú' => 'Ãº',
            'û' => 'Ã»',
            'ü' => 'Ã¼',
            'ý' => 'Ã½',
            'þ' => 'Ã¾',
            'ÿ' => 'Ã¿',
            '-' => 'â€“',
            'ª' => 'Âª',
            'º' => 'Âº'
        ];
        $utf8 = array_keys($notUtf8);
        $count = count($utf8);
        for ($a = 0; $a < $count; $a++) {
            if (stripos($msg, $utf8[$a])!==false) {
                $msg = str_replace($notUtf8[$utf8[$a]], $utf8[$a], $msg);
            }
        }
        return $msg;
    }

}
