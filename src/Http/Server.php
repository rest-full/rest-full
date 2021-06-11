<?php

namespace Restfull\Http;

use Restfull\Error\Exceptions;
use Restfull\Filesystem\Folder;

/**
 * Class Server
 * @package Restfull\Http
 */
class Server
{

    /**
     * @var Runner
     */
    private $runner;

    /**
     * @var Aplication
     */
    private $app;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * Server constructor.
     */
    public function __construct()
    {
        if (!defined('RESTFULL')) {
            require_once __DIR__ . '/../../config/pathServer.php';
        }
        $this->request = new Request($_SERVER);
        $this->response = new Response($this->request->server);
        $this->runner = new Runner();
        return $this;
    }

    /**
     * @return Server
     * @throws Exceptions
     */
    public function execute()
    {
        if ($this->files($this->request->route)) {
            $this->runner->run($this->request, $this->response);
        }
        return $this;
    }

    /**
     * @param string $file
     * @return bool
     * @throws Exceptions
     */
    public function files(string $file): bool
    {
        $datas = explode(DS, $file);
        if (in_array($datas[0], ['img', 'files', 'js', 'css']) === false) {
            return true;
        }
        if ((new Folder(ROOT_PATH . $datas[0]))->exists()) {
            if (stripos($datas[0], "img") !== false) {
                $extension = explode(".", $datas[count($datas) - 1]);
                if (in_array($extension[1], ['jpg', 'png'])) {
                    $this->response->file(ROOT_PATH . $file)->body('');
                    return false;
                }
                return true;
            }
            $this->response->file(ROOT_PATH . $file)->body('');
            return false;
        }
        return true;
    }

    /**
     * @return string
     * @throws Exceptions
     */
    public function send()
    {
        return $this->response->send();
    }

}
