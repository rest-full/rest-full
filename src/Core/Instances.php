<?php

namespace Restfull\Core;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use Restfull\Error\Exceptions;

/**
 * Class InstanceClass
 * @package Restfull\Core
 */
class Instances
{

    /**
     * @var array
     */
    private $dependecies_inject = [];

    /**
     * @var ReflectionClass|ReflectionFunction
     */
    private $reflection;

    /**
     * @param string $class
     * @param null $dependecies_inject
     * @param bool $activeExceptions
     * @return object|null
     * @throws Exceptions
     */
    public function resolveClass(string $class, $dependecies_inject = null, bool $activeExceptions = true): ?object
    {
        try {
            if (strripos($class, DS) !== false) {
                $class = substr($class, strripos($class, DS) + 1);
            }
            if (isset($dependecies_inject)) {
                $this->dependecies_inject = $dependecies_inject;
            }
            if (is_string($class)) {
                $this->class($class);
            }
            if (is_object($this->reflection)) {
                if (!$this->reflection->isInstantiable()) {
                    throw new Exceptions("{$this->reflection->name} is not instanciable");
                }
                $constructor = $this->reflection->getConstructor();
                if (is_null($constructor)) {
                    return new $this->reflection->name;
                }
                return $this->reflection->newInstanceArgs($this->getDependencies($constructor->getParameters()));
            }
            return null;
        } catch (ReflectionException $e) {
            $log = new Logger("Erros");
            $log->pushHandler(new StreamHandler(ROOT . DS . "log" . DS . "error.log"));
            $log->log('500', nl2br($class) . ' and ' . $_SERVER['REQUEST_URI']);
            if ($activeExceptions) {
                throw new Exceptions($e, '500');
            } else {
                echo $e->getMessage();
                exit;
            }
        }
    }

    /**
     * @param string $class
     * @return Instances
     * @throws ReflectionException
     */
    private function class(string $class): Instances
    {
        if (is_callable($class)) {
            $this->reflection = new ReflectionFunction($class);
            return $this;
        }
        $this->reflection = new ReflectionClass($class);
        return $this;
    }

    /**
     * @param array $parameters
     * @return array
     * @throws Exceptions
     */
    private function getDependencies(array $parameters): array
    {
        $dependecies = [];
        if (count($parameters) > 0) {
            $a = 0;
            $keys = array_keys($this->dependecies_inject);
            foreach ($parameters as $parameter) {
                $dependecy = $parameter->getClass();
                if (isset($dependecy)) {
                    $dependecy = $dependecy->name;
                    if ($this->dependecies_inject[$keys[$a]] instanceof $dependecy) {
                        $dependecies[] = $this->dependecies_inject[$keys[$a]];
                    } else {
                        $dependecies[] = $this->resolveClass($parameter->getClass());
                    }
                } else {
                    if (isset($this->dependecies_inject[$parameter->name])) {
                        $dependecies[] = $this->dependecies_inject[$parameter->name];
                    } else {
                        if ($parameter->isDefaultValueAvailable()) {
                            $dependecies[] = $parameter->getDefaultValue();
                        } else {
                            throw new Exceptions("Cannot resolve unknow!");
                        }
                    }
                }
                $a++;
            }
        }
        return $dependecies;
    }

    /**
     * @param array $calleble
     * @param array $datas
     * @return false|mixed
     */
    public function callebleFunctionActive(array $calleble, array $datas)
    {
        return call_user_func_array($calleble, $datas);
    }

    /**
     * @param string $class
     * @param string $method
     * @return array
     * @throws Exceptions
     */
    public function getParameters(string $class, string $method = ''): array
    {
        try {
            $this->class($class);
            if (empty($method)) {
                $method = 'getConstructor';
            }
            return ($this->reflection->{$method}())->getParameters();
        } catch (ReflectionException $e) {
            throw new Exceptions($e, '500');
        }
    }

    /**
     * @param string $class
     * @return array
     * @throws Exceptions
     */
    public function getMethods(string $class): array
    {
        try {
            $this->class($class);
            foreach ($this->reflection->getMethods() as $method) {
                $methods[] = $method->name;
            }
            return $methods;
        } catch (ReflectionException $e) {
            throw new Exceptions($e, '500');
        }
    }

    /**
     * @param callable $callback
     * @return array
     * @throws Exceptions
     */
    public function getFunction(callable $callback): array
    {
        try {
            $params = [];
            $this->class($callback);
            $parameters = $this->reflection->getParameters();
            if (!is_null($parameters)) {
                foreach ($parameters as $value) {
                    $params[] = $value->name;
                }
            }
            return $params;
        } catch (ReflectionException $e) {
            throw new Exceptions($e, '500');
        }
    }

    /**
     * @param string $class
     * @return string
     */
    public function extension(string $class): string
    {
        if (file_exists(str_replace(substr(ROOT_APP, -4, -1), 'src', str_replace(DS_REVERSE, DS, $class)) . '.php')) {
            $class = ucfirst(substr($class, strlen(ROOT_APP) - strlen(strtolower(APP) . DS)));
        } elseif (stripos($class, 'Model') !== false) {
            if (stripos($class, 'Entity') !== false) {
                $class = explode(DS, $this->path($class));
                $newClass = explode(DS_REVERSE, $class[count($class) - 1]);
                $newClass[count($newClass) - 1] = $newClass[count($newClass) - 2];
                $class[count($class) - 1] = implode(DS, $newClass);
                $class = implode(DS, $class);
                unset($newClass);
                $class = str_replace(MVC[2]['app'], MVC[2]['restfull'], $class);
            }
            if (file_exists($class . '.php')) {
                $class = substr($class, stripos(RESTFULL, "vendor") + strlen("vendor" . DS));
                $class = str_replace("rest-full/src" . DS, "Restfull" . DS_REVERSE, $class);
                $class = str_replace(DS, DS_REVERSE, $class);
            }
        } else {
            $class = RESTFULL . substr($class, strlen(substr(ROOT_APP, -4, -1)));
            $class = str_replace(RESTFULL, ROOT_NAMESPACE, $class);
        }

        return $class;
    }

    /**
     * @param string $format
     * @param array $args
     * @param bool $convert
     * @return string
     * @throws Exceptions
     */
    public function namespaceClass(string $format, array $args, bool $convert = false): string
    {
        if (!$convert) {
            if (stripos($format, DS) !== false) {
                $replace = str_replace(DS, DS_REVERSE, $format);
            }
            $replace = explode(DS_REVERSE, (isset($replace) ? $replace : $format));
            $count = 0;
            for ($a = 0; $a < count($replace); $a++) {
                if (stripos($replace[$a], '%s') !== false) {
                    $count++;
                }
            }
            if ($count != count($args)) {
                throw new Exceptions(
                        'The ' . $format . ' format or ' . implode(', ', $args) . ' files are not equal.', 404
                );
            }
        }
        return vsprintf($format, $args);
    }

}
