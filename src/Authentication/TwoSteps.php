<?php

declare(strict_types=1);

namespace Restfull\Authentication;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;

/**
 *
 */
class TwoSteps extends Authenticator
{

    /**
     * @var string
     */
    public $qrcode = '';

    /**
     * @var string
     */
    private $secret = '';

    /**
     * @var Authenticator
     */
    private $authtwo;

    /**
     * @param Auth $auth
     */
    public function __construct(Instances $instance, Auth $auth)
    {
        parent::__construct($instance);
        $this->auth = $auth;
        return $this;
    }

    /**
     * @param int $counts
     *
     * @return Twosteps
     * @throws Exceptions
     */
    public function qrcodeValid(int $counts = 16): Twosteps
    {
        if (!isset($this->secret)) {
            $this->secret = $this->createSecret($counts);
        }
        if (!$this->auth->check('user')) {
            $this->qrcode = $this->getQRCodeGoogleUrl($this->auth->getAuth('email'), $this->secret);
        }
        return $this;
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function validateCode(string $code): bool
    {
        return $this->verifyCode($this->secret, $code, 5);
    }

    /**
     * @return string
     */
    public function generate(): string
    {
        return implode('<br>', parent::generate());
    }

    /**
     * @return string
     */
    public function getQrcode(): string
    {
        return $this->qrcode;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     *
     * @return Twosteps
     */
    public function setSecret(string $secret): TwoSteps
    {
        $this->secret = $secret;
        return $this;
    }
}
