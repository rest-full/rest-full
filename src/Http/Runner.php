<?php

declare(strict_types=1);

namespace Restfull\Http;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;
use Restfull\Http\Middleware\Middleware;

/**
 *
 */
class Runner
{

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Request
     */
    private $request;

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
     * @var Instances
     */
    private $instance;

    /**
     *
     */
    public function __construct(Request $request, Response $response)
    {
        $this->queue = [];
        $this->response = $response;
        $this->request = $request;
        return $this;
    }

    /**
     * @param string $class
     *
     * @return Runner
     */
    public function add(string $class): Runner
    {
        $this->queue[] = $class;
        return $this;
    }

    /**
     * @param Instances $instance
     *
     * @return object|Response
     * @throws Exceptions
     */
    public function run(Instances $instance)
    {
        $this->middleware = $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Http' . DS_REVERSE . 'Middleware' . DS_REVERSE . 'Middleware',
            ['request' => $this->request, 'response' => $this->response, 'instance' => $instance]
        );
        $this->request->applicationBootstrap(
            $instance->resolveClass(
                ROOT_NAMESPACE[0] . DS_REVERSE . 'Core' . DS_REVERSE . 'Middleware' . DS_REVERSE . 'ApplicationMiddleware',
                ['request' => $this->request, 'response' => $this->response, 'instance' => $instance]
            )->bootstrap()
        );
        $this->instance = $instance;
        $this->request->bootstrap('hash')->http($this->request, $this->response);
        $this->request->methods()->path_info()->checkExistAPI();
        if ($this->request->route === '') {
            $route = 'main' . DS . 'index';
            if ($this->request->encryptionKeys['general']) {
                $route = $this->request->bootstrap('hash')->encrypt($route);
                if (!empty($this->request->base)) {
                    if (stripos($route, $this->request->base) === false) {
                        $route = $this->request->base . DS . $route;
                    }
                }
                $this->response->redirect($route);
                return $this->response;
            }
            $this->request->route = $route;
        }
        if ($this->files($this->request->route)) {
            $this->middleware->queue($this, $this->request->bootstrap('middleware'));
            $this->index = 0;
            return $this();
        }
        return $this;
    }

    /**
     * @param string $file
     *
     * @return bool
     * @throws Exceptions
     */
    public function files(string $file): bool
    {
        if (empty($file)) {
            $file = $this->request->route;
        }
        $datas = explode(DS, $file);
        $identify = $this->request->identifyHome($datas);
        if ($identify === 0) {
            return true;
        }
        $file = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Filesystem' . DS_REVERSE . 'File',
            ['instance' => $this->instance, 'file' => $file]
        );
        if ($file->folder()->exists()) {
            $extension = explode(".", $datas[count($datas) - 1]);
            if (in_array($extension[1], ['jpg', 'png'])) {
                if ($file->exists()) {
                    $this->response->file($file->pathinfo())->body('');
                    return false;
                }
                return true;
            }
            if ($file->exists()) {
                $this->response->file($file->pathinfo())->body('');
                return false;
            }
            return true;
        }
        return true;
    }

    /**
     * @return object
     * @throws Exceptions
     */
    public function __invoke(): object
    {
        $next = $this->queue[$this->index];
        if (!is_object($this->queue[$this->index])) {
            $file = $this->instance->resolveClass(
                ROOT_NAMESPACE[0] . DS_REVERSE . 'Filesystem' . DS_REVERSE . 'File',
                ['instance' => $this->instance, 'file' => $next . "php"]
            );
            if ($file->exists()) {
                throw new Exceptions("Middleware {$next} was not found.");
            }
            $next = $this->instance->resolveClass($next, $this->middleware->http());
        }
        $this->index++;
        return $next($this);
    }

}
