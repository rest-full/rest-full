<?php

namespace Restfull\Authentication;

/**
 * Class Cookies
 * @package Restfull\Authentication
 */
class Cookies
{

    /**
     * @var string
     */
    public $key = '';

    /**
     * @var array
     */
    private $cookies = [];

    /**
     * Cookies constructor.
     */
    public function __construct()
    {
        $this->cookies = $_COOKIE;
        return $this;
    }

    /**
     * @return array
     */
    public function keys(): array
    {
        if (count($this->cookies) > 0) {
            if (in_array('XDEBUG_SESSION', array_keys($this->cookies)) !== false) {
                unset($this->cookies['XDEBUG_SESSION']);
            }
            return array_keys($this->cookies);
        }
        return [];
    }

    /**
     * @return bool
     */
    public function check(): bool
    {
        return (isset($this->cookies[$this->key])) ? true : false;
    }

    /**
     * @param $value
     * @param int $time
     * @return Cookies
     */
    public function write($value, int $time): Cookies
    {
        setcookie($this->key, serialize($value), $time, "/");
        if (isset($_COOKIE[$this->key])) {
            $this->cookies[$this->key] = $_COOKIE[$this->key];
        }
        return $this;
    }

    /**
     * @return array
     */
    public function get(): array
    {
        if (isset($this->cookies[$this->key])) {
            return unserialize($this->cookies[$this->key]);
        }
        return [];
    }

    /**
     * @return bool
     */
    public function checking(): bool
    {
        $min = in_array('XDEBUG_SESSION', array_keys($this->cookies)) ? 2 : 1;
        return count($this->cookies) > $min ? true : false;
    }

    /**
     * @return Cookies
     */
    public function destroy(): Cookies
    {
        if (isset($this->cookies[$this->key])) {
            unset($this->cookies[$this->key], $_COOKIE[$this->key]);
            @setcookie($this->key, '', time() - 3600, "/");
        }
        return $this;
    }

}