<?php

namespace Restfull\Event;

/**
 * Class Event
 * @package Restfull\Event
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
    protected $data = [];

    /**
     * @var mixed
     */
    private $result;

    /**
     * @param string $result
     * @return $this|mixed
     */
    public function result($result = '')
    {
        if (!empty($result)) {
            $this->result = $result;
            return $this;
        }
        return $this->result;
    }

}
