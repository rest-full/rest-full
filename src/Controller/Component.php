<?php

declare(strict_types=1);

namespace Restfull\Controller;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;
use Restfull\Http\Request;
use Restfull\Http\Response;
use Restfull\Utility\Translator;

/**
 *
 */
class Component
{

    /**
     * @var BaseController
     */
    protected $controller;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var mixed
     */
    protected $query;

    /**
     * @var InstanceClass
     */
    protected $instance;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param BaseController $controller
     */
    public function __construct(BaseController $controller)
    {
        $this->http($controller->request, $controller->response);
        $this->instance = $controller->instances();
        $this->translator = $controller->Translator;
        $this->controller = $controller;
        return $this;
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Component
     */
    private function http(Request $request, Response $response): Component
    {
        $this->request = $request;
        $this->response = $response;
        return $this;
    }

    /**
     * @param string $type
     * @param array $table
     * @param array $options
     * @param array $details
     *
     * @return object
     * @throws Exceptions
     */
    public function querys(string $type, array $table, array $options = [], array $details = [])
    {
        return $this->controller->querys($type, $table, $options, $details);
    }

    /**
     * @param string $format
     * @param array $args
     *
     * @return string
     * @throws Exceptions
     */
    public function vsprintf(string $format, array $args): string
    {
        return $this->instance->assemblyClassOrPath($format, $args);
    }

    /**
     * @return Instances
     */
    public function instance()
    {
        return $this->instance;
    }
}
