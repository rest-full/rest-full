<?php

namespace Restfull\Core\Middleware;

use Restfull\Error\Exceptions;
use Restfull\Http\Middleware\Middleware;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 * Class AplicationMiddleware
 * @package Restfull\Core\Middleware
 */
class AplicationMiddleware extends Middleware
{

    /**
     * @var Aplication
     */
    private $app;

    /**
     * AplicationMiddleware constructor.
     * @param Request $request
     * @param Response $response
     * @throws Exceptions
     */
    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->app = $this->instance->resolveClass(
                $this->instance->namespaceClass(
                        "%s" . DS_REVERSE . "Core" . DS_REVERSE . "Aplication",
                        [ROOT_NAMESPACE]
                )
        );
        return $this;
    }

    /**
     * @return object
     * @throws Exceptions
     */
    public function __invoke(): object
    {
        $dispatcher = $this->instance->resolveClass(
                $this->instance->namespaceClass(
                        "%s" . DS_REVERSE . "Executing" . DS_REVERSE . "Dispatcher",
                        [ROOT_NAMESPACE]
                ),
                ['request' => $this->request, 'response' => $this->response, 'intance' => $this->instance]
        );
        $dispatcher->run();
        return $this;
    }

    /**
     * @return array
     */
    public function bootstrap(): array
    {
        $bootstrap = $this->app->bootstrap()->bootstrap;
        $this->app->bootstrap('desligar');
        return $bootstrap;
    }
}