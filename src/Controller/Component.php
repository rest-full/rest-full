<?php

namespace Restfull\Controller;

use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 * Class Component
 * @package Restfull\Controller
 */
class Component
{

    /**
     * @var Controller
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
     * Component constructor.
     * @param BaseController $controller
     */
    public function __construct(BaseController $controller)
    {
        $this->http($controller->request, $controller->response);
        $this->controller = $controller;
        return $this;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Component
     */
    private function http(Request $request, Response $response): Component
    {
        $this->request = $request;
        $this->response = $response;
        return $this;
    }

}
