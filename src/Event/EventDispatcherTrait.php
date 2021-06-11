<?php

namespace Restfull\Event;

use Restfull\Core\Instances;
use Restfull\Error\Exceptions;

/**
 * Trait EventDispatcherTrait
 * @package Restfull\Event
 */
trait EventDispatcherTrait
{

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @param string $name
     * @param array|null $data
     * @return EventManager
     * @throws Exceptions
     */
    public function dispatchEvent(string $name, array $data = null): EventManager
    {
        $instance = new Instances();
        $this->eventManager = $instance->resolveClass(
                $instance->namespaceClass("%s" . DS_REVERSE . "Event" . DS_REVERSE . "EventManager", [ROOT_NAMESPACE]),
                ['name' => $name, 'subject' => $this, 'data' => $data]
        );
        return $this->eventManager->listeners($name);
    }

}
