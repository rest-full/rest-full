<?php

declare(strict_types=1);

namespace Restfull\ORM\Behavior;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;

/**
 *
 */
class Behavior
{

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var Instances
     */
    private $instance;

    /**
     *
     */
    public function __construct(Instances $instance)
    {
        $this->instance = $instance;
        return $this;
    }

    /**
     * @param Behavior $behavior
     * @param string $method
     * @param mixed $data
     *
     * @return Behavior
     * @throws Exceptions
     */
    public function checkCallMethod(Behavior $behavior, string $method, $data): Behavior
    {
        if (!in_array($method, $this->instance->methods($this->instance->name($behavior)))) {
            throw new Exceptions("Not exist this method, please create the method: " . $method);
        }
        $this->data = $this->instance->callebleFunctionActive([$this, $method], $data);
        return $this;
    }

}
