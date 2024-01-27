<?php

declare(strict_types=1);

namespace Restfull\Http;

use Restfull\Authentication\Auth;
use Restfull\Error\Exceptions;

/**
 *
 */
class Request
{

    /**
     * @var string
     */
    public $route = '';

    /**
     * @var string
     */
    public $base = '';

    /**
     * @var string
     */
    public $controller = '';

    /**
     * @var array
     */
    public $params = [];

    /**
     * @var string
     */
    public $action = '';

    /**
     * @var bool
     */
    public $ajax = false;

    /**
     * @var bool
     */
    public $shorten = false;

    /**
     * @var array
     */
    public $server = [];

    /**
     * @var bool
     */
    public $erroExision = false;
    /**
     * @var Auth
     */
    public $auth;
    /**
     * @var string
     */
    public $prefix = '';
    /**
     * @var bool
     */
    public $renderCsrf = false;
    /**
     * @var array
     */
    public $encryptionKeys = [];
    /**
     * @var string
     */
    public $userAgent = 'desktop';
    /**
     * @var string
     */
    public $url = '';
    /**
     * @var bool
     */
    public $blockedRoute = false;
    /**
     * @var array
     */
    private $bootstrap = [];
    /**
     * @var bool
     */
    private $changeRoute = false;

    /**
     * @var bool
     */
    private $api = false;

    /**
     * @var array
     */
    private $home = ['htdocs', 'public_html', 'www'];

    /**
     * @var array
     */
    private $get;

    /**
     * @var array
     */
    private $post;

    /**
     * @var array
     */
    private $put;

    /**
     * @var array
     */
    private $patch;

    /**
     * @var array
     */
    private $delete;

    /**
     * @var array
     */
    private $files;

    /**
     * @var mixed
     */
    private $attachments;

    /**
     * @var string
     */
    private $newRoute = '';

    /**
     * @param array $server
     */
    public function __construct(array $server)
    {
        $this->server = $server;
        if ($this->server['HTTP_HOST'] === 'localhost') {
            $this->server['REMOTE_ADDR'] = '187.85.61.4';
            $this->erroExision = true;
        }
        $this->userAgent();
        return $this;
    }

    /**
     * @return Request
     */
    public function userAgent(): Request
    {
        if (stripos($this->server['HTTP_USER_AGENT'], 'Mobile') === false) {
            $this->userAgent = 'mobile';
        }
        return $this;
    }

    /**
     * @return Request
     */
    public function path_info(): Request
    {
        $url = $this->server['REQUEST_URI'];
        $this->base($url);
        if (stripos($url, '?') === false) {
            $this->url = parse_url(urldecode($url), PHP_URL_PATH);
        } else {
            $this->url = parse_url(urldecode(substr($url, 0, stripos($url, '?'))), PHP_URL_PATH);
        }
        if (isset($this->base)) {
            $this->url = substr($this->url, strlen($this->base));
        }
        $this->ajax = isset($this->server['HTTP_X_REQUESTED_WITH']) ? true : false;
        $url = stripos(substr($this->url, 1), DS) === false ? substr($this->url, 1) : $this->url;
        $number = stripos($url, DS);
        if ($number === 0) {
            $number = 1;
        }
        $url = $this->bootstrap['hash']->expanseDB($number !== false ? substr($url, $number) : $url);
        if (strlen($url) > 1) {
            if (strlen($url) > 7) {
                $url = explode(DS, $url);
                if (count($url) > 3) {
                    if (in_array($this->base, $url) !== false) {
                        unset($url[array_search($this->base, $url)]);
                    }
                }
                if ($url[0] === '') {
                    unset($url[0]);
                }
                $url = implode(DS, array_values($url));
            } else {
                if ($this->shorten) {
                    $url = '';
                }
            }
        }
        if ($url !== false) {
            $this->route = $url;
        }
        $this->checkReplaceRoute();
        return $this;
    }

    /**
     * @return Request
     */
    public function base(string $url): Request
    {
        $project = explode(DS, substr(ROOT, 0, -1));
        $numberHomeArray = $this->identifyHome($project);
        if (isset($project[$numberHomeArray + 1]) && stripos($url, $project[$numberHomeArray + 1]) !== false) {
            $this->base = DS . $project[$numberHomeArray + 1];
        }
        unset($project);
        return $this;
    }

    /**
     * @param array $project
     * @return false|int|string
     */
    public function identifyHome(array $project)
    {
        $numberHomeArray = 0;
        $count = count($this->home);
        for ($a = 0; $a < $count; $a++) {
            if (in_array($this->home[$a], $project) !== false) {
                $numberHomeArray = array_search($this->home[$a], $project);
                break;
            }
        }
        return $numberHomeArray;
    }

    /**
     * @return Request
     */
    private function checkReplaceRoute(): Request
    {
        if (isset($this->post)) {
            if (array_key_exists('control', $this->post) !== false && array_key_exists(
                    'action',
                    $this->post
                ) !== false) {
                if (stripos($this->route, $this->post['control']) !== false && stripos(
                        $this->route,
                        $this->post['action']
                    ) !== false) {
                    $this->changeRoute = !$this->changeRoute;
                }
                $keys = ['control', 'action'];
                foreach ($keys as $key) {
                    $this->newRoute = $this->post['key'];
                    if ($key === 'control') {
                        $this->newRoute .= '+';
                    }
                    unset($this->post[$key], $_POST[$key]);
                }
            }
        }
        return $this;
    }

    /**
     * @return Request
     */
    public function checkExistAPI(): Request
    {
        $routes = explode(DS, $this->route);
        if (!empty($this->base) && in_array($this->base, $routes)) {
            unset($routes[array_search($this->base, $routes)]);
        }
        if (in_array("api", $routes)) {
            $this->api = true;
            unset($routes[array_search("api", $routes)]);
            $this->route = implode(DS . $routes);
        }
        return $this;
    }

    /**
     * @return Request
     */
    public function methods(): Request
    {
        $this->bootstrap['security']->superGlobal();
        $this->get = $_GET ?? null;
        $this->post = $_POST ?? null;
        $this->put = $_PUT ?? null;
        $this->patch = $_PATCH ?? null;
        $this->delete = $_DELETE ?? null;
        if (isset($_FILES['attachments'])) {
            $this->attachments = $_FILES['attachments'];
            unset($_FILES['attachments']);
        }
        $this->files = $_FILES ?? null;
        $this->auth = $this->bootstrap['logged'];
        unset($this->bootstrap['logged']);
        return $this;
    }

    /**
     * @return Request
     */
    public function ativation(): Request
    {
        $this->ativations['paginator'] = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function bolleanApi(): bool
    {
        return $this->api;
    }

    /**
     * @param array $keys
     *
     * @return Request
     * @throws Exceptions
     */
    public function urlParamsDecrypt(array $keys = []): Request
    {
        if (count($this->params) > 0) {
            $keys = count($keys) > 0 ? array_merge($keys, ['page', 'id']) : ['page', 'id'];
            foreach ($this->params as $key => $param) {
                if (in_array($key, $keys) !== false) {
                    if ($this->bootstrap['security']->validBase64($param)) {
                        throw new Exceptions('Parameter passed is not properly configured.', 404);
                    }
                    $this->params[$key] = base64_decode($param);
                }
            }
        }
        return $this;
    }

    /**
     * @param string $name
     * @param object $object
     * @return $this
     */
    public function changeBootstrap(string $name, object $object): Request
    {
        $this->bootstrap[$name] = $object;
        return $this;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function bootstrap(string $name, bool $change = false)
    {
        return $this->bootstrap[$name];
    }

    /**
     * @return string
     */
    public function csrfPost(): string
    {
        if (isset($this->post['csrf'])) {
            $csrf = $this->post['csrf'];
            unset($this->post['csrf']);
            return $csrf;
        }
        return '';
    }

    /**
     * @param string|null $modo
     *
     * @return array|mixed
     */
    public function data(string $modo = null)
    {
        if (isset($modo)) {
            if ($modo === 'method') {
                return $this->server['REQUEST_METHOD'];
            } else {
                return $this->{$modo};
            }
        }
        return $this->post;
    }

    /**
     * @return Request
     */
    public function requestMethodGet(): Request
    {
        $this->renderCsrf = true;
        $this->server['REQUEST_METHOD'] = 'GET';
        return $this;
    }

    /**
     * @return Request
     */
    public function requestMethod(): Request
    {
        if (isset($this->post['_METHOD'])) {
            $method = $this->post['_METHOD'];
            unset($this->post['_METHOD']);
            if (is_array($method)) {
                $newMethod = $method[0];
                $method = '';
                $method = $newMethod;
                unset($mewMethod);
            }
            $this->server['REQUEST_METHOD'] = strtoupper($method);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function newRoute(): string
    {
        if ($this->changeRoute) {
            return $this->newRoute;
        }
        return '';
    }

    /**
     * @param array $bootstrap
     * @return $this
     */
    public function applicationBootstrap(array $bootstrap): Request
    {
        $this->bootstrap = $bootstrap;
        return $this;
    }

}
