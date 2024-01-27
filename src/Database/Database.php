<?php

declare(strict_types=1);

namespace Restfull\Database;

use PDO;
use Restfull\Error\Exceptions;

/**
 *
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
     *
     */
    public function __construct()
    {
        $this->conneting = Conneting::class;
        return $this;
    }

    /**
     * @param array $details
     *
     * @return Database
     */
    public function details(array $details): Database
    {
        $this->details = $details;
        return $this;
    }

    /**
     * @param string $place
     * @param bool $return
     *
     * @return mixed
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
        if ($this->conneting::connectedDatabase() instanceof PDO) {
            return true;
        }
        return false;
    }

    /**
     * @param array $http
     *
     * @return PDO
     * @throws Exceptions
     */
    public function pdo(string $command): PDO
    {
        $this->conneting::banks($this->details);
        $this->conneting::connexion($this->place);
        return $this->conneting::connectedDatabase($command);
    }

    /**
     * @return bool
     */
    public function validNotEmpty(): bool
    {
        foreach (array_keys($this->validDetailsIsNotEmpty) as $key) {
            if ($this->details[$this->place][$key] === '') {
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
        return $this->details[$this->place]['dbname'];
    }

    /**
     * @return string
     */
    public function placeInstantiated(): string
    {
        return $this->place;
    }

    /**
     * @return bool
     */
    public function validateExistingConnectionData(): bool
    {
        return array_key_exists($this->place, $this->conneting::banks());
    }
}