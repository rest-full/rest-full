<?php

declare(strict_types=1);

namespace Restfull\Event;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;

/**
 *
 */
class Event
{

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var object
     */
    protected $subject;

    /**
     * @var array
     */
    protected $datas = [];

    /**
     * @var mixed
     */
    private $result;

    /**
     * @var Instances
     */
    private $instance;


    /**
     * @param string $name
     * @param object $subject
     * @param array $datas
     */
    public function __construct(Instances $instance, string $name, object $subject, array $datas = [])
    {
        $this->instance = $instance;
        $this->name = $name;
        $this->subject = $subject;
        if (count($datas) > 0) {
            $this->datas = $datas;
        }
        return $this;
    }

    /**
     * @param string $result
     *
     * @return mixed
     */
    public function result($result = '')
    {
        if (!empty($result)) {
            $this->result = $result;
            return $this;
        }
        return $this->result;
    }

    /**
     * @param string $event
     *
     * @return mixed|null
     * @throws Exceptions
     */
    protected function callMethodEnvet(string $event)
    {
        $this->instance->correlations($this->datas);
        if ($this->instance->dependencies($this->instance->parameters($this->subject, $event), true)) {
            return $this->instance->callebleFunctionActive([$this->subject, $event], array_values($this->datas));
        }
        return null;
    }

    /**
     * @param object $obj
     *
     * @return Event
     */
    protected function alignData(object $obj): Event
    {
        $this->datas = count($this->datas) > 0 ? array_merge([$obj], $this->datas) : [$obj];
        return $this;
    }
}
