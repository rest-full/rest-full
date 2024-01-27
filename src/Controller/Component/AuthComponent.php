<?php

declare(strict_types=1);

namespace Restfull\Controller\Component;

use Restfull\Controller\Component;
use Restfull\Controller\Controller;
use Restfull\Error\Exceptions;
use Restfull\Network\Auth;
use Restfull\Security\Hash;

/**
 *
 */
class AuthComponent extends Component
{

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var Hash
     */
    private $hash;

    /**
     * @var array
     */
    private $pages = [];

    /**
     * @var array
     */
    private $config = [];

    /**
     * @param Controller $controller
     */
    public function __construct(Controller $controller)
    {
        parent::__construct($controller);
        $this->auth = $controller->request->bootstrap('auth');
        $this->hash = $this->request->bootstrap('hash');
        return $this;
    }

    /**
     * @param array $config
     *
     * @return AuthComponent
     * @throws Exceptions
     */
    public function authenticity(array $config): AuthComponent
    {
        $this->redirectsPath(
            $config['redirect'] ?? [
            'unlogged' => 'usuario+logout',
            'logging' => 'usuario+logging',
            'logged' => 'main+dashboard',
            'action' => 'main+login'
        ],
            $config['internal'] ?? true
        );
        if (isset($config['redirect'])) {
            unset($config['redirect']);
        }
        if (isset($config['internal'])) {
            unset($config['internal']);
        }
        foreach (['authenticate'] as $key) {
            if (in_array($key, array_keys($config)) === false) {
                throw new Exceptions(
                    "The auth component\'s authentication must contain the following keys: authenticate.", 404
                );
            }
        }
        if (isset($config['pages']) && count($config['pages']) > 0) {
            foreach ($config['pages'] as $key => $values) {
                $count = count($values);;
                for ($a = 0; $a < $count; $a++) {
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
        if (isset($pages)) {
            $this->pages($pages);
        }
        if (count($this->auth->getCookie(true)) > 2) {
            foreach ($this->auth->getCookie() as $key => $value) {
                if (in_array($key, $config['cookie']) !== false && $config['cookie'][$key] !== $value) {
                    $config['cookie'][$key] = $value;
                } elseif (in_array($key, $config['cookie']) === false) {
                    $config['cookie'][$key] = $value;
                }
            }
        }
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * @param array $redirects
     *
     * @return AuthComponent
     * @throws Exceptions
     */
    public function redirectsPath(array $redirects, bool $internal = true): AuthComponent
    {
        foreach (['unlogged', 'logging', 'logged', 'action'] as $key) {
            if (!isset($redirects[$key])) {
                throw new Exceptions('There are none of these keys: unlogged, logging, logged or action', 404);
            }
        }
        $this->hash->changeConfig(0);
        $this->config['redirect']['unlogged'] = DS . $this->controller->encryptLinks($redirects['unlogged'], 'app');
        $this->config['redirect']['logging'] = DS . $this->controller->encryptLinks($redirects['logging'], 'app');
        $this->config['redirect']['action'] = DS . $this->controller->encryptLinks($redirects['action'], 'app');
        $this->hash->changeConfig(3);
        $this->config['redirect']['logged'] = DS . $this->controller->encrypted(
                $this->controller->encryptLinks($redirects['logged'], 'app')
            );
        return $this;
    }

    /**
     * @param array $pages
     *
     * @return AuthComponent
     */
    public function pages(array $pages): AuthComponent
    {
        if (count($this->pages) > 0) {
            foreach ($pages as $key => $values) {
                $count = count($values);
                for ($a = 0; $a < $count; $a++) {
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
     * @return array
     */
    public function config(): array
    {
        return $this->config;
    }

    /**
     * @param bool $auth
     *
     * @return bool
     */
    public function pageAction(bool $auth): bool
    {
        $result = false;
        $route = $this->controller->getUrl();
        $result = $this->validPage($route, $result);
        if ($auth) {
            return $this->validPage($route, $result);
        }
        return $result;
    }

    /**
     * @param string $route
     * @param bool $result
     *
     * @return bool
     */
    private function validPage(string $route, bool $result): bool
    {
        $route = explode(DS, $route);
        if (count($route) > 2) {
            array_pop($route);
            if ($this->request->prefix != 'app') {
                array_shift($route);
            }
        }
        $route = implode(DS, $route);
        $redirects = $this->config['redirect'];
        array_pop($redirects);
        if (in_array($route, $redirects) === false) {
            if (in_array($this->request->action, $this->pagesNotAuth($this->request->controller)) === false) {
                $result = !$result;
            }
        }
        return $result;
    }

    /**
     * @param string $control
     *
     * @return array
     */
    public function pagesNotAuth(string $control): array
    {
        foreach (array_keys($this->pages) as $key) {
            if (strtolower($control) === $key) {
                $pages = $this->pages[$key];
                break;
            }
        }
        if (!isset($pages) && $control !== 'Main') {
            return $this->pagesNotAuth('Main');
        }
        return $pages ?? [];
    }

    /**
     * @param int|null $code
     *
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
        if ($this->checkCookieSet('user') !== 0) {
            $user = $this->getData();
            if (count($user) > 0) {
                $this->hash->updateSecurity()->deleteByTimeOrIdentifier([], false, $user['id']);
            }
            if (count($this->auth->getCookie(true)) > 2) {
                $this->auth->destroy('user', array_merge(['valid' => true], $this->config['cookie']));
            } else {
                $this->auth->destroy('user', ['valid' => false]);
            }
            return $this;
        }
        $this->auth->destroy('user', ['valid' => false]);
        return $this;
    }

    /**
     * @return bool
     */
    public function checkCookieSet(string $key): bool
    {
        $this->auth->key($key, 'cookie');
        $cookies = $this->auth->getCookie(true);
        if (count($cookies) > 2) {
            return array_key_exists($key, $cookies);
        }
        return false;
    }

    /**
     * @param string|null $key
     *
     * @return array|int|mixed
     */
    public function getData(string $key = null)
    {
        if ($this->auth->check('user')) {
            $datas = $this->auth->getData();
            if (count($datas) === 0) {
                $this->auth->setAuth('user');
                $datas = $this->auth->getData();
            }
            if (empty($datas)) {
                return 0;
            }
            if (!is_null($key)) {
                if (!isset($datas[$key])) {
                    return '';
                }
                return $datas[$key];
            }
            return $datas;
        }
        return [];
    }

    /**
     * @return bool
     */
    public function check(): bool
    {
        return $this->auth->check('user');
    }

    /**
     * @param array $values
     * @param array|false[] $cookie
     *
     * @return AuthComponent
     */
    public function login(array $values, array $cookie = ['valid' => false]): AuthComponent
    {
        $this->config['cookie'] = array_merge($this->config['cookie'], $cookie);
        $this->auth->write('user', $values, $this->config['cookie']);
        $this->controller->redirect($this->config['redirect']['logged']);
        $this->hash->updateSecurity('connect');
        return $this;
    }

    /**
     * @param string $route
     *
     * @return string
     */
    public function loggedRedirect(string $route): string
    {
        if (substr(
                $this->request->route,
                0,
                strripos($this->request->route, DS)
            ) === $this->config['redirect']['logging']) {
            if ($route === $this->config['redirect']['logged'] && $this->hash->checkShortenDB(
                    $route,
                    $this->getData('id')
                )) {
                $route = $this->redirectControl($this->config['redirect']['logged']);
            }
        }
        return $route;
    }

    /**
     * @param string $url
     *
     * @return string
     * @throws Exceptions
     */
    private function redirectControl(string $url): string
    {
        if ($this->controller->encrypted && $this->hash->valideDecrypt($url)->validationResult()) {
            return $url;
        }
        return $this->controller->encryptLinks($url, '');
    }

    /**
     * @param string $key
     * @param string $subkey
     *
     * @return string
     */
    public function orderOrPageAuth(string $key, string $subkey = 'page'): string
    {
        if ($this->auth->check($key)) {
            $datas = $this->auth->getAuth($key);
        }
        if (isset($datas[$this->controller->name][$subkey])) {
            return $datas[$this->controller->name][$subkey];
        }
        if ($subkey === 'page') {
            return '1';
        }
        return '';
    }

    /**
     * @param string $key
     * @param array $values
     *
     * @return array
     */
    public function postAuth(string $key, array $values): array
    {
        if (in_array($this->request->action, ['listar', 'countList']) !== false) {
            if ($this->auth->check($key)) {
                $datas = $this->auth->getAuth($key);
                if ($this->request->controller === array_keys($datas)[0]) {
                    if ($this->controller->request->ativations['paginator']) {
                        $values[$this->request->controller]['limite']
                            = $datas[$this->request->controller]['limite'];
                    }
                    if (count($values[$this->request->controller]) <= count($datas[$this->request->controller])) {
                        if (count($values[$this->request->controller]) < count($datas[$this->request->controller])) {
                            foreach (array_keys($datas[$this->request->controller]) as $newKey) {
                                if (!isset($values[$this->request->controller][$newKey])) {
                                    unset($data[$this->request->controller][$newKey]);
                                } elseif ($data[$this->request->controller][$newKey] != $values[$this->request->controller][$newKey]) {
                                    $data[$this->request->controller][$newKey] = $values[$this->request->controller][$newKey];
                                }
                            }
                        } else {
                            foreach ($values[$this->request->controller] as $newKey => $value) {
                                if ($datas[$this->request->controller] != $value) {
                                    $datas[$this->request->controller][$newKey] = $value;
                                }
                            }
                        }
                        $this->auth->write($key, $datas);
                        return $datas[$this->request->controller];
                    } elseif (count($values[$this->request->controller]) > count($datas[$this->request->controller])) {
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
     * @param string|null $key
     *
     * @return AuthComponent
     */
    public function checkKeyIsDestroy(string $key = null): AuthComponent
    {
        if (is_null($key)) {
            $key = 'user';
        }
        if ($this->auth->check($key)) {
            if ($this->checkCookieSet($key)) {
                $this->config['cookie']['valid'] = !$this->auth->getCookie()['valid'];
            }
            $this->auth->destroy($key, $this->config['cookie']);
        }
        return $this;
    }

    /**
     * @param string $key
     * @param array $datas
     *
     * @return AuthComponent
     */
    public function writeOuther(string $key, array $datas, bool $force = false): AuthComponent
    {
        if (!$force) {
            $counts = 0;
            foreach (array_keys($datas) as $subkey) {
                if (!$this->checkOuther($key, $subkey)) {
                    $counts++;
                }
            }
            if ($counts === 0) {
                $this->auth->write($key, $datas);
            }
            return $this;
        }
        $this->auth->write($key, $datas);
        return $this;
    }

    /**
     * @param string $key
     * @param string|null $subkey
     *
     * @return bool
     */
    public function checkOuther(string $key, string $subkey = null): bool
    {
        if (!isset($subkey)) {
            return $this->auth->check($key);
        }
        $values = $this->auth->getSession($key);
        return !isset($values[$subkey]) && empty($values[$subkey]);
    }

    /**
     * @return bool
     */
    public function checkAuth(): bool
    {
        if ($this->auth->getAuth('token')) {
            return true;
        }
        return false;
    }

    /**
     * @param string $key
     * @param bool $delete
     *
     * @return array
     */
    public function outherData(string $key, bool $delete = false): array
    {
        $datas = $this->auth->getAuth($key);
        if ($delete) {
            $this->auth->destroy($key);
        }
        return $datas;
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

    /**
     * @return bool
     */
    public function validateLogged(): bool
    {
        return $this->auth->setAuth('user')->check();
    }

}
