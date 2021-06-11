<?php

namespace Restfull\Controller\Component;

use Restfull\Controller\Component;
use Restfull\Controller\Controller;
use Restfull\Error\Exceptions;
use Restfull\Network\Auth;

/**
 * Class AuthComponent
 * @package Restfull\Controller\Component
 */
class AuthComponent extends Component
{

    /**
     * @var string
     */
    private $redirectAjax = '';
    /**
     * @var Auth
     */
    private $auth;
    /**
     * @var array
     */
    private $pages = [];
    /**
     * @var array
     */
    private $config = [];
    /**
     * @var string
     */
    private $script = '';

    /**
     * AuthComponent constructor.
     * @param Controller $controller
     * @param array|null $config
     */
    public function __construct(Controller $controller, array $config = null)
    {
        parent::__construct($controller);
        $this->auth = $controller->request->auth;
        if (!isset($config['redirect']['unlogged'])) {
            $config['redirect']['unlogged'] = $this->controller->encryptLinks(
                    'main+logout',
                    3,
                    ['internal' => false, 'general' => false],
                    false
            );
        }
        if (!isset($config['redirect']['logging'])) {
            $config['redirect']['logging'] = $this->controller->encryptLinks(
                    'usuario+logando',
                    3,
                    ['internal' => false, 'general' => false],
                    false
            );
        }
        if (!isset($config['redirect']['logged'])) {
            $config['redirect']['logged'] = $this->controller->encryptLinks(
                    'main+home',
                    3,
                    ['internal' => false, 'general' => false],
                    false
            );
        }
        if (!isset($config['redirect']['action'])) {
            $config['redirect']['action'] = $this->controller->encryptLinks(
                    'usuario+login',
                    3,
                    ['internal' => false, 'general' => false],
                    false
            );
        }
        if (isset($config)) {
            $this->config = $config;
        }
        return $this;
    }

    /**
     * @param array $config
     * @return AuthComponent
     * @throws Exceptions
     */
    public function authenticity(array $config): AuthComponent
    {
        foreach (['authenticate'] as $key) {
            if (in_array($key, array_keys($config)) === false) {
                throw new Exceptions(
                        "The auth component\'s authentication must contain the following keys: authenticate.",
                        404
                );
            }
        }
        $pages = [
                'main' => [
                        substr(
                                $this->config['redirect']['unlogged'],
                                strripos($this->config['redirect']['unlogged'], DS) + 1
                        )
                ]
        ];
        if (isset($config['pages']) && count($config['pages']) > 0) {
            foreach ($config['pages'] as $key => $values) {
                for ($a = 0; $a < count($values); $a++) {
                    if (in_array($values[$a], $pages[$key]) === false) {
                        if (in_array($key, array_keys($pages)) !== false) {
                            $pages[$key] = array_merge($pages[$key], $values);
                        } else {
                            $pages[$key] = $values;
                        }
                    }
                }
            }
            if (!isset($config['pages']['main']) || in_array('index', $config['pages']['main']) === false) {
                $this->pages(['main' => ['index']]);
            }
            unset($config['pages']);
        }
        if (stripos($this->config['redirect']['logging'], 'usuario') !== false) {
            $pages = array_merge($pages, ['usuario' => ['logando']]);
        }
        $this->pages($pages);
        if (isset($config['cookie'])) {
            if ($this->auth->check(null, $config['cookie'])) {
                if ($this->controller->getUrl() == $this->config['redirect']['unlogged']) {
                    $this->controller->redirect($this->config['redirect']['logged']);
                }
            }
        }
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * @param array $pages
     * @return AuthComponent
     */
    public function pages(array $pages): AuthComponent
    {
        if (count($this->pages) > 0) {
            foreach ($pages as $key => $values) {
                for ($a = 0; $a < count($values); $a++) {
                    if (in_array($values[$a], $this->pages[$key]) !== false) {
                        unset($values[$a]);
                    }
                }
                sort($values);
                if (in_array($key, array_keys($this->pages)) !== false) {
                    $this->pages[$key] = array_merge($this->pages[$key], $values);
                } else {
                    $this->pages[$key] = $values;
                }
            }
        } else {
            $this->pages = $pages;
        }
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     * @throws Exceptions
     */
    public function authenticate(array $data): AuthComponent
    {
        $count = 2;
        foreach (array_keys($data) as $key) {
            if (array_key_exists($key, $this->config()['authenticate']) === false) {
                $count--;
            }
        }
        if ($coun != 2) {
            throw new Exceptions("One of the predetermined fields to login wasn't passed.", 404);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function config(): array
    {
        return $this->config;
    }

    /**
     * @param array $redirects
     * @return $this
     */
    public function redirectsPath(array $redirects): AuthComponent
    {
        if (isset($redirects['unlogged']) && $this->config['redirect']['unlogged'] != $redirects['unlogged']) {
            $this->config['redirect']['unlogged'] = $this->controller->encryptLinks(
                    $redirects['unlogged'],
                    3,
                    ['internal' => false, 'general' => false]
            );
        }
        if (isset($redirects['logging']) && $this->config['redirect']['logging'] != $redirects['logging']) {
            $this->config['redirect']['logging'] = $this->controller->encryptLinks(
                    $redirects['logging'],
                    3,
                    ['internal' => false, 'general' => false]
            );
        }
        if (isset($redirects['logged']) && $this->config['redirect']['logged'] != $redirects['logged']) {
            $this->config['redirect']['logged'] = $this->controller->encryptLinks(
                    $redirects['logged'],
                    3,
                    ['internal' => false, 'general' => false]
            );
        }
        if (isset($redirects['action'])) {
            $this->config['redirect']['action'] = $this->controller->encryptLinks(
                    $redirects['action'],
                    3,
                    ['internal' => false, 'general' => false],
            );
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function pageAutorized(): bool
    {
        $result = '';
        $resp = false;
        $route = explode(DS, $this->controller->getUrl());
        if (count($route) > 2) {
            array_pop($route);
        }
        $route = implode(DS, $route);
        if (in_array(
                        $route,
                        [
                                $this->config['redirect']['unlogged'],
                                $this->config['redirect']['logging'],
                                $this->config['redirect']['action']
                        ]
                ) === false) {
            $keys = array_keys($this->pages);
            for ($a = 0; $a < count($keys); $a++) {
                if (in_array(substr($route, stripos($route, DS) + 1), $this->pages[$keys[$a]]) === false) {
                    $result = '1';
                    break;
                }
            }
            if (!empty($result)) {
                $resp = !$resp;
            }
        }
        if ($this->validateLogged()) {
            if (in_array(
                            $route,
                            [
                                    $this->config['redirect']['unlogged'],
                                    $this->config['redirect']['logging'],
                                    $this->config['redirect']['action']
                            ]
                    ) === false) {
                $resp = !$resp;
            }
        }
        return $resp;
    }

    /**
     * @return bool
     */
    public function validateLogged(): bool
    {
        if (isset($this->config['cookie'])) {
            return $this->auth->check('user', $this->config['cookie']);
        }
        return $this->auth->check('user');
    }

    /**
     * @param int|null $code
     * @return mixed
     */
    public function twoSteps(int $code = null)
    {
        return $this->auth->twoSteps($code);
    }

    /**
     * @return AuthComponent
     */
    public function logout(): AuthComponent
    {
        if ($this->checkCookieSet('user')) {
            $this->auth->destroy('user', array_merge(['valid' => true], $this->config['cookie']));
        } else {
            $this->auth->destroy('user', ['valid' => false]);
        }
        return $this;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function checkCookieSet(string $key): bool
    {
        if (count($this->auth->getCookie($key, true, true)) > 2) {
            return true;
        }
        return false;
    }

    /**
     * @param array $values
     * @param array|null $cookie
     * @return AuthComponent
     */
    public function login(
            array $values,
            array $cookie = null,
            bool $redirect = true,
            array $encrypt = ['number' => 3, 'encrypt' => ['internal' => false, 'general' => true]]
    ): AuthComponent {
        if (isset($cookie)) {
            $cookie['key'] = 'user';
            $cookie = array_merge(['valid' => true], $cookie);
        } else {
            $cookie = ['valid' => false];
        }
        $this->auth->write('user', $values, $cookie);
        if ($redirect) {
            $this->controller->redirect(
                    $this->controller->encryptLinks(
                            $this->config['redirect']['logged'],
                            $encrypt['number'],
                            $encrypt['encrypt']
                    )
            );
        } else {
            if ($this->request->ajax) {
                $this->redirectAjax = $this->config['redirect']['logged'];
            }
        }
        return $this;
    }

    /**
     * @param string $route
     * @return string
     */
    public function loggedRedirect(string $route): string
    {
        if (substr(
                        $this->request->route,
                        0,
                        strripos($this->request->route, DS)
                ) === $this->config['redirect']['logging']) {
            if (!empty($this->redirectAjax)) {
                return $this->redirectAjax;
            }
            return $this->config['redirect']['logged'];
        }
        return $route;
    }

    /**
     * @return bool
     */
    public function checkAuth(): bool
    {
        return count($this->auth->getAuth('user')) > 0 ? true : false;
    }

    /**
     * @return AuthComponent
     */
    public function keyAuth(): AuthComponent
    {
        $this->auth->keys('user');
        return $this;
    }

    /**
     * @param string|null $key
     * @param string|null $cookie
     * @return array|int|mixed
     */
    public function getData(string $key = null, string $cookie = null)
    {
        if ($this->auth->check('user')) {
            $data = $this->auth->getData();
            if (count($data) == 0) {
                $cookie = is_null($cookie) ? ['valid' => false] : ['valid' => true, 'key' => $cookie];
                $this->auth->setAuth($cookie);
                $data = $this->auth->getData();
            }
            if (isset($key)) {
                if (empty($data)) {
                    return 0;
                }
                return $data[$key];
            }
            return $data;
        }
        return [];
    }

    /**
     * @param string $key
     * @param string $subkey
     * @return mixed|string
     */
    public function orderOrPageAuth(string $key, string $subkey = 'page')
    {
        if ($this->auth->check($key)) {
            $data = $this->auth->getAuth($key);
        }
        if ($subkey == 'page') {
            return '1';
        }
        if (isset($data[$this->controller->name][$subkey])) {
            return $data[$this->controller->name][$subkey];
        }
        return '';
    }

    /**
     * @param string $key
     * @param array $values
     * @return mixed
     */
    public function postAuth(string $key, array $values)
    {
        if (in_array($this->request->action, ['listar', 'countList']) !== false) {
            if ($this->auth->check($key)) {
                $data = $this->auth->getAuth($key);
                if ($this->request->controller == array_keys($data)[0]) {
                    if ($this->controller->request->ativations['paginator']) {
                        $values[$this->request->controller]['limite'] = $data[$this->request->controller]['limite'];
                    }
                    if (count($values[$this->request->controller]) <= count($data[$this->request->controller])) {
                        if (count($values[$this->request->controller]) == count($data[$this->request->controller])) {
                            foreach ($values[$this->request->controller] as $newKey => $value) {
                                if ($data[$this->request->controller] != $value) {
                                    $data[$this->request->controller][$newKey] = $value;
                                }
                            }
                        }
                        $this->auth->write($key, $data);
                        return $data[$this->request->controller];
                    } elseif (count($values[$this->request->controller]) > count($data[$this->request->controller])) {
                        $this->auth->write($key, $values);
                        return $values[$this->request->controller];
                    }
                } else {
                    $this->checkKeyIsDestroy($key);
                }
            }
        }
        $this->auth->write($key, $values);
        return $values[$this->request->controller];
    }

    /**
     * @param string $key
     * @return AuthComponent
     */
    public function checkKeyIsDestroy(string $key): AuthComponent
    {
        if ($this->auth->check($key)) {
            if ($this->checkCookieSet($key)) {
                $this->auth->destroy($key, array_merge(['valid' => true], $this->config['cookie']));
            } else {
                $this->auth->destroy($key, ['valid' => false]);
            }
        }
        return $this;
    }

    /**
     * @param string $key
     * @param array $data
     * @return AuthComponent
     */
    public function writeOuther(string $key, array $data)
    {
        $this->auth->write($key, $data);
        return $this;
    }

    /**
     * @return mixed
     */
    public function connectAuth()
    {
        if ($this->auth->check('connexion')) {
            $data = $this->auth->getAuth('connexion');
        }
        return $data[$key];
    }

    /**
     * @return AuthComponent
     */
    public function script(): AuthComponent
    {
        $script = '';
        if ($this->validateLogged() && !$this->checkCookieSet('user')) {
            $script = 'setTimeout(function () {location.reload();}, 2100000);';
        }
        $this->controller->set('scriptTime', $script);
        return $this;
    }
}