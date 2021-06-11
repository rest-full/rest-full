<?php

namespace Restfull\Http;

use Restfull\Authentication\Auth;
use Restfull\Error\Exceptions;

/**
 * Class Request
 * @package Restfull\Http
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
     * @var array
     */
    public $server = [];

    /**
     * @var bool
     */
    public $erroExision = false;

    /**
     * @var array
     */
    public $bootstrap = [];

    /**
     * @var Auth
     */
    public $auth;
    /**
     * @var string
     */
    public $prefix;
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
    private $files;
    /**
     * @var mixed
     */
    private $attachments;

    /**
     * Request constructor.
     * @param array $server
     */
    public function __construct(array $server)
    {
        $this->server = $server;
        if ($this->server['HTTP_HOST'] == 'localhost') {
            $this->erroExision = true;
        }
        return $this;
    }

    /**
     * @return Request
     */
    public function path_info(): Request
    {
        $this->base();
        $url = parse_url(urldecode($_SERVER['REQUEST_URI']), PHP_URL_PATH);
        if (isset($this->base)) {
            $url = substr($url, strlen($this->base));
        }
        $this->ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? true : false;
        if ($url == DS) {
            $this->route = "main" . DS . "index";
        } else {
            if (stripos(substr($url, 1), DS) === false) {
                $url = substr($url, 1);
                if ($this->bootstrap['security']->valideDecryptBase64($url)) {
                    $url = $this->bootstrap['security']->decrypt($url, 3, 'file');
                }
            }
            if (strlen($url) > 1) {
                $url = explode(DS, $url);
                if (count($url) > 3) {
                    if (in_array($this->base, $url)) {
                        unset($url[array_search($this->base, $url)]);
                    }
                }
                if ($url[0] == '') {
                    unset($url[0]);
                }
                $url = implode(DS, array_values($url));
            }
            $this->route = $url;
        }
        return $this;
    }

    /**
     * @return Request
     */
    public function base(): Request
    {
        $project = explode(DIRECTORY_SEPARATOR, substr(ROOT, 0, -1));
        for ($a = 0; $a < count($this->home); $a++) {
            if (in_array($this->home[$a], $project)) {
                $numberHomeArray = array_search($this->home[$a], $project);
            }
        }
        $this->base = (count($project) - 1) == $numberHomeArray ? '' : DS . $project[$numberHomeArray + 1];
        unset($project);
        return $this;
    }

    /**
     * @return Request
     */
    public function checkExistAPI(): Request
    {
        $url = explode(DS, $this->route);
        if (in_array($this->base, $url)) {
            unset($url[array_search($this->base, $url)]);
        }
        foreach ($url as $route) {
            $routes[] = $route;
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
        $this->post = $_POST ?? null;
        $this->get = $_GET ?? null;
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
     * @return Request
     * @throws Exceptions
     */
    public function urlDecrypt(array $keys = []): Request
    {
        if (count($this->params) > 0) {
            $keys = count($keys) > 0 ? array_merge($keys, ['page', 'id']) : ['page', 'id'];
            foreach ($this->params as $key => $param) {
                if (in_array($key, $keys) !== false) {
                    if ($this->bootstrap['security']->valideDecryptBase64($param)) {
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
     * @return object
     */
    public function bootstrap(string $name): object
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
     * @return array|mixed
     */
    public function data(string $modo = null)
    {
        if (isset($modo)) {
            if ($modo == 'method') {
                return $this->server['REQUEST_METHOD'];
            } else {
                return $this->$modo;
            }
        }
        return $this->post;
    }

}
