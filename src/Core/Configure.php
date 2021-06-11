<?php

namespace Restfull\Core;

use Restfull\Authentication\Auth;
use Restfull\Database\Database;
use Restfull\Error\Exceptions;
use Restfull\Htmltopdf\HtmlToPdf;
use Restfull\Mail\Email;
use Restfull\Pdftohtml\PdfToHtml;
use Restfull\Plugins\Abstration;
use Restfull\Security\Security;

/**
 * Class Configure
 * @package Restfull\Core
 */
class Configure
{

    /**
     * @var Email
     */
    private static $email;

    /**
     * @var Security
     */
    private static $security;

    /**
     * @var HtmlToPdf
     */
    private static $convertPdf;

    /**
     * @var PdfToHtml
     */
    private static $convertHtml;

    /**
     * @var Auth
     */
    private static $logged;

    /**
     * @var Abstration
     */
    private static $plugins;

    /**
     * @var array
     */
    private static $database;

    /**
     * @return array
     */
    public static function returnsConfings(): array
    {
        $bootstrap = [
                'email' => self::$email,
                'security' => self::$security,
                'logged' => self::$logged,
                'database' => self::$database,
                'pdf' => self::$convertPdf,

        ];
        if (self::$plugins instanceof Abstration) {
            $bootstrap = array_merge($bootstrap, ['plugins' => self::$plugins]);
        }
        if (self::$convertHtml instanceof PdfToHtml) {
            $bootstrap = array_merge($bootstrap, ['html' => self::$convertHtml]);
        }
        return $bootstrap;
    }

    /**
     * @param string $place
     * @return bool
     */
    public static function validateConnection(string $place): bool
    {
        self::$database->place($place);
        $start = self::$database->validConnetion();
        if ($start) {
            return true;
        }
        return false;
    }

    /**
     * @param array $banco
     * @param string|null $place
     */
    public static function configureDataBase(array $banco, string $place = null): void
    {
        $keys = array_keys($banco);
        if (!isset($place)) {
            $uri = parse_url(urldecode($_SERVER['REQUEST_URI']), PHP_URL_PATH);
            $bd = substr($uri, strripos(ROOT, DS_REVERSE) - 2);
            $bd = substr($bd, 0, stripos($bd, DS));
            for ($a = 0; $a < count($banco); $a++) {
                if ($bd == $keys[$a]) {
                    $db = $banco[$keys[$a]];
                }
            }
            if (!isset($db)) {
                $db = $banco['default'];
                $place = 'default';
            }
        } else {
            $db = $banco[$place];
        }
        self::$database = new Database();
        self::$database->place($place)->details($db)->instance();
        return;
    }

    /**
     *
     */
    public static function checkAndInstanceUserLogged(): void
    {
        self::$logged = new Auth();
        $key = self::$logged->fecthTheCookieKey();
        if (!empty($key)) {
            self::$logged->newAuth($key);
        }
        return;
    }

    /**
     * @param array $emails
     */
    public static function email(array $emails): void
    {
        self::$email = new Email($emails);
        return;
    }

    /**
     * @param int $segurança
     * @throws \Exception
     */
    public static function security(int $segurança): void
    {
        self::$security = new Security(self::$logged);
        self::$security->superGlobal();
        self::$security->salt($segurança);
        return;
    }

    /**
     * @param array $pdf
     */
    public static function pdf(array $pdf): void
    {
        Self::$convertPdf = new HtmlToPdf($pdf);
        return;
    }

    /**
     * @param array $html
     */
    public static function html(array $html): void
    {
        Self::$convertHtml = new PdfToHtml($html);
        return;
    }

    /**
     *
     */
    public static function close(): void
    {
        if (isset(self::$convertPdf)) {
            self::$convertPdf->destroy();
        }
        if (isset(self::$convertHtml)) {
            self::$convertHtml->destroy();
        }
        self::$email->destroy();
        return;
    }

    /**
     * @param array $plugins
     * @throws Exceptions
     */
    public static function plugins(array $plugins): void
    {
        if (count($plugins) > 0) {
            $newPlugin = new Abstration();
            for ($a = 0; $a < count($plugins); $a++) {
                $newPlugin->startClass($plugins['name'], $plugins['path']);
            }
            self::$plugins = $newPlugin;
        }
        return;
    }

}
