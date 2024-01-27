<?php

declare(strict_types=1);

namespace Restfull\Error\Middleware;

use Restfull\Http\Middleware\Middleware;
use Restfull\Http\Request;
use Restfull\Http\Response;
use Throwable;

/**
 *
 */
class ErrorMiddleware extends Middleware
{

    /**
     * @var ErrorHandler
     */
    private $error;

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response, object $instance)
    {
        $this->error = $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Error' . DS_REVERSE . 'ErrorHandler',
            ['request' => $request, 'response' => $response, 'instance' => $instance]
        );
        parent::__construct($request, $response, $instance);
        return $this;
    }

    /**
     * @param object $next
     *
     * @return object
     */
    public function __invoke(object $next): object
    {
        try {
            return $next();
        } catch (Throwable $ex) {
//            if ($this->request->bolleanApi()) {
//                return $this->error->apiError($ex);
//            }
//            if ($this->request->erroExision) {
            $this->request->controller = "Error";
            $this->request->action = "handling";
            $this->error->logError($ex);
            $this->error->MVCHandling($ex);
            if ($this->request->ajax) {
                $this->response->body(
                    json_encode(['type' => 'exception', 'result' => $this->response->send()],
                        JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE)
                );
            }
            return $this->response;
//            }
//            $this->response->body($ex->getMessage());
//            return $this->response;
        }
    }

}
