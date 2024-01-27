<?php

declare(strict_types=1);

namespace Restfull\Plugins;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;

/**
 *
 */
abstract class Plugin
{

    /**
     * @var $object
     */
    protected $object;

    /**
     * @var Instances
     */
    protected $instance;

    /**
     * @var array
     */
    protected $plugins = [];

    /**
     * @param Instances $instance
     */
    public function __construct(Instances $instance)
    {
        $this->instance = $instance;
        return $this;
    }

    /**
     * @param string $name
     * @param string $path
     *
     * @return Plugin
     */
    protected function seting(string $name, string $path): Plugin
    {
        $this->plugins = count($this->plugins) > 0 ? array_merge($this->plugins, [$name => $path]) : [$name => $path];
        return $this;
    }

    /**
     * @param string $name
     * @param array $options
     *
     * @return Plugin
     * @throws Exceptions
     */
    protected function identifyAndInstantiateClass(string $name, array $options = []): Plugin
    {
        if (in_array($name, array_keys($this->plugins)) === false) {
            throw new Exceptions("The {$name} plugin wasn\'t found in the list of plugins.", 404);
        }
        $this->object = count($options) > 0 ? $this->instance->resolveClass(
            $this->plugins[$name],
            $options
        ) : $this->instance->resolveClass($this->plugins[$name]);
        return $this;
    }

    /**
     * @param string $method
     * @param array $datas
     * @param bool $returnData
     *
     * @return mixed
     */
    protected function methodChange(string $method, array $datas, bool $returnData = false)
    {
        if ($returnData) {
            return $this->instance->callebleFunctionActive([$this->object, $method], array_values($datas));
        }
        $this->instance->callebleFunctionActive([$this->object, $method], array_values($datas));
        return $this;
    }

    /**
     * @param string $method
     * @param array $datas
     *
     * @return Plugin
     */
    protected function needToInstantiateAbstractClassMethod(string $method, array $datas = []): Plugin
    {
        $this->instance->callebleFunctionActive([$this->object, $method], $datas);
        return $this;
    }

}
