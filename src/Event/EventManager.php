<?php

declare(strict_types=1);

namespace Restfull\Event;

/**
 *
 */
class EventManager extends Event
{

    /**
     * @param string $classEvent
     * @param object $obj
     *
     * @return EventManager
     */
    public function listeners(string $classEvent, object $obj): EventManager
    {
        if ($this->name != $classEvent) {
            $this->name = $classEvent;
        }
        list($control, $method) = explode(".", $this->name);
        $this->alignData($obj);
        foreach ($this->implementedEvents($control) as $event) {
            if ($method === $event) {
                $result = $this->callMethodEnvet($method);
            }
        }
        if (isset($result)) {
            $this->result($result);
        }
        return $this;
    }

    /**
     * @param string $key
     *
     * @return string[]
     */
    public function implementedEvents(string $key): array
    {
        $listening = [
            'Controller' => ['beforeFilter', 'beforeRedirect', 'afterFilter'],
            'ORM' => ['beforeValidator', 'afterValidator', 'beforeFind', 'afterFind'],
            'View' => [
                'beforeRenderFile',
                'afterRenderFile',
                'beforeRender',
                'afterRender',
                'beforeLayout',
                'afterLayout'
            ]
        ];
        return $listening[$key];
    }
}
