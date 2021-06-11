<?php

namespace Restfull\ORM\Behavior;

use Restfull\Error\Exceptions;

/**
 * Class Behavior
 * @package Restfull\ORM\Behavior
 */
class Behavior
{

    /**
     * @var mixed
     */
    public $data;

    /**
     * @return mixed
     */
    public function eventProcessVerification()
    {
        if (is_array($event)) {
            for ($a = 0; $a < count($event); $a++) {
                $event = $this->dispatchEvent(
                        MVC[2] . "." . $event[$a],
                        ['request' => $this->request, 'response' => $this->response]
                );
                if ($event->result() instanceof Response) {
                    return null;
                }
                return $event->result();
            }
        }
        $event = $this->dispatchEvent(
                MVC[2] . "." . $event,
                ['request' => $this->request, 'response' => $this->response]
        );
        if ($event->result() instanceof Response) {
            return null;
        }
        return $event->result();
    }

    /**
     * @param Behavior $behavior
     * @param string $method
     * @param mixed $data
     * @return Behavior
     * @throws Exceptions
     */
    public function checkCallMethod(Behavior $behavior, string $method, mixed $data)
    {
        if (!in_array($method, get_class_methods($behavior))) {
            throw new Exceptions("Not exist this method, please create the method: " . $method);
        }

        $this->data = $behavior->$method($data);

        return $this;
    }

}
