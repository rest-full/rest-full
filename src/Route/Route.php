<?php

declare(strict_types=1);

namespace Restfull\Route;

use Restfull\Container\Instances;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 *
 */
class Route
{

    /**
     * @var array
     */
    public $name = [];

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
     * @var Instances
     */
    private $instance;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var string
     */
    private $prefix = '';

    /**
     * @var bool
     */
    private $active = false;

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Instances $instance, Request $request, Response $response)
    {
        $this->instance = $instance;
        $this->request = $request;
        $this->response = $response;
        include_once ROOT . DS . 'config' . DS . 'routes.php';
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
     *
     * @return Route
     */
    public function get(string $route, string $handler = '', $callable = null): Route
    {
        if (stripos($route, '_') !== false || stripos($route, '-') !== false) {
            $route = str_replace(['-', '_'], ' ', $route);
        }
        $path = explode(DS, $route);
        $params = [];
        if (empty($handler)) {
            $handler = ucwords(array_shift($path));
            $count = count($path);
            for ($a = 1; $a < $count; $a++) {
                $handler .= $a === 1 ? '.' . $path[$a] : DS . $path[$a];
            }
            $handler = str_replace(DS, '.', $handler);
        }
        $pathRoute = $handler != $route ? $route : str_replace('.', DS, $handler);
        $count = count($path);
        if ($count > 2) {
            for ($a = 2; $a < $count; $a++) {
                $params[] = str_replace(['{', '}'], '', $path[$a]);
            }
        }
        if (stripos($route, ' ') !== false) {
            $route = str_replace(' ', '', $route);
        }
        $this->name($handler, $params, $pathRoute)->add('GET', $route, $handler, $callable);
        return $this;
    }

    /**
     * @param string $method
     * @param string $route
     * @param string $handler
     * @param null $callback
     *
     * @return Route
     */
    protected function add(string $method, string $route, string $handler, $callback = null): Route
    {
        if (isset($callback)) {
            $this->request->callback[$route] = [$callback, $this->instance->anonymousFunction($callback)];
            return $this;
        }
        $url = explode('.', $handler);
        $count = count($url);
        for ($a = 0; $a < $count; $a++) {
            if ($a === 0) {
                $dispatcher['controller'] = ucfirst($url[$a]);
            } elseif ($a === 1) {
                $dispatcher['action'] = $url[$a];
            }
        }
        $this->uri[$method][substr(
            $route,
            0,
            stripos($route, DS)
        )][$route][(empty($this->prefix) ? 'app' : $this->prefix)] = $dispatcher;
        return $this;
    }

    /**
     * @param string $handler
     * @param array $params
     *
     * @return Route
     */
    public function name(string $handler, array $params, string $route)
    {
        $this->name[strtolower(
            str_replace('.', ($this->active ? '+' : DS), $handler)
        )][(empty($this->prefix) ? 'app' : $this->prefix)] = ['route' => $route, 'params' => $params];
        return $this;
    }

    /**
     * @param string $route
     * @param string $handler
     * @param null $callable
     *
     * @return Route
     */
    public function post(string $route, string $handler = '', $callable = null): Route
    {
        if (stripos($route, '_') !== false || stripos($route, '-') !== false) {
            $route = str_replace(['-', '_'], ' ', $route);
        }
        $path = explode(DS, $route);
        $params = [];
        if (empty($handler)) {
            $handler = ucwords(array_shift($path));
            $count = count($path);
            for ($a = 1; $a < $count; $a++) {
                $handler .= $a === 1 ? '.' . $path[$a] : DS . $path[$a];
            }
            $handler = str_replace(DS, '.', $handler);
        }
        $pathRoute = $handler != $route ? $route : str_replace('.', DS, $handler);
        $count = count($path);
        if ($count > 2) {
            for ($a = 2; $a < $count; $a++) {
                $params[] = str_replace(['{', '}'], '', $path[$a]);
            }
        }
        if (stripos($route, ' ') !== false) {
            $route = str_replace(' ', '', $route);
        }
        $this->name($handler, $params, $pathRoute)->add('POST', $route, $handler, $callable);
        return $this;
    }

    /**
     * @param string $route
     * @param string $handler
     * @param null $callable
     *
     * @return Route
     */
    public function put(string $route, string $handler = '', $callable = null): Route
    {
        if (stripos($route, '_') !== false || stripos($route, '-') !== false) {
            $route = str_replace(['-', '_'], ' ', $route);
        }
        $path = explode(DS, $route);
        $params = [];
        if (empty($handler)) {
            $handler = ucwords(array_shift($path));
            $count = count($path);
            for ($a = 1; $a < $count; $a++) {
                $handler .= $a === 1 ? '.' . $path[$a] : DS . $path[$a];
            }
            $handler = str_replace(DS, '.', $handler);
        }
        $pathRoute = $handler != $route ? $route : str_replace('.', DS, $handler);
        $count = count($path);
        if ($count > 2) {
            for ($a = 2; $a < $count; $a++) {
                $params[] = str_replace(['{', '}'], '', $path[$a]);
            }
        }
        if (stripos($route, ' ') !== false) {
            $route = str_replace(' ', '', $route);
        }
        $this->name($handler, $params, $pathRoute)->add('PUT', $route, $handler, $callable);
        return $this;
    }

    /**
     * @param string $route
     * @param string $handler
     * @param null $callable
     *
     * @return Route
     */
    public function delete(string $route, string $handler = '', $callable = null): Route
    {
        if (stripos($route, '_') !== false || stripos($route, '-') !== false) {
            $route = str_replace(['-', '_'], ' ', $route);
        }
        $path = explode(DS, $route);
        $params = [];
        if (empty($handler)) {
            $handler = ucwords(array_shift($path));
            $count = count($path);
            for ($a = 1; $a < $count; $a++) {
                $handler .= $a === 1 ? '.' . $path[$a] : DS . $path[$a];
            }
            $handler = str_replace(DS, '.', $handler);
        }
        $pathRoute = $handler != $route ? $route : str_replace('.', DS, $handler);
        $count = count($path);
        if ($count > 2) {
            for ($a = 2; $a < $count; $a++) {
                $params[] = str_replace(['{', '}'], '', $path[$a]);
            }
        }
        if (stripos($route, ' ') !== false) {
            $route = str_replace(' ', '', $route);
        }
        $this->name($handler, $params, $pathRoute)->add('DELETE', $route, $handler, $callable);
        return $this;
    }

    /**
     * @param string $route
     * @param string $handler
     * @param null $callable
     *
     * @return Route
     */
    public function path(string $route, string $handler = '', $callable = null): Route
    {
        if (stripos($route, '_') !== false || stripos($route, '-') !== false) {
            $route = str_replace(['-', '_'], ' ', $route);
        }
        $path = explode(DS, $route);
        $params = [];
        if (empty($handler)) {
            $handler = ucwords(array_shift($path));
            $count = count($path);
            for ($a = 1; $a < $count; $a++) {
                $handler .= $a === 1 ? '.' . $path[$a] : DS . $path[$a];
            }
            $handler = str_replace(DS, '.', $handler);
        }
        $pathRoute = $handler != $route ? $route : str_replace('.', DS, $handler);
        $count = count($path);
        if ($count > 2) {
            for ($a = 2; $a < $count; $a++) {
                $params[] = str_replace(['{', '}'], '', $path[$a]);
            }
        }
        if (stripos($route, ' ') !== false) {
            $route = str_replace(' ', '', $route);
        }
        $this->name($handler, $params, $pathRoute)->add('PATH', $route, $handler, $callable);
        return $this;
    }

    /**
     * @param string $route
     * @param string $handler
     * @param null $callable
     *
     * @return Route
     */
    public function block(string $route, string $handler = '', $callable = null): Route
    {
        if (stripos($route, '_') !== false || stripos($route, '-') !== false) {
            $route = str_replace(['-', '_'], ' ', $route);
        }
        $path = explode(DS, $route);
        $params = [];
        if (empty($handler)) {
            $handler = ucwords(array_shift($path));
            $count = count($path);
            for ($a = 1; $a < $count; $a++) {
                $handler .= $a === 1 ? '.' . $path[$a] : DS . $path[$a];
            }
            $handler = str_replace(DS, '.', $handler);
        }
        $pathRoute = $handler != $route ? $route : str_replace('.', DS, $handler);
        $count = count($path);
        if ($count > 2) {
            for ($a = 2; $a < $count; $a++) {
                $params[] = str_replace(['{', '}'], '', $path[$a]);
            }
        }
        if (stripos($route, ' ') !== false) {
            $route = str_replace(' ', '', $route);
        }
        $this->name($handler, $params, $pathRoute)->add('BLOCK', $route, $handler, $callable);
        return $this;
    }

    /**
     * @return array
     */
    public function names(): array
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param string $method
     *
     * @return array
     */
    public function uri(string $method): array
    {
        return $this->uri[$method];
    }

    /**
     * @param string $http
     *
     * @return mixed
     */
    public function http(string $http)
    {
        return $this->{$http};
    }

    /**
     * @param string $control
     * @param array $actives
     *
     * @return Route
     */
    public function resource(string $control, array $actives): Route
    {
        list($name, $paramsList) = $actives;
        $resources = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Route' . DS_REVERSE . 'Resources',
            ['instance' => $this->instance]
        );
        $resources->resources($control, $name, $paramsList);
        if ($name) {
            $this->name = array_merge($this->name, $resources->getName());
        }
        $this->uri = array_merge($this->uri, $resources->getUri());
        return $this;
    }

    /**
     * @param string $prefix
     *
     * @return Route
     */
    public function prefix(string $prefix): Route
    {
        $this->prefix = $prefix;
        return $this;
    }

}
