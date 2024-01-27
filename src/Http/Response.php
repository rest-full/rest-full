<?php

declare(strict_types=1);

namespace Restfull\Http;

use Restfull\Error\Exceptions;
use Restfull\Route\Route;

/**
 *
 */
class Response
{

    /**
     * @var array
     */
    public $server;

    /**
     * @var int
     */
    private $httpCode = 200;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var string
     */
    private $body = '';

    /**
     * @var string
     */
    private $file = '';

    /**
     * @var Route
     */
    private $route;

    /**
     * @var array
     */
    private $message = [
        100 => 'Continue',
        101 => 'Switching',
        102 => 'Processing (WebDAV)',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status (WebDAV)',
        208 => 'Multi-Status (WebDAV)',
        226 => 'IM Used (HTTP Delta encoding)',
        300 => 'Multiple Choice',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'unused',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity (WebDAV)',
        423 => 'Locked (WebDAV)',
        424 => 'Failed Dependency (WebDAV)',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected (WebDAV)',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        598 => 'Network read timeout error',
        599 => 'Network connect timeout error'
    ];

    /**
     * @param array $server
     */
    public function __construct(array $server)
    {
        $this->server = $server;
        return $this;
    }

    /**
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * @param int $httpCode
     *
     * @return Response
     */
    public function setHttpCode(int $httpCode): Response
    {
        if ($httpCode != 200) {
            $this->httpCode = $httpCode;
        }
        if (isset($this->serve['SERVER_PROTOCOL'])) {
            $protocol = $this->server['SERVER_PROTOCOL'];
        } else {
            $protocol = 'HTTP/1.1';
        }
        $this->headers['IDM'] = $protocol . ' ' . $this->httpCode . ' '
            . $this->message[$this->httpCode];
        return $this;
    }

    /**
     * @param string $file
     *
     * @return Response
     * @throws Exceptions
     */
    public function file(string $file): Response
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return bool
     */
    public function checkBody(): bool
    {
        return (isset($this->body)) ? true : false;
    }

    /**
     * @return string
     */
    public function send(): string
    {
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }
        if (!empty($this->file)) {
            header('location: ' . $this->file);
            return $this->body;
        }
        return $this->body;
    }

    /**
     * @param string $url
     * @param bool $ajax
     *
     * @return Response
     */
    public function redirect(string $url, bool $ajax = false): Response
    {
        if (!$ajax) {
            $this->headers = ["location" => substr($url, 0, 1) === DS ? URL . $url : URL . DS . $url];
        } else {
            $this->body(
                json_encode(['valid' => true, 'redirect' => URL . $url],
                    JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }
        return $this;
    }

    /**
     * @param string $body
     *
     * @return Response
     */
    public function body(string $body): Response
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @param array $headers
     *
     * @return Response
     */
    public function setHeaders(array $headers): Response
    {
        foreach ($headers as $key => $value) {
            $this->headers[$key] = $value;
        }
        return $this;
    }

    /**
     * @param Route $route
     *
     * @return Response
     */
    public function route(Route $route): Response
    {
        $this->route = $route;
        return $this;
    }

    /**
     * @param string $nameRoute
     * @param string $prefix
     *
     * @return string
     * @throws Exceptions
     */
    public function identifyRouteByName(string $nameRoute, string $prefix, $returnData = null): string
    {
        if ($this->route->isActive()) {
            $routes = $this->route->names();
            if (!isset($routes[$nameRoute])) {
                if (is_null($returnData)) {
                    throw new Exceptions("This {$nameRoute} route not exit.", 404);
                }
                return $returnData;
            }
            if (!isset($routes[$nameRoute][$prefix])) {
                throw new Exceptions("the are no {$prefix} as a prefix", 404);
            }
            return $routes[$nameRoute][$prefix]['route'];
        }
        return $nameRoute;
    }

    /**
     * @param string $name
     * @param string $prefix
     *
     * @return string
     * @throws Exceptions
     */
    public function identifyRouteParametersByName(string $nameRoute, string $prefix): array
    {
        if ($this->route->isActive()) {
            $routes = $this->route->names();
            if (array_key_exists($nameRoute, $routes) === false) {
                throw new Exceptions("This {$nameRoute} route not exit.", 404);
            }
            return $routes[$nameRoute][$prefix]['params'];
        }
        return [];
    }

    /**
     * @param string $control
     * @param string $method
     *
     * @return array
     */
    public function routes(string $control, string $method = ''): array
    {
        if (empty($method)) {
            $allRoutes = [];
            $methods = ['GET', 'POST', 'PUT', 'DELETE'];
            foreach ($methods as $method) {
                foreach ($this->route->uri($method) as $key => $values) {
                    if ($control === $key) {
                        $allRoutes = array_merge($allRoutes, array_keys($values));
                        break;
                    }
                }
            }
            array_unique($allRoutes);
            return $allRoutes;
        }
        foreach ($this->route->uri($method) as $key => $value) {
            if ($control === $key) {
                $routes = array_keys($values);
                break;
            }
        }
        return $routes;
    }

    /**
     * @return string
     */
    public function content(): string
    {
        return $this->body;
    }

}
