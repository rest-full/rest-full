<?php

namespace Restfull\Mail;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Restfull\Error\Exceptions;

/**
 * Class Email
 * @package Restfull\Mail
 */
class Email
{

    /**
     * @var string
     */
    private $CharSet = PHPMailer::CHARSET_UTF8;

    /**
     * @var PHPMailer
     */
    private $mail;

    /**
     * @var array
     */
    private $config = [];

    /**
     * Email constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        if ($config['active']) {
            $this->mail = new PHPMailer();
            $this->mail->isSMTP();
            $this->mail->setWordWrap();
            $this->mail->setLanguage($config['language']);
            $this->mail->isHTML($config['html']);
            $this->preConfig($config['host'], $config['SMTP'], $config['user'], $config['pass'], $config['port']);
            $this->mail->CharSet = $this->CharSet;
        }
        $this->config = $config;
        return $this;
    }

    /**
     * @param string $host
     * @param array $SMTP
     * @param string $user
     * @param string $pass
     * @param int $port
     * @return Email
     */
    public function preConfig(string $host, array $SMTP, string $user, string $pass, int $port): Email
    {
        $this->mail->Host = $host;
        $this->mail->SMTPAuth = $SMTP['auth'];
        $this->mail->SMTPSecure = $SMTP['secure'];
        $this->mail->Username = $user;
        $this->mail->Password = $pass;
        $this->mail->Port = $port;
        $this->mail->SMTPDebug = $SMTP['debug'];
        return $this;
    }

    /**
     * @return array
     */
    public function getMail(): array
    {
        if ($this->mail instanceof PHPMailer) {
            return $this->config;
        }
        return [];
    }

    /**
     * @param bool $stmp
     * @return Email
     */
    public function SMTPauthTLS(bool $stmp): Email
    {
        $this->mail->SMTPAutoTLS = $stmp;
        return $this;
    }

    /**
     * @param object $config
     * @return Email
     */
    public function configs(object $config): Email
    {
        if ($this->mail->Host != $config->host) {
            $this->mail->Host = $config->host;
        }
        if ($this->mail->SMTPSecure != $config->secure) {
            $this->mail->SMTPSecure = $config->secure;
        }
        if ($this->mail->Username != $config->user) {
            $this->mail->Username = $config->user;
        }
        if ($this->mail->Password != $config->pass) {
            $this->mail->Password = $config->pass;
        }
        if ($this->mail->Port != $config->port) {
            $this->mail->Port = $config->port;
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function destroy()
    {
        $this->email = null;
        return $this;
    }

    /**
     * @param array $sender
     * @param array $recipient
     * @param array|null $recipientccs
     * @param array|null $recipientbccs
     * @return string
     * @throws Exception
     */
    public function addressing(
            array $sender,
            array $recipient,
            array $recipientccs = null,
            array $recipientbccs = null
    ): string {
        $a = 0;
        $resp[$a] = 'true';
        if (!$this->validerAddress($sender, $recipient)) {
            $resp[$a] = 'false';
        }
        if ($resp[$a] == 'true') {
            $this->mail->setFrom($sender['email'], $sender['name']);
            if (isset($recipient['name'])) {
                $this->mail->addAddress($recipient['email'], $recipient['name']);
            } else {
                $this->mail->addAddress($recipient['email']);
            }
        }
        $a++;
        if (isset($recipientccs)) {
            foreach ($recipientccs as $recipientcc) {
                $resp[$a] = 'true';
                if (!$this->validerAddress($recipientcc)) {
                    $resp[$a] = 'false';
                }
                if ($resp[$a] == 'true') {
                    if (isset($recipientcc['name'])) {
                        $this->mail->addCC($recipientcc['email'], $recipientcc['name']);
                    } else {
                        $this->mail->addCC($recipientcc['email']);
                    }
                }
                $a++;
            }
        }
        if (isset($recipientbccs)) {
            foreach ($recipientccs as $recipientcc) {
                $resp[$a] = 'true';
                if (!$this->validerAddress($recipientcc)) {
                    $resp[$a] = 'false';
                }
                if ($resp[$a] == 'true') {
                    if (isset($recipientbcc['name'])) {
                        $this->mail->addBCC($recipientbcc['email'], $recipientbcc['name']);
                    } else {
                        $this->mail->addBCC($recipientbcc['email']);
                    }
                }
                $a++;
            }
        }
        foreach ($resp as $value) {
            $count = 0;
            if ($value == 'true') {
                $count++;
            }
        }
        $newCount = isset($recipientbccs) || isset($recipientccs) ? (count($resp) - 1) : count($resp);
        if ($newCount == $count) {
            return "valid";
        }
        return "not valid";
    }

    /**
     * @param array $data1
     * @param array $data2
     * @return bool
     */
    private function validerAddress(array $data1, array $data2 = []): bool
    {
        $resp1 = ['valid', 'valid'];
        $a = 0;
        foreach (array_keys($data1) as $key) {
            if (!in_array($key, ['email', 'name'])) {
                $resp1[$a] = 'not valid';
            }
            $a++;
        }
        if (count($data2) > 0) {
            $resp2 = ['valid', 'valid'];
            $a = 0;
            foreach (array_keys($data1) as $key) {
                if (!in_array($key, ['email', 'name'])) {
                    $resp2[$a] = 'not valid';
                }
                $a++;
            }
            if (in_array('not valid', $resp2)) {
                return false;
            }
            return true;
        }
        if (in_array('not valid', $resp1)) {
            return false;
        }
        return true;
    }

    /**
     * @param array $arq
     * @return Email
     * @throws Exception
     */
    public function attachment(array $arq): Email
    {
        $keys = array_keys($arq);
        for ($a = 0; $a < count($keys); $a++) {
            $this->mail->addAttachment($arq[$keys[$a]]['tmp_name'], $arq[$keys[$a]]['name']);
        }
        return $this;
    }

    /**
     * @param string $subject
     * @param string $message
     * @return bool
     * @throws Exceptions
     */
    public function sends(string $subject, string $message): bool
    {
        try {
            $this->mail->Subject = $subject;
            $this->mail->Body = nl2br($message);
            $this->mail->AltBody = nl2br($message);
            return $this->mail->send();
        } catch (Exception $exception) {
            throw new Exceptions($exception, "422");
        }
    }

}
