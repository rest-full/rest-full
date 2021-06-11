<?php

namespace Restfull\Database;

use PDO;
use Restfull\Error\Exceptions;

/**
 * Class Database
 * @package Restfull\Database
 */
class Database
{

    /**
     * @var array
     */
    private $details = [];

    /**
     * @var string
     */
    private $place = '';

    /**
     * @var Conneting
     */
    private $conneting;

    /**
     * @var array
     */
    private $validDetailsIsNotEmpty = ['host' => false, 'password' => false, 'username' => false, 'dbname' => false];

    /**
     * Database constructor.
     */
    public function __construct()
    {
        $this->conneting = Conneting::class;
        return $this;
    }

    /**
     * @param array $details
     * @return Database
     */
    public function details(array $details): Database
    {
        $this->details[$this->place] = $details;
        return $this;
    }

    /**
     * @param string $place
     * @param bool $return
     * @return Database|string
     */
    public function place(string $place, bool $return = false)
    {
        if ($return) {
            return $this->place;
        }
        $this->place = $place;
        return $this;
    }

    /**
     * @return bool
     * @throws Exceptions
     */
    public function validConnetion(): bool
    {
        $this->conneting::connexion($this->place);
        if ($this->conneting::existDatabase() instanceof PDO) {
            return true;
        }
        return false;
    }

    /**
     * @param array $http
     * @return PDO
     * @throws Exceptions
     */
    public function pdo(array $http): PDO
    {
        $this->details = $http['request']->bootstrap('database')->details;
        $this->place = $http['request']->bootstrap('database')->place;
        return $this->instance()->conneting::existDatabase();
    }

    /**
     * @return $this
     */
    public function instance(): Database
    {
        $this->conneting::connexion($this->place);
        $this->conneting::setBanco($this->details[$this->place]);
        $this->conneting::setDrive($this->details[$this->place]);
        return $this;
    }

    /**
     * @return bool
     */
    public function validNotEmpty(): bool
    {
        foreach (array_keys($this->validDetailsIsNotEmpty) as $key) {
            if ($this->details[$this->place][$key] == '') {
                $this->validDetailsIsNotEmpty[$key] = true;
            }
        }
        $count = 4;
        foreach ($this->validDetailsIsNotEmpty as $value) {
            if ($value) {
                $count--;
            }
        }
        if ($count < 4) {
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function dbname(): string
    {
        return $this->conneting::dbname();
    }
}