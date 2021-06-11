<?php

namespace Restfull\Authentication;

/**
 * Class Sessions
 * @package Restfull\Authentication
 */
class Sessions
{

    /**
     * @var string
     */
    public $key = '';

    /**
     * @var string
     */
    private $timeExpired = '';

    /**
     * @var array
     */
    private $sessions = [];

    /**
     * Sessions constructor.
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
                date(
                        "Y-m-d H:i:s",
                        strtotime("+" . ini_get('session.gc_maxlifetime') . " seconds")
                )
        );
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
        if (count($_SESSION) == 0) {
            session_destroy();
        }
        return $this;
    }

    /**
     * @return array
     */
    public function keys(): array
    {
        return array_keys($this->sessions);
    }

    /**
     * @param array $value
     * @return Sessions
     */
    public function write(array $value): Sessions
    {
        $_SESSION[$this->key] = $this->sessions[$this->key] = $value;
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
