<?php

declare(strict_types=1);

namespace Restfull\Route\Middleware;

use Restfull\Error\Exceptions;
use Restfull\Http\Middleware\Middleware;
use Restfull\Http\Request;
use Restfull\Http\Response;
use Restfull\Route\Route;

/**
 *
 */
class RouteMiddleware extends Middleware
{

    /**
     * @var array
     */
    private $routes = [];

    /**
     * @var Route
     */
    private $routeClass;

    /**
     * @var array
     */
    private $callable = [];

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response, object $instance)
    {
        $this->routeClass = $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Route' . DS_REVERSE . 'Route',
            ['instance' => $instance, 'request' => $request, 'response' => $response]
        );
        $this->replaceRoute($request, $response);
        parent::__construct($request, $response, $instance);
        return $this;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return RouteMiddleware
     * @throws Exceptions
     */
    private function replaceRoute(Request $request, Response $response): RouteMiddleware
    {
        $route = $request->route;
        $newRoute = explode($this->routeClass->isActive() ? '+' : DS, $request->newRoute());
        $identifyRoute = [];
        if (count($newRoute) > 1) {
            $route = explode(DS, $route);
            $count = 0;
            if ($route[0] === $newRoute[0]) {
                $prefix = 'app';
            } else {
                $prefix = $route[0];
                $count++;
            }
            $identifyRoute = $response->identifyRouteByName($newRoute, $prefix, $identifyRoute);
            if (count($identifyRoute) > 0) {
                for ($number = $count; $number < 2; $number++) {
                    $route[$number] = $newRoute[$number - 1];
                }
            }
            $request->route = implode(DS, $route);
        }
        return $this;
    }

    /**
     * @param object $next
     *
     * @return object
     * @throws Exceptions
     */
    public function __invoke(object $next): object
    {
        if ($this->compare()) {
            if (count($this->callable) > 0) {
                $this->routeClass->http('request')->callable = $this->callable;
                return $this;
            }
            $this->routeClass->http('response')->route($this->routeClass);
            return $next();
        } else {
            throw new Exceptions("The {$this->routeClass->http('request')->route} route was not found.", 404);
        }
    }

    /**
     * @return bool
     * @throws Exceptions
     */
    public function compare(): bool
    {
        $found = false;
        $uri = explode(DS, $this->routeClass->http('request')->route);
        $prefix = $this->prefix($uri);
        if (in_array($prefix, $uri) !== false) {
            $count = count($uri);
            for ($a = 0; $a < $count; $a++) {
                if (isset($uri[($a + 1)])) {
                    $uri[$a] = strtolower($uri[($a + 1)]);
                } else {
                    unset($uri[$a]);
                }
            }
        }
        $this->routesOfAController(implode(DS, $uri));
        if (count($this->routes) > 0) {
            foreach ($this->routes as $route => $datas) {
                $route = explode(DS, $route);
                if (count($uri) === count($route)) {
                    if (isset($datas[$prefix])) {
                        $datas = $datas[$prefix];
                        $this->check($uri, $route, $datas);
                        if (!empty($this->routeClass->http('request')->controller)) {
                            $found = true;
                            break;
                        }
                    }
                }
            }
            if ($found) {
                return true;
            }
            return false;
        }
        $routes = $this->routeClass->uri('BLOCK')[$uri[0]];
        $uri = implode(DS, $uri);
        if (array_key_exists($uri, $routes) !== false) {
            $this->routeClass->http('request')->blockedRoute = true;
            $datas = $routes[$uri][$prefix];
            $this->routeClass->http('request')->controller = $datas['controller'];
            $this->routeClass->http('request')->action = $datas['action'];
            return true;
        }
        return false;
    }

    /**
     * @param array $uri
     *
     * @return string
     */
    public function prefix(array $uri): string
    {
        $prefixs = [];
        foreach ($this->routeClass->name as $datas) {
            $keys = array_keys($datas);
            $count = count($keys);
            for ($a = 0; $a < $count; $a++) {
                if (in_array($keys[$a], $prefixs) === false) {
                    $prefixs[] = $keys[$a];
                }
            }
        }
        if (in_array($uri[0], $prefixs) !== false) {
            $this->routeClass->http('request')->prefix = $uri[0];
        }
        if (!empty($this->routeClass->http('request')->prefix)) {
            return $this->routeClass->http('request')->prefix;
        }
        return 'app';
    }

    /**
     * @param string $uri
     *
     * @return RouteMiddleware
     * @throws Exceptions
     */
    private function routesOfAController(string $uri): RouteMiddleware
    {
        $found = false;
        if (!empty($this->request->base)) {
            if (substr($uri, 0, stripos($uri, DS)) === substr($this->request->base, 1)) {
                $uri = substr($uri, strlen($this->request->base));
            }
        }
        $routes = $this->routeClass->uri(
            $this->routeClass->http('request')->data('method')
        );
        foreach ($routes as $key => $values) {
            if (substr($uri, 0, stripos($uri, DS)) != $key) {
                continue;
            } else {
                $len = 1;
                $len += strlen($key);
                $len += stripos($uri, $key);
                $keys = array_keys($values);
                $count = count($keys);
                for ($a = 0; $a < $count; $a++) {
                    if (stripos($keys[$a], explode(DS, substr($uri, $len))[0]) !== false) {
                        $found = true;
                        break;
                    }
                }
                if ($found) {
                    $this->routes = $values;
                    break;
                }
            }
        }
        return $this;
    }

    /**
     * @param array $uri
     * @param array $route
     * @param       $datas
     *
     * @return RouteMiddleware
     * @throws Exceptions
     */
    private function check(array $uri, array $route, $datas): RouteMiddleware
    {
        $count = 0;
        foreach ($route as $key => $value) {
            $computo = count($uri);
            for ($a = 0; $a < $computo; $a++) {
                if ($value === $uri[$a]) {
                    $count++;
                    unset($route[$key]);
                    break;
                }
            }
        }
        if ($count === 2) {
            if (count($route) > 0) {
                foreach (array_keys($route) as $key) {
                    $this->routeClass->http('request')->params[substr($route[$key], 1, -1)] = $uri[$key];
                }
            }
            $this->exchange($datas);
        }
        return $this;
    }

    /**
     * @param $data
     *
     * @return RouteMiddleware
     * @throws Exceptions
     */
    private function exchange($data): RouteMiddleware
    {
        if (is_callable($data)) {
            $this->callable[implode(DS, $uri)] = $data;
        }
        if (isset($data['controller']) && isset($data['action'])) {
            $this->routeClass->http('request')->controller = $data['controller'];
            $this->routeClass->http('request')->action = $data['action'];
        } else {
            throw new Exceptions("This route contains their respective control and action.");
        }
        return $this;
    }

}
