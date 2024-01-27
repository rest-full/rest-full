<?php

declare(strict_types=1);

namespace Restfull\Http\Middleware;

use Restfull\Http\Request;
use Restfull\Http\Response;
use Restfull\Http\Runner;

/**
 *
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
     * @var array
     */
    private $middlewares = [];

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response, ?object $instance = null)
    {
        $this->request = $request;
        $this->response = $response;
        if (!is_null($instance)) {
            $this->instance = $instance;
        }
        return $this;
    }

    /**
     * @param array $middlewares
     *
     * @return Middleware
     */
    public function queue(Runner $run, array $middlewares): Runner
    {
        $classPath = [
            'Error' . DS_REVERSE . 'Middleware' . DS_REVERSE . 'ErrorMiddleware',
            'Security' . DS_REVERSE . 'Middleware' . DS_REVERSE . 'SecurityMiddleware',
            'Route' . DS_REVERSE . 'Middleware' . DS_REVERSE . 'RouteMiddleware'
        ];
        $classPath = array_merge(
            array_merge($classPath, $middlewares),
            [
                'Executing' . DS_REVERSE . 'Middleware' . DS_REVERSE . 'WebServiceMiddleware',
                'Core' . DS_REVERSE . 'Middleware' . DS_REVERSE . 'ApplicationMiddleware'
            ]
        );
        $count = count($classPath);
        for ($a = 0; $a < $count; $a++) {
            if (in_array(
                    substr($classPath[$a], strripos($classPath[$a], DS_REVERSE) + 1),
                    [
                        'ErrorMiddleware',
                        'SecurityMiddleware',
                        'RouteMiddleware',
                        'WebServiceMiddleware',
                        'ApplicationMiddleware',
                        'CacheMiddleware'
                    ]
                ) !== false) {
                if ($this->instance->validate(
                    RESTFULL_FRAMEWORK . str_replace(DS_REVERSE, DS, $classPath[$a]) . ".php",
                    'file'
                )) {
                    $run->add(ROOT_NAMESPACE[0] . DS_REVERSE . $classPath[$a]);
                }
            } else {
                if ($this->instance->validate(
                    RESTFULL . str_replace(DS_REVERSE, DS, $classPath[$a]) . ".php",
                    'file'
                )) {
                    $run->add(ROOT_NAMESPACE[1] . DS_REVERSE . $classPath[$a]);
                }
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
