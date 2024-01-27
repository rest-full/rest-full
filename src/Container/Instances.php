<?php

declare(strict_types=1);

namespace Restfull\Container;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionObject;
use Restfull\Error\Exceptions;

/**
 *
 */
class Instances
{

    /**
     * @var array
     */
    private $dependencies = [];

    /**
     * @var ReflectionClass|ReflectionFunction|ReflectionObject|null
     */
    private $reflection;

    /**
     * @param $class
     * @return array
     * @throws Exceptions
     */
    public function attributes($class): array
    {
        try {
            if (is_string($class)) {
                $class = $this->renameClass($class);
            }
            $this->class($class);
            $attributes = [];
            foreach ($this->reflection->getAttributes() as $attribute) {
                $attributes[$attribute->name] = $attribute->value;
            }
            return $attributes;
        } catch (ReflectionException $e) {
            $log = new Logger('Erros');
            $log->pushHandler(new StreamHandler(ROOT . DS . 'log' . DS . 'error.log'));
            $msg = nl2br($class);
            if (isset($_SERVER['REQUEST_URI'])) {
                $msg .= ' and ' . $_SERVER['REQUEST_URI'];
            }
            $log->log('500', $msg);
            throw new Exceptions($e, '500');
        }
    }

    /**
     * @param string $class
     *
     * @return string
     */
    public function renameClass(string $class): string
    {
        if ($this->validate($class, 'file')) {
            $class = str_replace(PATH_NAMESPACE, ROOT_NAMESPACE[1], $class);
        }
        if (stripos($class, ROOT) !== false) {
            $class = substr($class, strlen(ROOT));
            if (stripos($class, '.php') !== false) {
                $class = substr($class, 0, -4);
            }
            if (strripos($class, DS) !== false) {
                $class = str_replace(DS, DS_REVERSE, $class);
            }
            $location = substr($class, 0, stripos($class, DS_REVERSE));
            if (in_array($location, ['vendor', 'src']) !== false) {
                $class = $location === 'vendor' ? str_replace(
                    substr(RESTFULL_FRAMEWORK, strlen(ROOT), -1),
                    ROOT_NAMSPACE[0],
                    $class
                ) : str_replace(substr(RESTFULL, strlen(ROOT), -1), ROOT_NAMESPACE[1], $class);
            }
        }
        return $class;
    }

    /**
     * @param string $class
     * @return bool
     * @throws Exceptions
     */
    public function validate(string $classPath, string $classInstance): bool
    {
        $dependencies = [$classInstance => $classPath];
        if ($classInstance == 'file') {
            $dependencies = array_merge(['instance' => $this], $dependencies);
        }
        $class = $this->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Filesystem' . DS_REVERSE . ucfirst($classInstance),
            $dependencies
        );
        return $class->exists();
    }

    /**
     * @param string $class
     * @param null $dependencies
     * @param bool $activeExceptions
     *
     * @return object|null
     * @throws Exceptions
     */
    public function resolveClass(string $class, $dependencies = null): ?object
    {
        try {
            if (isset($dependencies)) {
                $this->correlations($dependencies);
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
                return $this->reflection->newInstanceArgs($this->dependencies($constructor->getParameters()));
            }
            return null;
        } catch (ReflectionException $e) {
            $log = new Logger("Erros");
            $log->pushHandler(new StreamHandler(ROOT . DS . "log" . DS . "error.log"));
            $msg = nl2br($class);
            if (isset($_SERVER['REQUEST_URI'])) {
                $msg .= ' and ' . $_SERVER['REQUEST_URI'];
            }
            $log->log('500', $msg);
            throw new Exceptions($e, '500');
        }
    }

    /**
     * @param array $dependencies
     *
     * @return Instances
     */
    public function correlations(array $dependencies): Instances
    {
        $this->dependencies = $dependencies;
        return $this;
    }

    /**
     * @param mixed $class
     *
     * @return Instances
     * @throws ReflectionException
     */
    private function class($class): Instances
    {
        if (is_callable($class)) {
            $this->reflection = new ReflectionFunction($class);
            return $this;
        }
        if (is_object($class)) {
            $this->reflection = new ReflectionObject($class);
            return $this;
        }
        $this->reflection = new ReflectionClass($class);
        return $this;
    }

    /**
     * @param array $parameters
     *
     * @return mixed
     * @throws Exceptions
     */
    public function dependencies(array $parameters, bool $compare = false)
    {
        $dependencies = [];
        if (count($parameters) > 0) {
            $a = 0;
            $keys = array_keys($this->dependencies);
            foreach ($parameters as $parameter) {
                $dependecy = $parameter->getName();
                if ($parameter->getType() === 'object') {
                    if ($this->dependencies[$keys[$a]] instanceof $dependecy) {
                        $dependencies[] = $this->dependencies[$keys[$a]];
                    } else {
                        $dependencies[] = $this->resolveClass($name);
                    }
                } else {
                    if (isset($this->dependencies[$dependecy])) {
                        $dependencies[] = $this->dependencies[$dependecy];
                    } else {
                        if ($parameter->isDefaultValueAvailable()) {
                            $dependencies[] = $parameter->getDefaultValue();
                        } else {
                            if (!$compare) {
                                throw new Exceptions("Unable to resolve parameter {$dependecy}!");
                            }
                        }
                    }
                }
                if ($compare) {
                    $compared[] = isset($dependencies[$a]) ? 0 : 1;
                }
                $a++;
            }
            if ($compare) {
                $return = false;
                $count = count($compared);
                for ($a = 0; $a < $count; $a++) {
                    if ($compared[$a] === 1) {
                        $return = !$return;
                        break;
                    }
                }
                return $return;
            }
        }
        if ($compare) {
            return false;
        }

        return $dependencies;
    }

    /**
     * @param string $format
     * @param array $args
     * @param bool $convert
     *
     * @return string
     * @throws Exceptions
     */
    public function assemblyClassOrPath(string $format, array $args, bool $isClass = false): string
    {
        $format = $this->convertParamets($format);
        if (substr_count($format, '%s') != count($args)) {
            throw new Exceptions(
                'The ' . $format . ' format or ' . implode(', ', $args) . ' files are not equal.',
                404
            );
        }
        $part = '';
        $count = count($args);
        for ($a = 0; $a < $count; $a++) {
            if (stripos($format, '%s') !== 0) {
                $part = substr($format, 0, stripos($format, '%s'));
            }
            $format = $part . $args[$a] . substr($format, stripos($format, '%s') + 2);
            if (!empty($part)) {
                $part = '';
            }
        }
        if ($isClass) {
            if (stripos($format, DS . DS_REVERSE) !== false) {
                $format = str_replace(DS . DS_REVERSE, DS, $format);
            }
            $origin = PHP_OS === 'Linux' ? DS_REVERSE : DS;
            if (stripos($format, $origin) !== false) {
                $replace = PHP_OS === 'Linux' ? DS : DS_REVERSE;
                $format = str_replace($origin, $replace, $format);
            }
        }
        return $format;
    }

    /**
     * @param string $format
     *
     * @return string
     */
    private function convertParamets(string $format): string
    {
        if (stripos($format, '%s') === false) {
            $oldFormat = explode(' ', $format);
            foreach ($oldFormat as $key => $value) {
                if (stripos($value, '%') !== false) {
                    $value = stripos($value, '(') !== false ? '(%s)' : '%s';
                }
                $newFormat[] = $value;
            }
            return implode(' ', $newFormat);
        }
        return $format;
    }

    /**
     * @param string $classPath
     * @param string $classInstance
     *
     * @return array
     * @throws Exceptions
     */
    public function read(string $classPath, string $classInstance): array
    {
        $dependencies = [$classInstance => $classPath];
        if ($classInstance == 'file') {
            $dependencies = array_merge(['instance' => $this], $dependencies);
        }
        $class = $this->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Filesystem' . DS_REVERSE . ucfirst($classInstance),
            $dependencies
        );
        return $class->read();
    }

    /**
     * @param array $calleble
     * @param array $datas
     *
     * @return mixed
     */
    public function callebleFunctionActive(array $calleble, array $datas)
    {
        return call_user_func_array($calleble, $datas);
    }

    /**
     * @param object $class
     *
     * @return string
     * @throws Exceptions
     */
    public function name(object $class): string
    {
        try {
            if (is_string($class)) {
                $class = $this->renameClass($class);
            }
            $this->class($class);
            return $this->reflection->getName();
        } catch (ReflectionException $e) {
            $log = new Logger("Erros");
            $log->pushHandler(new StreamHandler(ROOT . DS . "log" . DS . "error.log"));
            $msg = nl2br($class);
            if (isset($_SERVER['REQUEST_URI'])) {
                $msg .= ' and ' . $_SERVER['REQUEST_URI'];
            }
            $log->log('500', $msg);
            throw new Exceptions($e, '500');
        }
    }

    /**
     * @param mixed $class
     *
     * @return array
     * @throws Exceptions
     */
    public function methods($class): array
    {
        try {
            if (is_string($class)) {
                $class = $this->renameClass($class);
            }
            $this->class($class);
            foreach ($this->reflection->getMethods() as $method) {
                $methods[] = $method->name;
            }
            return $methods;
        } catch (ReflectionException $e) {
            $log = new Logger("Erros");
            $log->pushHandler(new StreamHandler(ROOT . DS . "log" . DS . "error.log"));
            $msg = nl2br($class);
            if (isset($_SERVER['REQUEST_URI'])) {
                $msg .= ' and ' . $_SERVER['REQUEST_URI'];
            }
            $log->log('500', $msg);
            throw new Exceptions($e, '500');
        }
    }

    /**
     * @param callable $callback
     *
     * @return array
     * @throws Exceptions
     */
    public function anonymousFunction(callable $callback): array
    {
        try {
            $params = [];
            $parameters = $this->parameters($callback);
            if (!is_null($parameters)) {
                foreach ($parameters as $value) {
                    $params[] = $value->name;
                }
            }
            return $params;
        } catch (ReflectionException $e) {
            $log = new Logger("Erros");
            $log->pushHandler(new StreamHandler(ROOT . DS . "log" . DS . "error.log"));
            $msg = nl2br($class);
            if (isset($_SERVER['REQUEST_URI'])) {
                $msg .= ' and ' . $_SERVER['REQUEST_URI'];
            }
            $log->log('500', $msg);
            throw new Exceptions($e, '500');
        }
    }

    /**
     * @param mixed $class
     * @param string $method
     *
     * @return array
     * @throws Exceptions
     */
    public function parameters($class, string $method = ''): array
    {
        try {
            if (is_string($class)) {
                $class = $this->renameClass($class);
            }
            $this->class($class);
            if (is_callable($class)) {
                return $this->reflection->getParameters();
            }
            if (empty($method)) {
                $newMethod = $this->reflection->getConstructor();
                if (is_null($newMethod)) {
                    return [];
                }
                return $newMethod->getParameters();
            }
            return $this->reflection->getMethod($method)->getParameters();
        } catch (ReflectionException $e) {
            $log = new Logger("Erros");
            $log->pushHandler(new StreamHandler(ROOT . DS . "log" . DS . "error.log"));
            $msg = nl2br($class);
            if (isset($_SERVER['REQUEST_URI'])) {
                $msg .= ' and ' . $_SERVER['REQUEST_URI'];
            }
            $log->log('500', $msg);
            throw new Exceptions($e, '500');
        }
    }

    /**
     * @param string $class
     *
     * @return string
     */
    public function locateTheFileWhetherItIsInTheAppOrInTheFramework(string $class, string $control = ''): string
    {
        $classPath = $class;
        if (empty($control)) {
            $location = substr($class, 0, stripos($class, DS_REVERSE));
            $classPath = $location == ROOT_NAMESPACE[1] ? str_replace(
                ROOT_NAMESPACE[1],
                RESTFULL,
                $class
            ) : str_replace(
                ROOT_NAMESPACE[0],
                RESTFULL_FRAMEWORK,
                $class
            );
            $classPath = str_replace(DS . DS, DS, str_replace(DS_REVERSE, DS, $classPath)) . '.php';
        }
        if (!$this->validate($classPath, 'file')) {
            if ($control == 'error') {
                $class = str_replace(DS . DS, DS, str_replace(DS_REVERSE, DS, $classPath));
            } else {
                $class = str_replace(ROOT_NAMESPACE[1], ROOT_NAMESPACE[0], $class);
                $class = $this->locateTheFileWhetherItIsInTheAppOrInTheFramework($class);
            }
        }
        return $class;
    }

    /**
     * @param string $component
     * @param array $partOfTheClassPath
     *
     * @return bool
     * @throws Exceptions
     */
    public function locateTheExternalComponent(string $component, array $partOfTheClassPath): bool
    {
        $component = str_replace($partOfTheClassPath[0], $partOfTheClassPath[1], $component);
        if (stripos($component, DS_REVERSE) !== false) {
            $component = str_replace(DS_REVERSE, DS, $component);
        }
        if (!$this->validate(str_replace(PATH_NAMESPACE . DS, 'example', $component), 'folder')) {
            return false;
        }
        return true;
    }

    /**
     * @param string $component
     * @param array $partClassPath
     * @param string $otherPartClassPath
     *
     * @return array
     * @throws Exceptions
     */
    public function returnMessagesTheExternalComponent(
        string $component,
        array $partOfTheClassPath,
        string $otherPartClassPath
    ): array {
        $component = substr($component, stripos($component, 'vendor') + strlen('vendor') + 1);
        $partClassPath = explode(DS_REVERSE, $partOfTheClassPath[1]);
        $count = count($partClassPath);
        for ($number = 0; $number < $count; $number++) {
            if (stripos($partClassPath[$number], '-') !== false) {
                $partClassPath[$number] = str_replace('-', '', $partClassPath[$number]);
            }
            $partClassPath[$number] = ucfirst($partClassPath[$number]);
        }
        $partOfTheClassPath[1] = implode(DS_REVERSE, $partClassPath);
        $component = str_replace($partOfTheClassPath[0], $partOfTheClassPath[1], $component);
        $classpath = $this->resolveClass(str_replace(DS . PATH_NAMESPACE . DS, '', $component) . $otherPartClassPath);
        var_dump($classpath->messages());
        die();
        return $classpath->messages();
    }
}
