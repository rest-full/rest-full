<?php

declare(strict_types=1);

namespace Restfull\Error;

use DateTime;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Restfull\Container\Instances;
use Restfull\Controller\BaseController;
use Restfull\Http\Request;
use Restfull\Http\Response;
use Throwable;

/**
 *
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
     *
     * @return bool
     * @throws Exception
     */
    public function logError(throwable $exception): bool
    {
        $classFile = ROOT . 'log' . DS_REVERSE . strtolower($this->request->controller) . '.log';
        $classFile = $this->instance->locateTheFileWhetherItIsInTheAppOrInTheFramework(
            $classFile,
            strtolower($this->request->controller)
        );
        $errorCode = $exception->getCode();
        $code = 500;
        if ($errorCode !== 0) {
            $code = substr((string)$errorCode, 0, 1) . "00";
        }
        $msg = $this->renderMsg($code, $exception->getMessage(), $exception->getFile(), $exception->getLine());
        $log = new Logger("Erros");
        $stream = new StreamHandler($classFile);
        return $log->pushHandler($stream)->log($code, nl2br($msg));
    }

    /**
     * @param $code
     * @param $error
     * @param $file
     * @param $line
     * @return string
     */
    private function renderMsg($code, $error, $file, $line): string
    {
        $msg = '[%s][%s]: %s in %s on line %s\n';
        $date = new DateTime(date('Y-m-d H:i:s'));
        return vsprintf($msg, [$date->format('Y-m-d H:i:s'), $code, $error, $file, $line]);
    }

    /**
     * @param Throwable $exception
     *
     * @return ErrorHandler
     */
    public function MVCHandling(Throwable $exception): ErrorHandler
    {
        error_reporting(0);
        ini_set('display_errors', 0);
        $controllerPath = substr(RESTFULL, 0, -1) . DS . MVC[0] . DS . ucfirst(
                $this->request->controller . MVC[0]
            ) . '.php';
        $controllerPath = $this->instance->renameClass(
            $this->instance->locateTheFileWhetherItIsInTheAppOrInTheFramework(
                $controllerPath,
                $this->request->controller
            )
        );
        $controller = $this->instance->resolveClass(
            $controllerPath,
            ['request' => $this->request, 'response' => $this->response, 'instance' => $this->instance]
        );
        $controller->errorHeadRender()->{$this->request->action}(
            [
                'msg' => $exception->getMessage(),
                'traces' => (stripos(get_class($exception), "Exceptions") !== false ? $exception->getTraces(
                ) : $exception->getTrace())
            ]
        );
        $viewBuilder = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Builders' . DS_REVERSE . MVC[1] . 'Builder',
            [
                'instance' => $this->instance,
                'request' => $this->request,
                'response' => $this->response,
                'datas' => $controller->view
            ]
        );
        $viewBuilder->config(
            [
                'activeHelpers' => $controller->activeHelpers,
                'action' => $controller->newAction(),
                'encrypted' => $controller->encrypted
            ]
        )->render($this->pathView($controller));
        $this->response = $viewBuilder->responseView();
        return $this;
    }

    /**
     * @param BaseController $controller
     * @return array
     * @throws Exceptions
     */
    private function pathView(BaseController $controller): array
    {
        $layout = substr(RESTFULL, 0, -1) . DS . 'Template' . DS . 'Layout' . DS . $controller->layout . '.phtml';
        $pageContent = substr(RESTFULL, 0, -1) . DS . 'Template' . DS . ucfirst(
                $controller->name
            ) . DS . $controller->action . ".phtml";
        $file = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Filesystem' . DS_REVERSE . 'File',
            ['instance' => $this->instance, 'file' => $pageContent]
        );
        if (!$file->exists()) {
            $pageContent = substr(RESTFULL_FRAMEWORK, 0, -1) . DS . ucfirst(
                    $controller->name
                ) . DS . "Exceptions" . DS . $controller->action . '.phtml';
        }
        return [$layout, $pageContent];
    }

    /**
     * @param Throwable $exception
     *
     * @return Response
     */
    public function apiError(throwable $exception): Response
    {
        $this->response->body(
            json_encode(['message' => $exception->getMessage(), 'code' => $exception->getCode()],
                JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        return $this->response;
    }
}
