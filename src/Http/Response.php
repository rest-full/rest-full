<?php

namespace Restfull\Http;

use Restfull\Core\Instances;
use Restfull\Error\Exceptions;
use Restfull\Filesystem\File;
use Restfull\Route\Route;

/**
 * Class Response
 * @package Restfull\Http
 */
class Response
{

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
     * @var array
     */
    private $server;

    /**
     * Response constructor.
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
        $this->headers['IDM'] = $protocol . ' ' . $this->httpCode . ' ' . $this->message[$this->httpCode];
        return $this;
    }

    /**
     * @param string $file
     * @return Response
     * @throws Exceptions
     */
    public function file(string $file): Response
    {
        if (!(new File($file))->exists()) {
            throw new Exceptions('This ' . $file . ' file did not exist.', 404);
        }
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
     * @throws Exceptions
     */
    public function send(): string
    {
        foreach ($this->headers as $key => $value) {
            header((new Instances())->namespaceClass("%s: %s", [$key, $value], true));
        }
        if (!empty($this->file)) {
            return $this->file;
        }
        return $this->body;
    }

    /**
     * @param string $url
     * @return Response
     */
    public function redirect(string $url, bool $ajax = false): Response
    {
        $baseUrl = URL;
        if ($this->server['SERVER_PORT'] != "80") {
            $baseUrl .= ":" . $this->server['SERVER_PORT'];
        }
        if (!$ajax) {
            $this->headers = [
                    "location" => $baseUrl . $url
            ];
        } else {
            $this->body(json_encode(['valid' => '1', 'redirect' => $baseUrl . $url]));
        }
        return $this;
    }

    /**
     * @param string $body
     * @return Response
     */
    public function body(string $body): Response
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @param array $headers
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
     * @return Response
     */
    public function route(Route $route): Response
    {
        $this->route = $route;
        return $this;
    }

    /**
     * @param string $name
     * @return string
     * @throws Exceptions
     */
    public function routeIdentify(string $name): string
    {
        return $this->route->nameRoute($name);
    }

}
