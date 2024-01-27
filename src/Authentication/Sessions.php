<?php

declare(strict_types=1);

namespace Restfull\Authentication;

/**
 *
 */
class Sessions
{

    /**
     * @var string
     */
    public $key = '';

    /**
     * @var array
     */
    private $keys = [];

    /**
     * @var array
     */
    private $sessions = [];

    /**
     *
     */
    public function __construct()
    {
        if (empty(session_id())) {
            session_start();
        }
        $this->sessions = $_SESSION;
        //session_destroy();
        return $this;
    }

    /**
     * @return Sessions
     */
    public function sessionExpired(): Sessions
    {
        if (isset($this->sessions['time'])) {
            if ($this->sessions['time'] < strtotime(date("H:i:s"))) {
                $this->key = 'user';
                if ($this->validety()) {
                    $this->destroy();
                }
            }
        }
        $_SESSION['time'] = $this->sessions['time'] = strtotime(
            date("Y-m-d H:i:s", strtotime("+" . ini_get('session.gc_maxlifetime') . " seconds"))
        );
        $this->keys();
        return $this;
    }

    /**
     * @return bool
     */
    public function validety(): bool
    {
        return isset($this->sessions[$this->key]) && !empty($this->sessions[$this->key]);
    }

    /**
     * @return Sessions
     */
    public function destroy(): Sessions
    {
        if (isset($this->sessions[$this->key])) {
            unset($this->sessions[$this->key], $_SESSION[$this->key]);
        }
        if (count($_SESSION) === 0) {
            session_destroy();
        }
        return $this;
    }

    /**
     * @return Sessions
     */
    private function keys(): Sessions
    {
        if (count($this->sessions) > 0) {
            $this->keys = array_keys($this->sessions);
        }
        return $this;
    }

    /**
     * @param int $time
     *
     * @return Sessions
     */
    public function changeTimeExpire(int $time): Sessions
    {
        if ($this->sessions['time'] != $time) {
            $this->sessions['time'] = $time;
        }
        return $this;
    }

    /**
     * @param string|null $key
     *
     * @return mixed
     */
    public function key(string $key = null)
    {
        if (isset($key)) {
            if (in_array($key, $this->keys) === false) {
                $this->key = $key;
            }
            if ($this->key !== $key) {
                $this->key = $key;
            }
            return $this;
        }
        if (count($this->sessions) > 0) {
            return array_keys($this->sessions);
        }
        return [];
    }

    /**
     * @param array $value
     *
     * @return Sessions
     */
    public function write(array $value): Sessions
    {
        $_SESSION[$this->key] = $value;
        $this->sessions[$this->key] = $value;
        return $this;
    }

    /**
     * @return array|mixed
     */
    public function get()
    {
        if (isset($this->sessions[$this->key])) {
            return $this->sessions[$this->key];
        }
        return [];
    }

    /**
     *
     */
    public function __destruct()
    {
        unset($this->key);
    }

}
