<?php

namespace Restfull\Error\Middleware;

use Restfull\Error\Exceptions;
use Restfull\Http\Middleware\Middleware;
use Restfull\Http\Request;
use Restfull\Http\Response;
use Throwable;

/**
 * Class ErrorMiddleware
 * @package Restfull\Error\Middleware
 * @author  José Luis
 */
class ErrorMiddleware extends Middleware
{

    /**
     * @var ErrorHandler
     */
    private $error;

    /**
     * ErrorMiddleware constructor.
     * @param Request $request
     * @param Response $response
     * @throws Exceptions
     */
    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->error = $this->instance->resolveClass(
                $this->instance->namespaceClass(
                        "%s" . DS_REVERSE . "Error" . DS_REVERSE . "ErrorHandler",
                        [ROOT_NAMESPACE]
                ),
                ['request' => $request, 'response' => $response, 'instance' => $this->instance]
        );
        return $this;
    }

    /**
     * @param object $next
     * @return object
     */
    public function __invoke(object $next): object
    {
        try {
            return $next();
        } catch (Throwable $ex) {
            if ($this->request->bolleanApi()) {
                return $this->error->apiError($ex);
            }
            if ($this->request->erroExision) {
                $this->error->logError($ex);
                $this->request->controller = "Error";
                $this->request->action = "handling";
                return $this->error->MVCHandling($ex);
            }
            $this->response->body($ex->getMessage());
            return $this->response;
        }
    }

}