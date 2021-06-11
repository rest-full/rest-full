<?php

namespace Restfull\Http;

use Restfull\Core\Instances;
use Restfull\Core\Middleware\AplicationMiddleware;
use Restfull\Error\Exceptions;
use Restfull\Filesystem\File;
use Restfull\Http\Middleware\Middleware;

/**
 * Class Runner
 * @package Restfull\Http
 */
class Runner
{

    /**
     * @var array
     */
    private $queue = [];

    /**
     * @var int
     */
    private $index = 0;

    /**
     * @var Middleware
     */
    private $middleware;

    /**
     * Runner constructor.
     */
    public function __construct()
    {
        $this->queue = [];
        return $this;
    }

    /**
     * @param string $class
     * @return Runner
     */
    public function add(string $class): Runner
    {
        $this->queue[] = $class;
        return $this;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return object
     * @throws Exceptions
     */
    public function run(Request $request, Response $response)
    {
        $this->middleware = new Middleware($request, $response);
        $request->bootstrap = (new AplicationMiddleware($request, $response))->bootstrap();
        $request->path_info()->checkExistAPI()->methods();
        $this->middleware->run($this);
        $this->index = 0;
        return $this();
    }

    /**
     * @return object
     * @throws Exceptions
     */
    public function __invoke(): object
    {
        $next = $this->queue[$this->index];
        if (!is_object($this->queue[$this->index])) {
            $instance = new Instances();
            if ((new File($next . "php"))->exists()) {
                throw new Exceptions($instance->namespaceClass('Middleware "%s" was not found.', [$next]));
            }
            $next = $instance->resolveClass($next, $this->middleware->http());
        }
        $this->index++;
        return $next($this);
    }

}
