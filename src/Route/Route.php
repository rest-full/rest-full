<?php

namespace Restfull\Route;

use Restfull\Core\Instances;
use Restfull\Error\Exceptions;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 * Class Route
 * @package Restfull\Route
 */
class Route
{

    /**
     * @var int
     */
    public $countPrefix = 0;
    /**
     * @var array
     */
    private $uri = [];
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Response
     */
    private $response;
    /**
     * @var array
     */
    private $data = [];
    /**
     * @var array
     */
    private $name = [];
    /**
     * @var string
     */
    private $prefix = '';
    /**
     * @var bool
     */
    private $active = false;

    /**
     * Route constructor.
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        include_once ROOT . DS . 'config' . DS . 'routes.php';
        $this->notFound('Error');
        return $this;
    }

    /**
     * @param string $control
     * @return Route
     */
    public function notFound(string $control): Route
    {
        $route = DS . strtolower($control) . DS . 'problems';
        $this->add('GET', $route, str_replace(DS, '.', substr($route, 1)))->name(
                str_replace(DS, '.', substr($route, 1))
        );
        return $this;
    }

    /**
     * @param string $handler
     * @param bool $use
     * @return Route
     */
    public function name(string $handler, bool $use = false)
    {
        $handler = strtolower($handler);
        $route = str_replace('.', DS, $handler);
        if (!empty($this->prefix)) {
            $route = DS . $this->prefix . DS . $route;
        }
        if ($this->active) {
            $this->name[$route] = str_replace('.', '+', $handler);
        }
        return $this;
    }

    /**
     * @param string $method
     * @param string $route
     * @param string $handler
     * @param null $callback
     * @return Route
     * @throws Exceptions
     */
    protected function add(string $method, string $route, string $handler, $callback = null): Route
    {
        if (!empty($this->prefix)) {
            $route = DS . $this->prefix . $route;
        }
        $url = explode(DS, substr($route, 1));
        if (isset($callback)) {
            $this->request->callback[$route] = [$callback, (new Instances())->getFunction($callback)];
            return $this;
        }
        $data = explode(".", $handler);
        for ($a = 0; $a < count($data); $a++) {
            if ($a == 0) {
                $dispatcher['controller'] = ucfirst($data[$a]);
            } elseif ($a == 1) {
                $dispatcher['action'] = $data[$a];
            }
        }
        $this->uri[$method][$url[0]][$route] = $dispatcher;
        return $this;
    }

    /**
     * @return Route
     */
    public function activeName(): Route
    {
        $this->active = true;
        return $this;
    }

    /**
     * @param string $route
     * @param string $handler
     * @param null $callable
     * @return Route
     * @throws Exceptions
     */
    public function get(string $route, string $handler = '', $callable = null): Route
    {
        if (empty($handler)) {
            $handler = str_replace(DS, '.', substr($route, 1));
        }
        $this->add('GET', $route, $handler, $callable)->name($handler);
        return $this;
    }

    /**
     * @param string $route
     * @param string $handler
     * @param null $callable
     * @return Route
     * @throws Exceptions
     */
    public function post(string $route, string $handler = '', $callable = null): Route
    {
        if (empty($handler)) {
            $handler = str_replace(DS, '.', substr($route, 1));
        }
        $this->add('POST', $route, $handler, $callable)->name($handler);
        return $this;
    }

    /**
     * @param string $route
     * @param string $handler
     * @param null $callable
     * @return Route
     * @throws Exceptions
     */
    public function put(string $route, string $handler = '', $callable = null): Route
    {
        if (empty($handler)) {
            $handler = str_replace(DS, '.', substr($route, 1));
        }
        $this->add('PUT', $route, $handler, $callable)->name($handler);
        return $this;
    }

    /**
     * @param string $route
     * @param string $handler
     * @param null $callable
     * @return Route
     * @throws Exceptions
     */
    public function delete(string $route, string $handler = '', $callable = null): Route
    {
        if (empty($handler)) {
            $handler = str_replace(DS, '.', substr($route, 1));
        }
        $this->add('DELETE', $route, $handler, $callable)->name($handler);
        return $this;
    }

    /**
     * @param string $route
     * @param string $handler
     * @param null $callable
     * @return Route
     * @throws Exceptions
     */
    public function path(string $route, string $handler = '', $callable = null): Route
    {
        if (empty($handler)) {
            $handler = str_replace(DS, '.', substr($route, 1));
        }
        $this->add('PATH', $route, $handler, $callable)->name($handler);
        return $this;
    }

    /**
     * @param string $name
     * @return string
     * @throws Exceptions
     */
    public function nameRoute(string $name): string
    {
        $route = '';
        foreach ($this->name as $key => $value) {
            if ($value == $name) {
                $route = $key;
            }
        }
        if (empty($route)) {
            throw new Exceptions('This ' . $name . ' route name was not found, as it does not exist.', 404);
        }
        return $route;
    }

    /**
     * @param string $method
     * @return array
     */
    public function uri(string $method): array
    {
        return $this->uri[$method];
    }

    /**
     * @param string $http
     * @return mixed
     */
    public function http(string $http)
    {
        return $this->$http;
    }

    /**
     * @param string $control
     * @param array $actives
     * @return Route
     */
    public function resource(string $control, array $actives): Route
    {
        list($name, $paramsList) = $actives;
        $resources = new Resources();
        $resources->resources($control, $name, $paramsList);
        if ($name) {
            $this->name = array_merge($this->name, $resources->getName());
        }
        $this->uri = array_merge($this->uri, $resources->getUri());
        return $this;
    }

    /**
     * @param string $prefix
     * @param $callable
     * @return Route
     */
    public function prefix(string $prefix, $callable): Route
    {
        if (!empty($prefix)) {
            $this->countPrefix++;
        }
        $this->prefix = $prefix;
        $callable = call_user_func($callable, $this);
        return $this;
    }

}
