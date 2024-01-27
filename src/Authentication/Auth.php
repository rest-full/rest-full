<?php

declare(strict_types=1);

namespace Restfull\Authentication;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;

/**
 *
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
     * @param Instances $instance
     * @throws Exceptions
     */
    public function __construct(Instances $instance)
    {
        $this->session = $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Authentication' . DS_REVERSE . 'Sessions'
        );
        $this->session->sessionExpired();
        $this->cookie = $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Authentication' . DS_REVERSE . 'Cookies'
        );
        return $this;
    }

    /**
     * @param string $key
     * @return array
     */
    public function getData(string $key = ''): array
    {
        if (!empty($key)) {
            $this->session->key = $key;
            return $this->session->get();
        }
        if (isset($this->auth)) {
            return $this->auth;
        }
        return [];
    }

    /**
     * @param string $key
     * @return array
     */
    public function getSession(string $key = 'user'): array
    {
        $this->session->key($key);
        if ($this->session->validety()) {
            return $this->session->get();
        }
        return [];
    }

    /**
     * @param string $key
     * @param string $object
     * @return Auth
     */
    public function key(string $key, string $object): Auth
    {
        if ($object === 'cookie') {
            if (in_array($key, $this->cookie->keys()) !== false) {
                $this->cookie->key = $key;
            }
        } else {
            if (in_array($key, $this->session->key()) !== false) {
                $this->session->key = $key;
            }
        }
        return $this;
    }

    /**
     * @param string|null $key
     * @return bool
     */
    public function check(string $key = null): bool
    {
        if (!is_null($key)) {
            $this->session->key($key);
            return $this->session->validety();
        }
        $this->setAuth('user');
        return empty($this->auth) ? false : true;
    }

    /**
     * @param bool $count
     * @return array
     */
    public function getCookie(bool $count = false): array
    {
        if ($count) {
            return $this->cookie->keys();
        }
        if (isset($this->session->key('cookie')->get()['key'])) {
            return $this->cookie->keys($this->session->key('cookie')->get()['key'])->get();
        }
        return [];
    }

    /**
     * @param string $identify
     * @return array
     */
    public function getAuth(string $identify): array
    {
        return $this->session->key($identify)->get();
    }

    /**
     * @param string $key
     * @return Auth
     */
    public function setAuth(string $key): Auth
    {
        if ($this->validCookie()) {
            $this->cookie->keys($key);
            if ($this->cookie->check()) {
                $session = $this->cookie->get();
                if ($session instanceof Sessions) {
                    $this->session = $session;
                }
                $this->auth = $this->getAuth($key);
                return $this;
            }
        }
        $this->auth = $this->getAuth($key);
        return $this;
    }

    /**
     * @return bool
     */
    public function validCookie(): bool
    {
        if (isset($this->session->key('cookie')->get()['valid'])) {
            return $this->session->key('cookie')->get()['valid'] !== true;
        }
        return false;
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
     * @param array $cookie
     * @return Auth
     */
    public function write(string $key, array $value, array $cookie = ['valid' => false]): Auth
    {
        $this->session->key($key);
        $this->session->write($value);
        if ($cookie['valid']) {
            $this->session->changeTimeExpire($cookie['time']);
            if (!isset($cookie['value'])) {
                $cookie['value'] = $this->session;
            }
            $this->cookie->keys($cookie['key']);
            $this->cookie->write($cookie['value'], $cookie['time']);
        }
        $this->session->key('cookie');
        $this->session->write(['valid' => $cookie['valid']]);
        return $this;
    }

    /**
     * @param string $session
     * @param array $cookie
     * @return Auth
     */
    public function destroy(string $session, array $cookie = []): Auth
    {
        $this->session->key($session);
        $this->session->destroy();
        if ($cookie['valid']) {
            unset($cookie['valid']);
            $this->cookie->keys($cookie['key']);
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
