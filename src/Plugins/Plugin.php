<?php

namespace Restfull\Plugins;

use Restfull\Core\Instances;
use Restfull\Error\Exceptions;

/**
 * Class Plugin
 * @package Restfull\Plugins
 */
abstract class Plugin
{

    /**
     * @var $object
     */
    protected $object;
    /**
     * @var InstanceClass
     */
    protected $instance;
    /**
     * @var array
     */
    private $plugins = [];

    /**
     * @param string $name
     * @param string $path
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
     * @return Plugin
     * @throws Exceptions
     */
    protected function identifyAndInstantiateClass(string $name, array $options = []): Plugin
    {
        if (in_array($name, $this->plugins) === false) {
            throw new Exceptions("The {$name} plugin wasn\'t found in the list of plugins.", 404);
        }
        $this->instance = new Instances();
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
     * @return Plugin
     */
    protected function methodChange(string $method, array $datas, bool $returnData = false): Plugin
    {
        if ($returnData) {
            return $this->object->{$method}($datas);
        }
        $this->object->{$method}($datas);
        return $this;
    }
}