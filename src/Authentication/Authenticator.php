<?php

declare(strict_types=1);

namespace Restfull\Authentication;

use chillerlan\QRCode\QRCode;
use PragmaRX\Random\Random;
use Restfull\Container\Instances;
use Restfull\Error\Exceptions;

/**
 *
 */
abstract class Authenticator
{

    /**
     * @var int
     */
    protected $codeLength = 6;

    /**
     * @var array
     */
    private $base32 = [];

    /**
     * @var Random
     */
    private $random;

    /**
     * @param Instances $instance
     * @throws Exceptions
     */
    public function __construct(Instances $instance)
    {
        $base32 = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S'];
        $base32 = array_merge($base32, ['T', 'U', 'V', 'W', 'X', 'Y', 'Z', '2', '3', '4', '5', '6', '7']);
        $this->base32 = array_merge($base32, ['=']);
        if ($this->codeLength != 6) {
            $this->codeLength = 6;
        }
        $this->random = $instance->resolveClass('PragmaRX'.DS_REVERSE.'Random'.DS_REVERSE.'Random');
        return $this;
    }

    /**
     * @param int $secretLength
     * @return string
     * @throws Exceptions
     * @throws \Random\RandomException
     */
    public function createSecret(int $secretLength): string
    {
        if ($secretLength < 16 || $secretLength > 128) {
            throw new Exceptions('Bad secret length');
        }
        $secret = '';
        $rnd = false;
        if (function_exists('random_bytes')) {
            $rnd = random_bytes($secretLength);
        } elseif (function_exists('mcrypt_create_iv')) {
            $rnd = mcrypt_create_iv($secretLength, MCRYPT_DEV_URANDOM);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $rnd = openssl_random_pseudo_bytes($secretLength, $cryptoStrong);
            if (!$cryptoStrong) {
                $rnd = false;
            }
        }
        if ($rnd !== false) {
            for ($i = 0; $i < $secretLength; ++$i) {
                $secret .= $this->base32[ord($rnd[$i]) & 31];
            }
        } else {
            throw new Exceptions('No source of secure random');
        }
        return $secret;
    }

    /**
     * @param string $name
     * @param string $secret
     * @return string
     */
    public function getQRCodeGoogleUrl(string $name, string $secret): string
    {
        return '<img = src="' . (new QRCode())->render('otpauth://totp/' . $name . '?secret=' . $secret) . '">';
    }

    /**
     * @param string $secret
     * @param string $code
     * @param int $discrepancy
     * @param int|null $currentTimeSlice
     * @return string
     */
    public function verifyCode(string $secret, string $code, int $discrepancy = 1, int $currentTimeSlice = null): string
    {
        if ($currentTimeSlice === null) {
            $currentTimeSlice = floor(time() / 30);
        }
        for ($i = -$discrepancy; $i <= $discrepancy; ++$i) {
            $calculatedCode = $this->getCode($secret, $currentTimeSlice + $i);
            if ($this->timingSafeEquals($calculatedCode, $code)) {
                return true;
            }
        }
        return '';
    }

    /**
     * @param string $secret
     * @param int|null $timeSlice
     * @return string
     */
    public function getCode(string $secret, int $timeSlice = null): string
    {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }
        $secretkey = $this->base32Decode($secret);
        $time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N*', $timeSlice);
        $hm = hash_hmac('SHA1', $time, $secretkey, true);
        $offset = ord(substr($hm, -1)) & 0x0F;
        $hashpart = substr($hm, $offset, 4);
        $value = unpack('N', $hashpart)[1] & 0x7FFFFFFF;
        $modulo = pow(10, $this->codeLength);
        return str_pad($value % $modulo, $this->codeLength, '0', STR_PAD_LEFT);
    }

    /**
     * @param string $secret
     * @return false|string
     */
    protected function base32Decode(string $secret)
    {
        if (empty($secret)) {
            return '';
        }
        $base32charsFlipped = array_flip($this->base32);
        $paddingCharCount = substr_count($secret, $this->base32[32]);
        $allowedValues = array(6, 4, 3, 1, 0);
        if (!in_array($paddingCharCount, $allowedValues)) {
            return false;
        }
        for ($i = 0; $i < 4; ++$i) {
            if ($paddingCharCount === $allowedValues[$i] && substr($secret, -($allowedValues[$i])) != str_repeat(
                    $this->base32[32],
                    $allowedValues[$i]
                )) {
                return false;
            }
        }
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';
        $count = count($secret);
        for ($i = 0; $i < $count; $i = $i + 8) {
            $x = '';
            if (!in_array($secret[$i], $this->base32)) {
                return false;
            }
            for ($j = 0; $j < 8; ++$j) {
                $x .= str_pad(base_convert($base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
            $eightBits = str_split($x, 8);
            $count = count($eightBits);
            for ($z = 0; $z < $count; ++$z) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) === 48) ? $y : '';
            }
        }
        return $binaryString;
    }

    /**
     * @param string $safeString
     * @param string $userString
     * @return bool
     */
    private function timingSafeEquals(string $safeString, string $userString): bool
    {
        if (function_exists('hash_equals')) {
            return hash_equals($safeString, $userString);
        }
        $safeLen = strlen($safeString);
        $userLen = strlen($userString);
        if ($userLen != $safeLen) {
            return false;
        }
        $result = 0;
        for ($i = 0; $i < $userLen; ++$i) {
            $result |= (ord($safeString[$i]) ^ ord($userString[$i]));
        }
        return $result === 0;
    }

    /**
     * @return array
     */
    public function generate(): array
    {
        $codes = [];
        for ($a = 1; $a <= 8; $a++) {
            $codes[] = $this->random->size(5)->get() . '-' . $this->random->size(5)->get();
        }
        return $code;
    }
}