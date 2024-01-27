<?php

declare(strict_types=1);

namespace Restfull\Core;

use Restfull\Authentication\Auth;
use Restfull\Container\Instances;
use Restfull\Database\Database;
use Restfull\Error\Exceptions;
use Restfull\Htmltopdf\HtmlToPdf;
use Restfull\Mail\Email;
use Restfull\Pdftohtml\PdfToHtml;
use Restfull\Plugins\Abstration;
use Restfull\Security\Hash;
use Restfull\Security\Security;

/**
 *
 */
class Configure
{

    /**
     * @var Email
     */
    private $email;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var HtmlToPdf
     */
    private $convertPdf;

    /**
     * @var PdfToHtml
     */
    private $convertHtml;

    /**
     * @var Auth
     */
    private $logged;

    /**
     * @var Abstration
     */
    private $plugins;

    /**
     * @var Database
     */
    private $database = [];

    /**
     * @var Hash
     */
    private $hash;

    /**
     * @var array
     */
    private $middlewares = [];

    /**
     * @var string
     */
    private $cache = '';

    /**
     * @var Instances
     */
    private $instance;

    /**
     * @param Instances $instance
     */
    public function __construct(Instances $instance)
    {
        $this->instance = $instance;
        return $this;
    }

    /**
     * @return array
     */
    public function returnsConfings(): array
    {
        $bootstrap = [
            'email' => $this->email,
            'security' => $this->security,
            'auth' => $this->logged,
            'database' => $this->database,
            'pdf' => $this->convertPdf,
            'hash' => $this->hash,
            'plugins' => $this->plugins,
            'middleware' => $this->middlewares,
            'cache' => $this->cache
        ];
        if ($this->plugins instanceof Abstration) {
            $bootstrap = array_merge($bootstrap, ['plugins' => $this->plugins]);
        }
        if ($this->convertHtml instanceof PdfToHtml) {
            $bootstrap = array_merge($bootstrap, ['html' => $this->convertHtml]);
        }
        return $bootstrap;
    }

    /**
     *
     */
    public function otherMiddleware(array $middlewares): void
    {
        $this->middlewares = $middlewares;
        return;
    }

    /**
     * @param bool $cache
     * @param string $time
     *
     * @return void
     */
    public function cacheActive(bool $cache, string $time): void
    {
        if ($cache) {
            $this->middlewares = array_merge(
                ['Executing' . DS_REVERSE . 'Middleware' . DS_REVERSE . 'CacheMiddleware'],
                $this->middlewares
            );
            return;
        }
        $this->cache = $time;
        return;
    }

    /**
     * @param string $place
     *
     * @return bool
     */
    public function validateConnection(string $place): bool
    {
        if ($this->database->place($place)->validConnetion()) {
            return true;
        }
        return false;
    }

    /**
     * @param array $bank
     * @param string|null $place
     */
    public function configureDatabase(array $config, string $place = null): void
    {
        if (!isset($place)) {
            $place = 'default';
        }
        $this->database = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Database' . DS_REVERSE . 'Database'
        );
        $this->database->place($place)->details($config);
        return;
    }

    /**
     *
     */
    public function checkAndInstanceUserLogged(): void
    {
        $this->logged = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Authentication' . DS_REVERSE . 'Auth'
        );
        $this->logged->setAuth('user');
        return;
    }

    /**
     * @param array $emails
     */
    public function email(array $emails): void
    {
        $this->email = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Mail' . DS_REVERSE . 'Email',
            ['config' => $emails]
        );
        return;
    }

    /**
     * @param int $security
     */
    public function security(int $security): void
    {
        $this->security = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Security' . DS_REVERSE . 'Security',
            ['auth' => $this->logged, 'number' => $security]
        );
        $this->security->superGlobal();
        $this->hash = $this->security->hash($this->instance, $security);
        return;
    }

    /**
     * @param array $pdf
     */
    public function pdf(array $pdf): void
    {
        $this->convertPdf = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'HtmlToPdf' . DS_REVERSE . 'HtmlToPdf',
            ['config' => $pdf['config'], 'pdf' => $pdf['mode']]
        );
        return;
    }

    /**
     *
     */
    public function close(): void
    {
        if (isset($this->convertPdf)) {
            $this->convertPdf->destroy();
        }
        if (isset($this->convertHtml)) {
            $this->convertHtml->destroy();
        }
        $this->email->destroy();
        return;
    }

    /**
     * @param array $plugins
     *
     * @throws Exceptions
     */
    public function plugins(Instances $instance, array $plugins): void
    {
        $count = count($plugins);
        $this->plugins = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Plugins' . DS_REVERSE . 'Abstration',
            ['instance' => $instance]
        );
        for ($a = 0; $a < $count; $a++) {
            if (count($plugins[$a]) > 0) {
                if (isset($plugins[$a]['path'])) {
                    $this->plugins->setClass($plugins[$a]['name'], $plugins[$a]['path']);
                } else {
                    $this->plugins->setClass($plugins[$a]['name']);
                }
            }
        }
        return;
    }

    /**
     * @param array $margins
     *
     * @return array
     */
    private function margins(array $margins): array
    {
        switch (count($margins)) {
            case '1':
                $top = array_shift($margins);
                $margins = [$top, $top, $top, $top];
                break;
            case '2':
                $top = array_shift($margins);
                $button = array_shift($margins);
                $margins = [$top, $button, $top, $button];
                break;
            case '3':
                $top = array_shift($margins);
                $left = array_shift($margins);
                $button = array_shift($margins);
                $margins = [$top, $left, $button, $left];
        }
        return $margins;
    }

}
