<?php

declare(strict_types=1);

namespace Restfull\Event;

use Restfull\Container\Instances;

/**
 *
 */
trait EventDispatcherTrait
{

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @param string $name
     * @param array $datas
     * @param object|null $object
     *
     * @return EventManager
     */
    public function dispatchEvent(
        Instances $instance,
        string $name,
        array $datas = [],
        object $object = null
    ): EventManager {
        if (!isset($object)) {
            $object = $this;
        }
        $this->eventManager = $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Event' . DS_REVERSE . 'EventManager',
            ['instance' => $instance, 'name' => $name, 'subject' => $object, 'datas' => $datas]
        );
        return $this->eventManager->listeners($name, $this->eventManager);
    }
}
