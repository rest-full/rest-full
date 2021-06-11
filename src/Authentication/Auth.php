<?php

namespace Restfull\Authentication;

use Restfull\Core\Instances;
use Restfull\Error\Exceptions;

/**
 * Class Auth
 * @package Restfull\Authentication
 */
class Auth
{

    /**
     * @var array
     */
    private $auth = [];

    /**
     * @var Sessions
     */
    private $session;

    /**
     * @var Cookies
     */
    private $cookie;

    /**
     * Auth constructor.
     * @throws Exceptions
     */
    public function __construct()
    {
        $instance = new Instances();
        $this->session = $instance->resolveClass(
                $instance->namespaceClass(
                        "%s" . DS_REVERSE . "Authentication" . DS_REVERSE . "Sessions",
                        [ROOT_NAMESPACE]
                )
        );
        $this->session->sessionExpired();
        $this->cookie = $instance->resolveClass(
                $instance->namespaceClass(
                        "%s" . DS_REVERSE . "Authentication" . DS_REVERSE . "Cookies",
                        [ROOT_NAMESPACE]
                )
        );
        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        if (isset($this->auth)) {
            return $this->auth;
        }
        return [];
    }

    /**
     * @param string $key
     * @return array
     */
    public function getSession(string $key): array
    {
        $this->session->key = $key;
        if ($this->session->validety()) {
            return $this->session->get();
        }
        return [];
    }

    /**
     * @param string|null $key
     * @return bool
     */
    public function check(string $key = null): bool
    {
        if (!is_null($key)) {
            $this->session->key = $key;
            return $this->session->validety();
        }
        $this->setAuth(['valid' => false]);
        return empty($this->auth) ? false : true;
    }

    /**
     * @param string $key
     * @return Auth
     */
    public function newAuth(string $key): Auth
    {
        $session = $this->getCookie($key);
        if($session instanceof Sessions) {
            $this->session = $session;
        }
        $this->auth = $this->getAuth();
        return $this;
    }

    /**
     * @param string $identify
     * @param bool $cookie
     * @param bool $count
     * @return array
     */
    public function getCookie(string $identify, bool $cookie = false, bool $count = false): array
    {
        if (!in_array($identify, $this->cookie->keys())) {
            $this->cookie->key = $identify;
        } else {
            $this->keys($identify, 'cookie');
        }
        if ($cookie) {
            if ($count) {
                return $this->cookie->keys();
            }
            return [];
        }
        return $this->cookie->get();
    }

    /**
     * @param string $key
     * @param bool $valid
     * @param string $method
     * @return Auth|bool
     */
    public function keys(string $key, bool $valid = false, string $method = 'session')
    {
        if (!isset($this->$method->key)) {
            $this->$method->key = $key;
        } elseif ($this->$method->key != $key) {
            $this->$method->key = $key;
        }
        if ($method == 'session' && $valid) {
            return $this->session->validety();
        }
        return $this;
    }

    /**
     * @param string|null $identify
     * @return array
     */
    public function getAuth(string $identify = null): array
    {
        if (isset($identify)) {
            if (!in_array($identify, $this->session->keys())) {
                $this->session->key = $identify;
            } else {
                $this->keys($identify);
            }
        }
        return $this->session->get();
    }

    /**
     * @return Auth
     */
    public function setAuth(): Auth
    {
        if ($this->session->validety()) {
            $this->auth = isset($this->auth) ? array_merge(
                    $this->auth,
                    $this->getAuth('user')
            ) : $this->getAuth('user');
            return $this;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function fecthTheCookieKey(): string
    {
        $keys = $this->cookie->keys();
        $key = '';
        for ($a = 0; $a < count($keys); $a++) {
            if ($keys[$a] != 'PHPSESSID') {
                $key = $keys[$a];
                break;
            }
        }
        return $key;
    }

    /**
     * @param string $key
     * @return int
     */
    public function counts(string $key): int
    {
        if (isset($this->auth)) {
            return count($this->auth[$key]);
        }
        return 0;
    }

    /**
     * @param string $key
     * @param array $value
     * @param array|false[] $cookie
     * @return Auth
     */
    public function write(string $key, array $value, array $cookie = ['valid' => false]): Auth
    {
        if (in_array($key, $this->session->keys()) === false) {
            $this->session->key = $key;
        }
        $this->session->write($value);
        if ($cookie['valid']) {
            if (!isset($cookie['value'])) {
                $cookie['value'] = $this->session;
            }
            unset($cookie['valid']);
            $this->cookie->key = $cookie['key'];
            unset($cookie['key']);
            $this->cookie->write($cookie['value'], $cookie['time']);
        }
        return $this;
    }

    /**
     * @param string $session
     * @param array $cookie
     * @return Auth
     */
    public function destroy(string $session, array $cookie = []): Auth
    {
        $this->session->key = $session;
        $this->session->destroy();
        if ($cookie['valid']) {
            unset($cookie['valid']);
            $this->cookie->key = $cookie['key'];
            unset($cookie['key']);
            $this->cookie->destroy();
        }
        return $this;
    }

    /**
     * @param int|null $code
     * @return mixed
     */
    public function twoSteps(int $code = null)
    {
        if (isset($code)) {
            return $this->twoSteps->validateCode($code);
        }
        return $this->twoSteps->getQrcode();
    }

    /**
     *
     */
    public function __destruct()
    {
        unset($this->session->key);
        return $this;
    }

}
