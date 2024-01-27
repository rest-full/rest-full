<?php

declare(strict_types=1);

namespace Restfull\Security;

use Restfull\Authentication\Auth;
use Restfull\Container\Instances;

/**
 *
 */
class Security
{

    /**
     * @var string
     */
    public $salt = '';

    /**
     * @var string
     */
    public $rand = '';

    /**
     * @var bool
     */
    private $alfanumerico = false;

    /**
     * @var auth
     */
    private $auth;

    /**
     * @var int
     */
    private $numberSalt = 32;

    /**
     * @var Hash
     */
    private $hash;

    /**
     * @param Auth $auth
     * @param int $number
     */
    public function __construct(Auth $auth, int $number)
    {
        $this->auth = $auth;
        if ($this->numberSalt != $number) {
            $this->numberSalt = $number;
        }
        $this->salt();
        return $this;
    }

    /**
     * @return Security
     */
    public function salt(): Security
    {
        $this->salt = substr(base64_encode(bin2hex(random_bytes($this->numberSalt))), 0, -2);
        if (!$this->auth->check('csrf')) {
            $this->auth->write('csrf', ['token' => $this->salt]);
        }
        return $this;
    }

    /**
     *
     */
    public function superGlobal(): void
    {
        if (!empty($_GET)) {
            $_GET = filter_var_array($_GET, FILTER_SANITIZE_SPECIAL_CHARS);
        }
        if (!empty($_POST)) {
            $_POST = filter_var_array($_POST, FILTER_SANITIZE_SPECIAL_CHARS);
        }
        if (!empty($_PUT)) {
            $_POST = filter_var_array($_PUT, FILTER_SANITIZE_SPECIAL_CHARS);
        }
        if (!empty($_PATCH)) {
            $_POST = filter_var_array($_PATCH, FILTER_SANITIZE_SPECIAL_CHARS);
        }
        if (!empty($_DELETE)) {
            $_POST = filter_var_array($_DELETE, FILTER_SANITIZE_SPECIAL_CHARS);
        }
        if (!empty($_FILES)) {
            $_FILES = filter_var_array($_FILES, FILTER_SANITIZE_SPECIAL_CHARS);
        }
        return;
    }

    /**
     * @param string $pass
     *
     * @return string
     */
    public function hashPass(string $pass): string
    {
        return password_hash($pass, PASSWORD_DEFAULT, ['cost' => 12]);
    }

    /**
     * @param string $pass
     * @param string $encrypted
     *
     * @return bool
     */
    public function passwordIndentify(string $pass, string $encrypted = ''): bool
    {
        if (!empty($encrypted)) {
            return password_verify($pass, $encrypted);
        }
        return false;
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param string $salt
     *
     * @return Security
     */
    public function setSalt(string $salt): Security
    {
        $this->salt = $salt;
        if ($this->auth->getSession('csrf')['token'] !== $salt) {
            $this->auth->write('csrf', ['token' => $this->salt]);
        }
        return $this;
    }

    /**
     * @param int $number
     * @param bool $alfanumerico
     *
     * @return string
     */
    public function getRand(int $number, bool $alfanumerico = false): string
    {
        if ($alfanumerico != $this->alfanumerico) {
            $this->alfanumerico = $alfanumerico;
        }
        $this->rand($number);
        return $this->rand;
    }

    /**
     * @param string $number
     *
     * @return Security
     */
    public function rand(string $number): Security
    {
        if ($this->alfanumerico) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < 100; $i++) {
                $rand = rand(0, $charactersLength - 1);
                if (stripos($randomString, $characters[$rand]) === false) {
                    $randomString .= $characters[$rand];
                }
                if (strlen($randomString) === $number) {
                    break;
                }
            }
            $this->rand = $randomString;
            return $this;
        }
        $rand = '';
        for ($a = 1; $a <= 100; $a++) {
            if ($a < 10) {
                $b = $a;
            } else {
                if ($a % 10 === 0) {
                    $c = $a / 10;
                }
                $b = $a - ($c * 10);
            }
            $newRand = rand(1, 9);
            if (stripos($rand, $newRand) === false) {
                $rand .= $newRand;
                if ($b === $number) {
                    break;
                }
            }
        }
        $this->rand = $rand;
        return $this;
    }

    /**
     * @param string $salt
     *
     * @return bool
     */
    public function valideCsrf(string $salt): bool
    {
        $this->setSalt($salt);
        return $this->auth->getSession('csrf') === $salt;
    }

    /**
     * @return string
     */
    public function csrfOldRoute(): string
    {
        return $this->auth->getSession('routeThatUsesCsrf')['route'];
    }

    /**
     * @return Hash
     */
    public function hash(Instances $instance, int $number)
    {
        return $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Security' . DS_REVERSE . 'Hash',
            ['auth' => $this->auth, 'instance' => $instance, 'number' => $number, 'salt' => $this->salt]
        );
    }

}
