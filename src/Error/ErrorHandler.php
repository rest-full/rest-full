<?php

namespace Restfull\Error;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Restfull\Core\Instances;
use Restfull\Http\Request;
use Restfull\Http\Response;
use Throwable;

/**
 * Class ErrorHandler
 * @package Restfull\Error
 */
class ErrorHandler
{
    /**
     * @var InstanceClass
     */
    private $instance;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Request
     */
    private $request;

    /**
     * ErrorHandler constructor.
     * @param Request $request
     * @param Response $response
     * @param Instances $instance
     */
    public function __construct(Request $request, Response $response, Instances $instance)
    {
        $this->request = $request;
        $this->response = $response;
        $this->instance = $instance;
        return $this;
    }

    /**
     * @param Throwable $exception
     * @return bool
     * @throws \Exception
     */
    public function logError(throwable $exception): bool
    {
        $erro = substr($exception->getCode(), 0, 1) . "00";
        $logger = empty($erro) || $erro == 0 ? 500 : $erro;
        $msg = "[" . date('Y-m-d H:i:s') . "] [{$logger}]: " . $exception->getMessage() . " in " . $exception->getFile(
                ) . " on Line " . $exception->getLine() . "\n";
        $log = new Logger("Erros");
        $log->pushHandler(new StreamHandler(RESTFULL . DS . "Error" . DS . "Log" . DS . "error.log"));
        return $log->log($logger, nl2br($msg));
    }

    /**
     * @param Throwable $exception
     * @return Response
     * @throws Exceptions
     */
    public function MVCHandling(Throwable $exception): Response
    {
        error_reporting(0);
        ini_set('display_errors', 0);
        $controller = $this->instance->resolveClass(
                $this->instance->namespaceClass(
                        "Restfull" . DS_REVERSE . "Error" . DS_REVERSE . "Exceptions" . DS_REVERSE . "%s",
                        [
                                $this->request->controller . MVC[0]
                        ]
                ),
                ['request' => $this->request, 'response' => $this->response, 'instance' => $this->instance],
                false
        );
        extract(
                $controller->{$this->request->action}(
                        [
                                'msg' => $exception->getMessage(),
                                'traces' => (stripos(
                                        get_class($exception),
                                        "Exceptions"
                                ) !== false ? $exception->getTraces() : $exception->getTrace())
                        ]
                )
        );
        ob_start();
        require_once RESTFULL . DS . "Error" . DS . "Exceptions" . DS . 'error.phtml';
        $this->response->body(ob_get_contents());
        ob_clean();
        return $this->response;
    }

    /**
     * @param Throwable $exception
     * @return Response
     */
    public function apiError(throwable $exception): Response
    {
        $this->response->body(
                json_encode(
                        ['message' => $exception->getMessage(), 'code' => $exception->getCode()],
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                )
        );
        return $this->response;
    }
}
