<?php

namespace Restfull\Route\Middleware;

use Restfull\Error\Exceptions;
use Restfull\Http\Middleware\Middleware;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 * Class RouteMiddleware
 * @package Restfull\Http\Middleware
 */
class RouteMiddleware extends Middleware
{

    /**
     * @var array
     */
    private $routes = [];

    /**
     * @var object|null
     */
    private $routeClass;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var array
     */
    private $callable = [];

    /**
     * @var bool
     */
    private $searchPrefix = false;

    /**
     * RouteMiddleware constructor.
     * @param Request $request
     * @param Response $response
     * @throws Exceptions
     */
    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->routeClass = $this->instance->resolveClass(
                $this->instance->namespaceClass(
                        "%s" . DS_REVERSE . "Route" . DS_REVERSE . "Route",
                        [ROOT_NAMESPACE]
                ),
                ['request' => $request, 'response' => $response]
        );
        if ($this->routeClass->countPrefix > 0) {
            $this->searchPrefix = true;
        }
        return $this;
    }

    /**
     * @param object $next
     * @return object
     * @throws Exceptions
     */
    public function __invoke(object $next): object
    {
        $explodeRoute = explode(DS, $this->routeClass->http('request')->route);
        if ($explodeRoute[0] == "error") {
            $this->error();
            return $next();
        }
        $this->compare();
        if (!empty($this->routeClass->http('request')->controller)) {
            if (count($this->callable) > 0) {
                $this->routeClass->http('request')->callable = $this->callable;
            }
            $this->routeClass->http('response')->route($this->routeClass);
            return $next();
        } else {
            throw new Exceptions("The {$this->routeClass->http('request')->route} route was not found.", 404);
        }
    }

    /**
     * @return RouteMiddleware
     * @throws Exceptions
     */
    private function error(): RouteMiddleware
    {
        $this->getURI();
        $this->data['count'] = 0;
        $uri = explode(DS, $this->routeClass->http('request')->route);
        foreach ($this->routes as $route => $data) {
            $url = explode(DS, substr($route, 1));
            $this->check($uri, $url, $data);
        }
        return $this;
    }

    /**
     * @return RouteMiddleware
     * @throws Exceptions
     */
    private function getURI(): RouteMiddleware
    {
        $routes = $this->routeClass->uri($this->routeClass->http('request')->data('method'));
        foreach (array_keys($routes) as $key) {
            if (substr(
                            $this->routeClass->http('request')->route,
                            0,
                            stripos($this->routeClass->http('request')->route, DS)
                    ) == $key) {
                $this->routes = $routes[$key];
            }
        }
        if (!isset($this->routes)) {
            throw new Exceptions(
                    "The route {$this->routeClass->http('request')->route} was not found in the routes.php file that stay in config."
            );
        }
        return $this;
    }

    /**
     * @return RouteMiddleware
     * @throws Exceptions
     */
    private function compare(): RouteMiddleware
    {
        $this->data['count'] = 0;
        $this->getURI();
        $uri = explode(DS, $this->routeClass->http('request')->route);
        foreach ($this->routes as $route => $data) {
            $count = 0;
            $url = explode(DS, substr($route, 1));
            if (count($uri) == count($url)) {
                if ($this->searchPrefix && strtolower($this->routes[DS . implode(DS, $uri)]['controller']) != $url[0]) {
                    list($uri, $url) = $this->prefix($uri, $url);
                }
                foreach ($url as $key => $value) {
                    for ($a = 0; $a < count($uri); $a++) {
                        if ($value == $uri[$a]) {
                            $count++;
                            unset($url[$key]);
                        }
                    }
                }
                if ($count == 2) {
                    if (count($url) > 0) {
                        foreach (array_keys($url) as $key) {
                            $param = substr($value, 1, -1);
                            $this->routeClass->http('request')->params[$param] = $uri[$key];
                        }
                    }
                    $this->exchange($data);
                } else {
                    $this->data['count']++;
                }
            }
        }
        return $this;
    }

    /**
     * @param array $uri
     * @param array $url
     * @return array
     */
    private function prefix(array $uri, array $url): array
    {
        $this->routeClass->http('request')->prefix = ucfirst($url[0]);
        for ($a = 1; $a < count($url); $a++) {
            $url[($a - 1)] = $url[$a];
        }
        for ($b = 1; $b < count($uri); $b++) {
            $uri[($b - 1)] = $uri[$b];
        }
        unset($url[($a - 1)], $uri[($b - 1)]);
        return [$uri, $url];
    }

    /**
     * @param $data
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