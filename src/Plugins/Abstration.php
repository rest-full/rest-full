<?php

namespace Restfull\Plugins;

use Restfull\Error\Exceptions;
use Restfull\Filesystem\Folder;

/**
 * Class Abstration
 * @package Restfull\Plugins
 */
class Abstration extends Plugin
{

    /**
     * @param string $name
     * @param string $path
     * @return Abstration
     * @throws Exceptions
     */
    public function setClass(string $name, string $path = ''): Abstration
    {
        if (empty($path)) {
            $foldersAndFiles = new Folder(FRAMEWORK);
            $insert = false;
            foreach ($foldersAndFiles->read()['diretory'] as $folder) {
                if (in_array($name . 'php', $foldersAndFiles->read($folder)['files']) !== false) {
                    $path = str_replace('-', '', ROOT_NAMESPACE) . DS_REVERSE . $folder . DS_REVERSE . $name;
                    $insert = true;
                    break;
                }
            }
            if (!$insert) {
                $foldersAndFiles = new Folder(ROOT_ABSTRACT);
                foreach ($foldersAndFiles->read($folder)['files'] as $file) {
                    if ($name == $file) {
                        $path = ROOT_ABSTRACT . $file;
                    }
                }
            }
            if (empty($path)) {
                throw new Exceptions("The {$name} abstraction cann't be found.", 404);
            }
        }
        $this->seting($name, $path);
        return $this;
    }

    /**
     * @param string $name
     * @param array $datas
     * @return Abstration
     * @throws Exceptions
     */
    public function startClass(string $name, array $datas = []): Abstration
    {
        if (count($datas) != count($this->instance->getParameters($this->object))) {
            throw new Exceptions("The passed parameters don't have the exact number of parameters allowed.", 404);
        }
        $this->identifyAndInstantiateClass($name, $datas);
        return $this;
    }

    /**
     * @param string $method
     * @param array $datas
     * @param bool $returnActive
     * @return Abstration
     * @throws Exceptions
     */
    public function treatment(string $method, array $datas, bool $returnActive = false)
    {
        if (count($datas) != count($this->instance->getParameters($this->object, $method))) {
            throw new Exceptions("The passed parameters don't have the exact number of parameters allowed.", 404);
        }
        if ($returnActive) {
            return $this->methodChange($method, $datas, true);
        }
        $this->methodChange($method, $datas);
        return $this;
    }
}