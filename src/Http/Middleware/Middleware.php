<?php

namespace Restfull\Http\Middleware;

use Restfull\Core\Instances;
use Restfull\Error\Exceptions;
use Restfull\Filesystem\File;
use Restfull\Http\Request;
use Restfull\Http\Response;
use Restfull\Http\Runner;

/**
 * Class Middleware
 * @package Restfull\Http\Middleware
 */
class Middleware
{

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var InstanceClass
     */
    protected $instance;

    /**
     * Middleware constructor.
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        if (!($this->instance instanceof Instances)) {
            $this->instance = new Instances();
        }
        return $this;
    }

    /**
     * @param Runner $run
     * @return Runner
     * @throws Exceptions
     */
    public function run(Runner $run): Runner
    {
        $class = [
                "%s" . DS_REVERSE . "Error" . DS_REVERSE . "Middleware" . DS_REVERSE . "ErrorMiddleware",
                "%s" . DS_REVERSE . "Security" . DS_REVERSE . "Middleware" . DS_REVERSE . "SecurityMiddleware",
                "%s" . DS_REVERSE . "Route" . DS_REVERSE . "Middleware" . DS_REVERSE . "RouteMiddleware",
                "%s" . DS_REVERSE . "Executing" . DS_REVERSE . "Middleware" . DS_REVERSE . "ApiMiddleware",
                "%s" . DS_REVERSE . "Core" . DS_REVERSE . "Middleware" . DS_REVERSE . "AplicationMiddleware"
        ];
        for ($a = 0; $a < count($class); $a++) {
            if ((new File($this->instance->namespaceClass($class[$a] . ".php", [PATH_NAMESPACE])))->exists()) {
                $run->add($this->instance->namespaceClass($class[$a], [ROOT_NAMESPACE]));
            }
        }
        return $run;
    }

    /**
     * @return array
     */
    public function http(): array
    {
        return ['request' => $this->request, 'response' => $this->response, 'instance' => $this->instance];
    }

}
