<?php

declare(strict_types=1);

namespace Restfull\Security\Middleware;

use Restfull\Http\Middleware\Middleware;
use Restfull\Http\Request;
use Restfull\Http\Response;
use Restfull\Security\Security;

/**
 *
 */
class SecurityMiddleware extends Middleware
{

    /**
     * @var Security
     */
    private $security;

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response, object $instance)
    {
        $this->security = $request->bootstrap('security');
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
        $csrf = $this->request->csrfPost();
        if (!empty($csrf)) {
            $this->request->requestMethod();
            if ($this->security->valideCsrf($csrf)) {
                $this->request->route = $this->security->csrfOldRoute();
                $this->request->requestMethodGet();
            }
        }
        return $next();
    }
}