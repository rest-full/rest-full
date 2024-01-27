<?php

declare(strict_types=1);

namespace Restfull\Executing\Middleware;

use Restfull\Http\Middleware\Middleware;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 *
 */
class CacheMiddleware extends Middleware
{

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param Request $request
     * @param Response $response
     * @param object $instance
     */
    public function __construct(Request $request, Response $response, object $instance)
    {
        $this->cache = $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Executing' . DS_REVERSE . 'Cache',
            ['instance' => $instance, 'request' => $request]
        );
        parent::__construct($request, $response, $instance);
        return $this;
    }

    /**
     * @param object $next
     *
     * @return object
     * @throws Exceptions
     */
    public function __invoke(object $next): CacheMiddleware
    {
        if (strtotime(date('Y-m-d H:i:s')) > $this->cache->expirationTime()) {
            $next();
            $this->cache->create($this->response->content());
        }
        $this->response->body($this->cache->read());
        return $this;
    }

}
