<?php

declare(strict_types=1);

namespace Restfull\Http;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;

/**
 *
 */
class Server
{

    /**
     * @var Runner
     */
    private $runner;

    /**
     * @var Application
     */
    private $app;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Instances
     */
    private $instance;

    /**
     *
     */
    public function __construct(Instances $instance)
    {
        if (stripos($_SERVER['HTTP_HOST'], ':') !== false) {
            list($_SERVER['HTTP_HOST'], $_SERVER['HTTP_PORT']) = explode(':', $_SERVER['HTTP_HOST']);
        }
        if (!isset($_SERVER['HTTP_PORT'])) {
            $_SERVER['HTTP_PORT'] = '80';
        }
        if (!defined('RESTFULL_FRAMEWORK')) {
            require_once __DIR__ . '/../../config/pathServer.php';
        }
        $this->request = $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Http' . DS_REVERSE . 'Request',
            ['server' => $_SERVER]
        );
        $this->response = $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Http' . DS_REVERSE . 'Response',
            ['server' => $this->request->server]
        );
        $this->runner = $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Http' . DS_REVERSE . 'Runner',
            ['request' => $this->request, 'response' => $this->response]
        );
        $this->instance = $instance;
        return $this;
    }

    /**
     * @return Server
     * @throws Exceptions
     */
    public function execute()
    {
        $this->runner->run($this->instance);
        return $this;
    }

    /**
     * @return string
     */
    public function send()
    {
        return $this->response->send();
    }

}
