<?php

declare(strict_types=1);

namespace Restfull\Mail;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Restfull\Error\Exceptions;

/**
 *
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
     * Email constructor.
     *
     * @param array $data
     */
    public function __construct(array $config)
    {
        if ($config['active']) {
            $this->mail = new PHPMailer();
            $this->mail->isSMTP();
            $this->mail->setWordWrap();
            $this->mail->setLanguage($config['linguage']);
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
     *
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
     *
     * @return Email
     */
    public function SMTPauthTLS(bool $stmp): Email
    {
        $this->mail->SMTPAutoTLS = $stmp;
        return $this;
    }

    /**
     * @param object $config
     *
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
     * @param string $path
     * @param string $name
     *
     * @return Email
     */
    public function embedImages(string $path, string $name): Email
    {
        $this->mail->addEmbeddedImage($path, $name);
        return $this;
    }

    /**
     * @return Email
     */
    public function destroy()
    {
        $this->email = null;
        return $this;
    }

    /**
     * @param array $sender
     * @param array $recipient
     *
     * @return string
     * @throws Exception
     */
    public function addressing(array $sender, array $recipients): void
    {
        if (!$this->validerAddress($sender, $recipients)) {
            throw new Exceptions('this sender ou recipient not valid.', 404);
        }
        $this->mail->setFrom($sender['email'], $sender['name']);
        if ($this->multiEmailsRecipient($recipients)) {
            foreach ($recipients as $recipient) {
                if (isset($recipient['name'])) {
                    $this->mail->addAddress($recipient['email'], $recipient['name']);
                } else {
                    $this->mail->addAddress($recipient['email']);
                }
            }
        } else {
            if (isset($recipients['name'])) {
                $this->mail->addAddress($recipients['email'], $recipients['name']);
            } else {
                $this->mail->addAddress($recipients['email']);
            }
        }
        return;
    }

    /**
     * @param array $data1
     * @param array $data2
     *
     * @return bool
     */
    public function validerAddress(array $data1, array $data2 = []): bool
    {
        $resp1 = ['valid', 'valid'];
        $a = 0;
        foreach (array_keys($data1) as $key) {
            if (in_array($key, ['email', 'name']) !== false) {
                $resp1[$a] = 'not valid';
            }
            $a++;
        }
        $keys = array_keys($data2);
        if (!$this->multiEmailsRecipient($data2)) {
            $resp2 = ['valid', 'valid'];
            $a = 0;
            foreach ($keys as $key) {
                if (in_array($key, ['email', 'name']) !== false) {
                    $resp2[$a] = 'not valid';
                }
                $a++;
            }
            if (in_array('not valid', $resp2) !== false) {
                return false;
            }
            return true;
        }
        $count = count($keys);
        for ($a = 0; $a < $count; $a++) {
            $resp2[] = ['valid', 'valid'];
            foreach (array_keys($data2[$keys[$a]]) as $key) {
                if (in_array($key, ['email', 'name']) !== false) {
                    $resp2[$a][$key] = 'not valid';
                }
            }
        }
        $newResp2 = $this->multi2uni($resp2);
        if (in_array('not valid', $newResp2) !== false) {
            return false;
        }
        return true;
    }

    /**
     * @param array $recipients
     *
     * @return bool
     */
    private function multiEmailsRecipient(array $recipients): bool
    {
        $resp = false;
        if (count(array_keys($recipients)) < 2) {
            foreach (array_keys($recipients) as $key) {
                $resp = is_numeric($key);
                break;
            }
        } else {
            $resp = true;
        }
        return $resp;
    }

    /**
     * @param array $old
     * @param array $array
     *
     * @return array
     */
    private function multi2uni(array $old, array $array = []): array
    {
        foreach ($old as $value) {
            if (is_array($value)) {
                $array = array_merge($this->multi2uni($value), $array);
            } else {
                $array[] = $value;
            }
        }
        return $array;
    }

    /**
     * @param array $arq
     *
     * @return Email
     * @throws Exception
     */
    public function attachment(array $arq): Email
    {
        $keys = array_keys($arq);
        $count = count($keys);
        for ($a = 0; $a < $count; $a++) {
            $this->mail->addAttachment($arq[$keys[$a]]['tmp_name'], $arq[$keys[$a]]['name']);
        }
        return $this;
    }

    /**
     * @param array $recipientccs
     *
     * @return Email
     * @throws Exception
     */
    public function copy(array $recipientccs): Email
    {
        if (!$this->validerAddress($recipientccs)) {
            throw new Exceptions('this recipientcc not valid.', 404);
        }
        foreach ($recipientccs as $recipientcc) {
            $resp = true;
            if (!$this->validerAddress($recipientcc)) {
                $resp = !$resp;
            }
            if ($resp) {
                if (isset($recipientcc['name'])) {
                    $this->mail->addCC($recipientcc['email'], $recipientcc['name']);
                } else {
                    $this->mail->addCC($recipientcc['email']);
                }
            }
        }
        return $this;
    }

    /**
     * @param array $recipientbccs
     *
     * @return Email
     * @throws Exception
     */
    public function hiddenCopy(array $recipientbccs): Email
    {
        if (!$this->validerAddress($recipientbccs)) {
            throw new Exceptions('this recipientbcc not valid.', 404);
        }
        foreach ($recipientbccs as $recipientbcc) {
            $resp = true;
            if (!$this->validerAddress($recipientbcc)) {
                $resp = !$resp;
            }
            if ($resp) {
                if (isset($recipientbcc['name'])) {
                    $this->mail->addBCC($recipientbcc['email'], $recipientbcc['name']);
                } else {
                    $this->mail->addBCC($recipientbcc['email']);
                }
            }
        }
        return $this;
    }

    /**
     * @param string $subject
     * @param string $message
     *
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
