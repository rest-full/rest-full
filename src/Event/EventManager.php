<?php

namespace Restfull\Event;

use Restfull\Core\Instances;

/**
 * Class EventManager
 * @package Restfull\Event
 */
class EventManager extends Event
{

    /**
     * EventManager constructor.
     * @param string $name
     * @param object $subject
     * @param array|null $data
     */
    public function __construct(string $name, object $subject, array $data = null)
    {
        $this->name = $name;
        $this->subject = $subject;
        $this->data = $data;
        return $this;
    }

    /**
     * @param string $classEvent
     * @return $this
     */
    public function listeners(string $classEvent): EventManager
    {
        if ($this->name != $classEvent) {
            $this->name = $classEvent;
        }
        list($control, $method) = explode(".", $this->name);
        if (isset($this->data)) {
            array_unshift($this->data, $this);
        } else {
            $this->data[] = $this;
        }
        foreach ($this->implementedEvents($control) as $event) {
            if ($method == $event) {
                $result = (new Instances())->callebleFunctionActive(
                        [$this->subject, $method],
                        array_values($this->data)
                );
            }
        }
        $this->result((isset($result)) ? $result : null);
        return $this;
    }

    /**
     * @param string $key
     * @return string[]
     */
    public function implementedEvents(string $key): array
    {
        $listening = [
                'Controller' => [
                        'beforeFilter',
                        'beforeRedirect',
                        'afterFilter'
                ],
                'ORM' => [
                        'beforeValidator',
                        'afterValidator',
                        'beforeFind',
                        'afterFind'
                ],
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
