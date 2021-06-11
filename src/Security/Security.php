<?php

namespace Restfull\Security;

use Restfull\Authentication\Auth;
use Restfull\Error\Exceptions;
use Restfull\Filesystem\File;

/**
 * Class Security
 * @package Restfull\Security
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
     * @var array
     */
    private $keyEncrypt = [];

    /**
     * @var auth
     */
    private $auth;

    /**
     * @var int
     */
    private $numberSalt = 32;

    /**
     * @var string
     */
    private $decripting = '0';

    /**
     * Security constructor.
     * @param Auth $auth
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
        if ($this->auth->keys('crypt', true)) {
            $this->keyEncrypt = $this->auth->get();
        }
        return $this;
    }

    /**
     *
     */
    public function superGlobal(): void
    {
        if (!empty($_GET)) {
            $_GET = filter_var_array($_GET, FILTER_DEFAULT);
        }
        if (!empty($_POST)) {
            $_POST = filter_var_array($_POST, FILTER_DEFAULT);
        }
        if (!empty($_FILES)) {
            $_FILES = filter_var_array($_FILES, FILTER_DEFAULT);
        }
        return;
    }

    /**
     * @param int $number
     * @return Security
     * @throws \Exception
     */
    public function salt(int $number): Security
    {
        $this->salt = substr(base64_encode(bin2hex(random_bytes($number))), 0, -2);
        if (!$this->auth->keys('csrf', true)) {
            $this->auth->write('csrf', ['token' => $this->salt]);
        }
        if ($this->numberSalt != $number) {
            $this->numberSalt = $number;
        }
        return $this;
    }

    /**
     * @param string $pass
     * @return string
     */
    public function hashPass(string $pass): string
    {
        return password_hash($pass, PASSWORD_DEFAULT, ['cost' => 12]);
    }

    /**
     * @param string $pass
     * @param string $encrypted
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
     * @return Security
     */
    public function setSalt(string $salt): Security
    {
        $this->salt = $salt;
        if ($this->auth->getSession('csrf')['token'] == $salt) {
            $this->auth->write('csrf', ['token' => $this->salt]);
        }
        return $this;
    }

    /**
     * @param int $number
     * @param bool $alfanumerico
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
                if (strlen($randomString) == $number) {
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
                if ($a % 10 == 0) {
                    $c = $a / 10;
                }
                $b = $a - ($c * 10);
            }
            $newRand = rand(1, 9);
            if (stripos($rand, $newRand) === false) {
                $rand .= $newRand;
                if ($b == $number) {
                    break;
                }
            }
        }
        $this->rand = $rand;
        return $this;
    }

    /**
     * @param string $path
     * @param int $level
     * @return string
     * @throws Exceptions
     */
    public function encrypt(string $path, int $level): string
    {
        if ($level != 0) {
            $this->decripting = '1';
            $caracters = 'abcdefghijklmnopqrstuvwxyz0123456789';
            $cript = '';
            for ($a = 0; $a < strlen($path); $a++) {
                $alfanumeric = $caracters[rand(1, 35)];
                $cript .= stripos($cript, $alfanumeric) !== false ? strtoupper($alfanumeric) : $alfanumeric;
                $cript .= $path[$a];
            }
            if ($level >= 2) {
                if (!isset($this->keyEncrypt['key']) || (empty($this->keyEncrypt['key']))) {
                    $this->dataKeysEncryptDecrypt();
                }
                $cript = str_replace(
                        DS,
                        '_',
                        str_replace(
                                '+',
                                '|',
                                openssl_encrypt(
                                        $cript,
                                        'AES-128-CBC',
                                        $this->keyEncrypt['key'],
                                        0,
                                        $this->keyEncrypt['iv']
                                )
                        )
                );
            }
            if ($level == 3) {
                $cript = str_replace(DS, '_', base64_encode($cript));
            }
            return $cript;
        }
        return $path;
    }

    /**
     * @param string $datas
     * @return Security
     */
    private function dataKeysEncryptDecrypt(string $datas = null): Security
    {
        if (!is_null($datas)) {
            $datas = explode(', ', base64_decode($datas));
            if ($this->decripting != $datas[2]) {
                $this->decripting = array_pop($datas);
            }
            if (count($this->keyEncrypt) == 0 || $this->keyEncrypt['key'] != $datas[0]) {
                $this->keyEncrypt = ['key' => $datas[0], 'iv' => $datas[1]];
            }
            return $this;
        }
        $this->activeDecrypt();
        if (!isset($this->keyEncrypt['key'])) {
            if (empty($this->keyEncrypt['key'])) {
                $this->keyEncrypt = [
                        'key' => bin2hex(random_bytes($this->numberSalt)),
                        'iv' => substr(
                                base64_encode(crypt(openssl_cipher_iv_length('AES-128-CBC'), $this->salt)),
                                0,
                                16
                        )
                ];
            }
        }
        return $this;
    }

    /**
     *
     */
    private function activeDecrypt(): void
    {
        $name = 'visit';
        if ($this->auth->check()) {
            $name = $this->auth->getData('name');
        }
        $file = new File(str_replace('src', 'config', RESTFULL) . 'key_ssl.txt');
        $rows = $file->read()['content'];
        if (count($rows) > 0) {
            for ($a = 0; $a < count($rows); $a++) {
                if ($rows[$a] !== "\r\n") {
                    list($newName, $keyEncript) = explode(': ', $rows[$a]);
                    if ($newName == $name) {
                        $this->dataKeysEncryptDecrypt($keyEncript);
                    }
                }
            }
            if (!isset($this->keyEncrypt['key'])) {
                if (empty($this->keyEncrypt['key'])) {
                    if ($name != 'visit') {
                        for ($a = 0; $a < count($rows); $a++) {
                            list($newName, $keyEncript) = explode(': ', $rows[$a]);
                            $this->dataKeysEncryptDecrypt($keyEncript);
                        }
                    }
                }
            }
        }
        return;
    }

    /**
     * @param string $cript
     * @param int $level
     * @param string $whatLocationTouse
     * @return string
     * @throws Exceptions
     */
    public function decrypt(string $cript, int $level, string $whatLocationTouse = 'url'): string
    {
        if ($level >= 1) {
            $this->decripting = '1';
        }
        if ($whatLocationTouse != 'url') {
            if (count($this->keyEncrypt) == 0) {
                $this->activeDecrypt();
            }
        } else {
            $this->keyEncrypt = $this->auth->getSession('crypt');
            $this->decripting = $this->keyEncrypt['decripting'];
            unset($this->keyEncrypt['decripting']);
        }
        if (!isset($this->keyEncrypt['key'])) {
            if (empty($this->keyEncrypt['key'])) {
                $this->activeEncrypt('file');
            }
        }
        if ($this->decripting == '1') {
            if ($level == 3) {
                $cript = base64_decode($cript);
            }
            if ($level >= 2) {
                $cript = str_replace('_', DS, str_replace('|', '+', $cript));
                $cript = openssl_decrypt(
                        $cript,
                        'AES-128-CBC',
                        $this->keyEncrypt['key'],
                        0,
                        $this->keyEncrypt['iv']
                );
            }
            $path = '';
            for ($a = 1; $a < strlen($cript); $a = $a + 2) {
                $path .= $cript[$a];
            }
            return $path;
        }
        return $cript;
    }

    /**
     * @param string $whatLocationTouse
     * @return Security
     * @throws Exceptions
     */
    public function activeEncrypt(string $whatLocationTouse = 'url'): Security
    {
        if (!isset($this->keyEncrypt['key'])) {
            if ($whatLocationTouse == 'url') {
                $this->auth->keys('crypt');
                $this->auth->write(array_merge($this->keyEncrypt, ['decripting' => $this->decripting]));
            } else {
                $name = 'visit';
                if ($this->auth->check('user')) {
                    $name = $this->auth->getData()['user'];
                }
                $text = $this->keyEncrypt['key'] . ', ' . $this->keyEncrypt['iv'] . ', 1';
                $file = new File(str_replace('src', 'config', RESTFULL) . 'key_ssl.txt');
                $rows = $file->read()['content'];
                if (count($rows) > 0) {
                    for ($a = 0; $a < count($rows); $a++) {
                        if ($rows[$a] === "\r\n") {
                            unset($rows[$a]);
                        }
                    }
                    sort($rows);
                    if ('LCAsIDE=' != base64_encode($text)) {
                        if (in_array($name . ': ' . base64_encode($text), $rows) !== false) {
                            $rows = implode("\r\n", array_merge($rows, [$name . ': ' . base64_encode($text)]));
                            $file->write($rows);
                        }
                    }
                } else {
                    if ('LCAsIDE=' != base64_encode($text)) {
                        if (in_array($name . ': ' . base64_encode($text), $rows) !== false) {
                            $rows = implode("\r\n", array_merge($rows, [$name . ': ' . base64_encode($text)]));
                            $file->write($rows);
                        }
                    }
                }
            }
        } else {
            if (empty($this->keyEncrypt['key'])) {
                $this->dataKeysEncryptDecrypt();
                $text = $this->keyEncrypt['key'] . ', ' . $this->keyEncrypt['iv'] . ', 1';
                if ('LCAsIDE=' != base64_encode($text)) {
                    $file->write($name . ': ' . base64_encode($text));
                }
            }
        }
        return $this;
    }

    public function valideCsrf(string $salt): bool
    {
        if ($this->auth->getSession('csrf')['token'] == $salt) {
            $this->auth->write('csrf', ['token' => $salt]);
            $this->salt = $salt;
            return true;
        }
        return false;
    }

    public function csrfOldRoute(): string
    {
        return $this->auth->getSession('routeThatUsesCsrf')['route'];
    }

    public function valideDecryptBase64(string $data): bool
    {
        $result = true;
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $data)) {
            $result = !$result;
        }
        if ($result) {
            $decrypt = base64_decode($data);
            if ($decrypt === false) {
                $result = !$result;
            }
            if ($result) {
                if (base64_encode($decrypt) !== $data) {
                    $result = !$result;
                }
            }
        }
        return $result;
    }
}
