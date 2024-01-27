<?php

declare(strict_types=1);

namespace Restfull\Executing;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;
use Restfull\Http\Request;

/**
 *
 */
class Cache
{

    /**
     * @var string
     */
    private $hash = '';

    /**
     * @var int
     */
    private $expirationTime = 0;

    /**
     * @var string
     */
    private $folder = '';

    /**
     * @var Instances
     */
    private $instance;

    /**
     * @param Request $request
     */
    public function __construct(Instances $instance, Request $request)
    {
        $this->instance = $instance;
        if (empty($this->expirationTime)) {
            $this->expirationTime = strtotime(date('Y-m-d H:i:s') . $request->bootstrap('cache')['time']);
        }
        $route = $request->route;
        $this->folder = substr(substr($route, 1), 0, stripos(substr($route, 1), DS));
        $this->hash = str_replace(DS, '_', substr($route, stripos($route, $this->folder) + strlen($this->folder) + 1));
        return $this;
    }

    /**
     * @param string $content
     *
     * @return Cache
     * @throws Exceptions
     */
    public function create(string $content): Cache
    {
        $classpath = ROOT . DS_REVERSE . 'cache' . DS_REVERSE . $this->folder . DS_REVERSE . $this->hash;
        $file = $this->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Filesystem' . DS_REVERSE . 'File',
            ['file' => $classPath]
        );
        if ($this->instance->validate($classPath, 'file')) {
            $file->delete();
        }
        $file->write(serialize($content));
        return $this;
    }

    /**
     * @return string
     * @throws Exceptions
     */
    public function read(): string
    {
        $classpath = ROOT . DS_REVERSE . 'cache' . DS_REVERSE . $this->folder . DS_REVERSE . $this->hash;
        $file = $this->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Filesystem' . DS_REVERSE . 'File',
            ['file' => $classPath]
        );
        return unserialize($file->read()['content']);
    }

    /**
     * @return int
     */
    public function expirationTime(): int
    {
        return $this->expirationTime;
    }

    /**
     * @return string
     */
    public function hash(): string
    {
        return $this->hash;
    }

}
