<?php

declare(strict_types=1);

namespace Restfull\Plugins;

use Restfull\Error\Exceptions;

/**
 *
 */
class Abstration extends Plugin
{

    /**
     * @var string
     */
    private $name = '';

    /**
     * @param string $name
     * @param string $path
     *
     * @return Abstration
     * @throws Exceptions
     */
    public function setClass(string $name, string $path = ''): Abstration
    {
        if (empty($path)) {
            $exist = true;
            $foldersAndFiles = $this->instance->read(ROOT_ABSTRACT, 'folder')['files'];
            foreach ($foldersAndFiles as $file) {
                if ($name === pathinfo($file, PATHINFO_FILENAME)) {
                    $exist = !$exist;
                    $path = ROOT_ABSTRACT . $file;
                }
            }
            if ($exist) {
                throw new Exceptions(
                    "The {$name} abstraction cann't be found or path is different from default ROOT_ABSTRACT.", 404
                );
            }
        }
        $this->seting($name, $path);
        return $this;
    }

    /**
     * @param string $name
     * @param array $datas
     *
     * @return Abstration
     * @throws Exceptions
     */
    public function startClass(string $name, array $datas = []): Abstration
    {
        $class = $this->plugins[$name];
        $this->instance->correlations($datas);
        if ($this->instance->dependencies($this->instance->parameters($class), true)) {
            throw new Exceptions(
                "In the class {$name} to be claimed, the parameter does not exist or parameter is missing.", 404
            );
        }
        $this->identifyAndInstantiateClass($name, $datas);
        $this->name = $name;
        return $this;
    }

    /**
     * @param array $methods
     * @param array $datas
     *
     * @return mixed
     * @throws Exceptions
     */
    public function treatments(array $methods, array $datas)
    {
        $count = count($methods);
        foreach ($methods as $key => $method) {
            if ($key === ($count - 1)) {
                $result = $this->treatment($method, $datas[$method], 'data');
            } else {
                if (isset($datas[$method])) {
                    $this->needToInstantiateAbstractClassMethod($method, $datas[$method]);
                } else {
                    $this->needToInstantiateAbstractClassMethod($method);
                }
            }
        }
        return $result;
    }

    /**
     * @param string $method
     * @param array $datas
     * @param bool $returnActive
     *
     * @return mixed
     * @throws Exceptions
     */
    public function treatment(string $method, array $datas, string $returnType = '')
    {
        $this->instance->correlations($datas);
        if ($this->instance->dependencies($this->instance->parameters($this->plugins[$this->name], $method), true)) {
            throw new Exceptions("Some parameter passed does not exist in the {$method} method to be claimed.", 404);
        }
        if ($returnType === 'data') {
            return $this->methodChange($method, $datas, true);
        }
        $this->methodChange($method, $datas);
        return $this->object;
    }

}
