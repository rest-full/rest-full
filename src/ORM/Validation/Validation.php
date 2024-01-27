<?php

declare(strict_types=1);

namespace Restfull\ORM\Validation;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;
use Restfull\Event\EventDispatcherTrait;

/**
 *
 */
abstract class Validation
{
    use EventDispatcherTrait;

    /**
     * @var array
     */
    protected $error = [];

    /**
     * @var array
     */
    protected $datas = [];

    /**
     * @var array
     */
    protected $rules = [];

    /**
     * @var bool
     */
    protected $search = false;

    /**
     * @var array
     */
    protected $words = [];

    /**
     * @var string
     */
    protected $typeExecuteQuery = '';

    /**
     * @var Instances
     */
    protected $instance;

    /**
     *
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
        $chulas = 'Caralho, Porra, Puta que pariu, Filho da puta, Merda, Vai tomar no cu, Vai se foder, Veado, Puta merda, Cacete';
        $this->words = explode(', ', $chulas);
        return $this;
    }

    /**
     * @param string $typeExecuteQuery
     *
     * @return Validation
     */
    public function executionTypeForQuery(string $typeExecuteQuery): Validation
    {
        $this->executionTypeForQuery = $typeExecuteQuery;
        return $this;
    }

    /**
     * @param array $datas
     *
     * @return mixed
     */
    public function datas(array $datas = [])
    {
        if (count($datas) == 0) {
            return $this->datas;
        }
        $this->datas = $datas;
        return $this;
    }

    /**
     * @return array
     */
    public function error(): array
    {
        return $this->error;
    }

    /**
     * @param string $event
     * @param array|null $data
     *
     * @return null
     */
    public function eventProcessVerification(string $event, array $data = null)
    {
        $event = $this->dispatchEvent($this->instance, MVC[2][strtolower(ROOT_NAMESPACE[0])] . "." . $event, $data);
        return $event->result();
    }

    /**
     * @return bool
     */
    public function check(): bool
    {
        return count($this->error) > 0;
    }

    /**
     * @param string $key
     *
     * @return array
     */
    public function rules(string $key = ''): array
    {
        if (empty($key)) {
            return $this->rules;
        }
        return $this->rules[$key];
    }

    /**
     * @param object $class
     * @param string $method
     * @param string $key
     *
     * @return Validation
     * @throws Exceptions
     */
    public function searchMethod(string $method, string $key): Validation
    {
        $class = ROOT_NAMESPACE[1] . DS . MVC[2][strtolower(
                ROOT_NAMESPACE[1]
            )] . DS_REVERSE . SUBMVC[2][4] . DS . 'Validate';
        if ($this->instance->validate($class, 'file')) {
            $datas = ['keyRules' => $key, 'datas' => $this->convertArrayToString($key)];
            if (in_array($method, $this->instance->methods($class)) !== false) {
                $this->instance->correlations($datas);
                if ($this->instance->dependencies($this->instance->parameters($class), true)) {
                    throw new Exceptions(
                        'There must be two parameters that are the key of the rule and the data.', 404
                    );
                }
                $this->instance->callebleFunctionActive([$this->instance->resolveClass($class), $method], $datas);
                $this->search = false;
            } else {
                $this->search = true;
            }
        } else {
            $this->search = true;
        }
        return $this;
    }

    /**
     * @param string $keyRule
     * @return mixed
     */
    protected function convertArrayToString(string $keyRule, bool $convert = true)
    {
        $datas = $this->datas[$keyRule];
        if ($convert) {
            if ($this->dataType[$keyRule] === 'array') {
                $datas = $this->datas[$keyRule][0];
            }
        }
        return $datas;
    }

    /**
     * @param string $method
     * @return bool
     * @throws Exceptions
     */
    public function standardMethod(string $method): bool
    {
        if ($this->search) {
            if (in_array($method, [
                    'array',
                    'email',
                    'equals',
                    'numeric',
                    'float',
                    'date',
                    'time',
                    'url',
                    'file',
                    'string',
                    'alphaNumeric',
                    'search'
                ]) === false) {
                throw new Exceptions('create "validate" class file for other validations', 404);
            }
            return true;
        }
        return false;
    }

    /**
     * @param array $rules
     * @return $this
     */
    public function addNewRules(array $rules): Validation
    {
        $this->rules = $rules;
        return $this;
    }

}
