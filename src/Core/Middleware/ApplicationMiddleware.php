<?php

declare(strict_types=1);

namespace Restfull\Core\Middleware;

use Restfull\Error\Exceptions;
use Restfull\Http\Middleware\Middleware;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 *
 */
class ApplicationMiddleware extends Middleware
{

    /**
     * @var Aplication
     */
    private $app;

    /**
     * @param Request $request
     * @param Response $response
     *
     * @throws Exceptions
     */
    public function __construct(Request $request, Response $response, object $instance)
    {
        $this->app = $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Core' . DS_REVERSE . 'Application',
            ['instance' => $instance]
        );
        parent::__construct($request, $response, $instance);
        return $this;
    }

    /**
     * @return object
     * @throws Exceptions
     */
    public function __invoke(): object
    {
        $dispatcher = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Executing' . DS_REVERSE . 'Dispatcher',
            ['request' => $this->request, 'response' => $this->response, 'instance' => $this->instance]
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