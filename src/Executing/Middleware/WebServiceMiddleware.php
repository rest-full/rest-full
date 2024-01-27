<?php

declare(strict_types=1);

namespace Restfull\Executing\Middleware;

use Restfull\Http\Middleware\Middleware;
use Restfull\Http\Request;
use Restfull\Http\Response;
use Restfull\Security\JsonWebTokens;

/**
 *
 */
class WebServiceMiddleware extends Middleware
{

    /**
     * @var JsonWebTokens
     */
    private $webservice;

    /**
     * @var string
     */
    private $error = 'null';

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response, object $instance)
    {
        parent::__construct($request, $response, $instance);
        $this->webservice = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Executing' . DS_REVERSE . 'WebService'
        );
        return $this;
    }

    /**
     * @param object $next
     *
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
     */
    public function api(): ApiMiddleware
    {
        $this->response->headers(['Content-Type' => 'application/json; charset=UTF-8']);
        $checkToken = $this->webservice->checkAPI($this->instance, $this->request, $this->response);
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
     *
     * @return ApiMiddleware
     */
    public function apiRender(array $datas): ApiMiddleware
    {
        if (isset($headers['Authorization'])) {
            $this->response->body(
                json_encode(
                    $datas['view'],
                    JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                )
            );
            return $this;
        }
        $this->webservice->encrypt($datas['access']);
        return $this;
    }
}