<?php

namespace Restfull\Executing\Middleware;

use Restfull\Error\Exceptions;
use Restfull\Http\Middleware\Middleware;
use Restfull\Http\Request;
use Restfull\Http\Response;
use Restfull\Security\JsonWebTokens;

/**
 * Class ApiMiddleware
 * @package Restfull\Executing\Middleware
 */
class ApiMiddleware extends Middleware
{

    /**
     * @var JsonWebTokens
     */
    private $security;

    /**
     * @var string
     */
    private $error = 'null';

    /**
     * ApiMiddleware constructor.
     * @param Request $request
     * @param Response $response
     * @throws Exceptions
     */
    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->security = $this->instance->resolveClass(
                $this->instance->namespaceClass(
                        "%s" . DS_REVERSE . "Security" . DS_REVERSE . "JsonWebTokens",
                        [ROOT_NAMESPACE]
                )
        );
        return $this;
    }

    /**
     * @param object $next
     * @return object
     */
    public function __invoke(object $next): object
    {
        if ($this->request->bolleanApi()) {
            $this->api();
            if (!empty($this->error)) {
                $datas = $next();
                return $this->apiRender($datas);
            }
            return $this;
        }
        return $next();
    }

    /**
     * @return ApiMiddleware
     * @throws Exceptions
     */
    public function api(): ApiMiddleware
    {
        $this->response->headers(['Content-Type' => 'application/json; charset=UTF-8']);
        $checkToken = $this->security->checkAPI($this->request, $this->response);
        if (!isset($checkToken['error'])) {
            if ($checkToken['response']->getHttpCode() !== 401) {
                header_remove('authorization');
            }
        } else {
            $this->error = 'erro';
        }
        $this->request = $checkToken['response'];
        $this->response = $checkToken['response'];
        return $this;
    }

    /**
     * @param array $datas
     * @return ApiMiddleware
     */
    public function apiRender(array $datas): ApiMiddleware
    {
        if (isset($headers['Authorization'])) {
            $this->response->body(json_encode($datas['view'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $this;
        }
        $this->security->encrypt($datas['access']);
        return $this;
    }
}