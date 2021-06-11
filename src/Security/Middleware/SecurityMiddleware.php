<?php

namespace Restfull\Security\Middleware;

use Restfull\Error\Exceptions;
use Restfull\Http\Middleware\Middleware;
use Restfull\Http\Request;
use Restfull\Http\Response;
use Restfull\Security\Security;

/**
 * Class ErrorMiddleware
 * @package Restfull\Security\Middleware
 * @author  José Luis
 */
class SecurityMiddleware extends Middleware
{

    /**
     * @var Security
     */
    private $security;

    /**
     * ErrorMiddleware constructor.
     * @param Request $request
     * @param Response $response
     * @throws Exceptions
     */
    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->security = $this->request->bootstrap('security');
        return $this;
    }

    /**
     * @param object $next
     * @return object
     */
    public function __invoke(object $next): object
    {
        $csrf = $this->request->csrfPost();
        if (!empty($csrf)) {
            if (!$this->security->valideCsrf($csrf)) {
                $this->request->route = $this->security->csrfOldRoute();
            }
            return $next();
        }
        return $next();
    }

}