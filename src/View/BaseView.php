<?php

namespace Restfull\View;

use Restfull\Core\Instances;
use Restfull\Error\Exceptions;
use Restfull\Event\Event;
use Restfull\Event\EventDispatcherTrait;
use Restfull\Event\EventManager;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 * Class BaseView
 * @package Restfull\View
 */
class BaseView extends View
{

    use EventDispatcherTrait;

    /**
     * BaseView constructor.
     * @param Request $request
     * @param Response $response
     * @param Instances $instance
     * @param array $data
     */
    public function __construct(Request $request, Response $response, Instances $instance, array $data = [])
    {
        $this->instance = $instance;
        $this->auth = $request->auth;
        $this->request = $request;
        $this->response = $response;
        $this->configData($data);
        $this->setCaminho();
        $this->loadHelpers();
        $this->initialize();
        return $this;
    }

    /**
     * @param string|null $helper
     * @return View
     * @throws Exceptions
     */
    public function loadHelpers(string $helper = null): View
    {
        if (isset($helper)) {
            $this->$helper = $this->instance->resolveClass(
                    $this->instance->extension(
                            $this->instance->namespaceClass(
                                    "%s" . DS_REVERSE . "%s" . DS_REVERSE . "%s" . DS_REVERSE . "%s",
                                    [substr(ROOT_APP, -4, -1), MVC[1], SUBMVC[1], $helper . SUBMVC[1]]
                            )
                    ),
                    ['View' => $this]
            );
            return $this;
        }
        $helper = $this->helpersInitialaze();
        foreach ($helper as $value) {
            if (array_key_exists($value, $this->ativationsHelpers) !== false) {
                if (!$this->ativationsHelpers[$value]) {
                    continue;
                }
            }
            $this->$value = $this->instance->resolveClass(
                    $this->instance->extension(
                            $this->instance->namespaceClass(
                                    "%s" . DS_REVERSE . "%s" . DS_REVERSE . "%s" . DS_REVERSE . "%s",
                                    [substr(ROOT_APP, -4, -1), MVC[1], SUBMVC[1], $value . SUBMVC[1]]
                            )
                    ),
                    ['View' => $this]
            );
        }
        return $this;
    }

    /**
     *
     */
    public function initialize()
    {
    }

    /**
     * @return string
     */
    public function action(): string
    {
        ob_start();
        if ($this->layout == 'não existe') {
            $layout = $this->content();
            ob_clean();
            return $layout;
        }
        extract($this->configTemplate);
        if (count($this->data) > 0) {
            extract($this->data);
        }
        if (!$this->request->ajax) {
            $this->eventProcessVerification('beforeRender', [$this->layoutPath]);
            require_once $this->layoutPath;
            $eventLayout = $this->eventProcessVerification('afterRender', [$this->layoutPath]);
            if (!is_null($eventLayout)) {
                $layout = $eventLayout;
            } elseif (empty($layout)) {
                $layout = ob_get_contents();
            }
        } else {
            if ($this->layout != 'default') {
                require_once $this->layoutPath;
                if (empty($layout)) {
                    $layout = ob_get_contents();
                }
            } else {
                $layout = $this->content();
            }
        }
        ob_clean();
        return $layout;
    }

    /**
     * @return string
     */
    public function content(): string
    {
        if (count($this->data) > 0) {
            extract($this->data);
        }
        ob_start();
        $this->eventProcessVerification('beforeRenderFile', [$this->pageContent]);
        require_once $this->pageContent;
        if (empty($content)) {
            $content = ob_get_contents();
        }
        $eventContent = $this->eventProcessVerification('afterRenderFile', [$this->pageContent, $content]);
        ob_clean();
        if (isset($eventContent)) {
            $content = $eventContent;
        }
        return $content;
    }

    /**
     * @param string $event
     * @param array|null $data
     * @return mixed|EventManager|null
     * @throws Exceptions
     */
    public function eventProcessVerification(string $event, array $data = null)
    {
        $event = $this->dispatchEvent(MVC[1] . "." . $event, $data);
        if ($event->result() instanceof Response) {
            return null;
        }
        return $event->result();
    }

    /**
     * @param string|null $model
     * @param array $data
     * @return string
     * @throws Exceptions
     */
    public function jmodel(string $model = null, array $data = []): string
    {
        ob_start();
        require $this->instance->namespaceClass(
                "%s/Template/Layout/jmodel.phtml",
                [substr(str_replace('App', 'src', ROOT_APP), 0, -1)]
        );
        $model = ob_get_contents();
        ob_clean();
        return $model;
    }

    /**
     * @param string|null $content
     * @param array|string[] $options
     * @return BaseView|mixed|string
     */
    public function transfersBlock(string $content = null, array $options = ['tag' => 'script'])
    {
        if (!is_null($content)) {
            $content = str_replace(";", ";" . PHP_EOL, $content);
            if (strripos($content, ";") !== false) {
                $content = substr($content, 0, strripos($content, ";") + 1);
            }
            if (!empty($this->transferBlock[$options['tag']])) {
                $newContent = substr(
                        $this->transferBlock[$options['tag']],
                        strripos($this->transferBlock[$options['tag']], " >") + 2
                );
                $content = substr($newContent, 0, stripos($newContent, "<")) . PHP_EOL . $content;
            }
            $this->transferBlock[$options['tag']] = $this->Html->tag($content, ['element' => $options['tag']]);
            return $this;
        }
        return isset($this->transferBlock[$options['tag']]) ? $this->transferBlock[$options['tag']] : '';
    }

    /**
     * @param string $link
     * @param array $encrypt
     * @param string $urlparams
     * @return string
     * @throws Exceptions
     */
    public function encryptLinks(
            string $link,
            array $encrypt = ['number' => 3, 'encrypt' => ['internal' => false, 'general' => false]],
            string $urlparams = '',
            bool $route = true
    ): string {
        if ($route) {
            if (stripos($link, DS) === false && $link != '#') {
                $link = $this->response->routeIdentify(strtolower($link));
            }
        } else {
            $linkstr_replace('+', DS, $link);
        }
        if (!empty($urlparams)) {
            $link = $link . DS . $urlparams;
        }
        if ($this->encrypting) {
            foreach (['internal', 'general'] as $key) {
                if (!isset($encrypt['encrypt'][$key])) {
                    throw new Exceptions("This {$key} key does not exist.", 404);
                }
            }
            if (((isset($this->data['auth']) && !empty($this->data['auth'])) && $encrypt['encrypt']['internal']) || $encrypt['encrypt']['general']) {
                $security = $this->request->bootstrap('security');
                $link = $security->encrypt($link, $encrypt['number']);
                if ($encrypt['number'] >= 2) {
                    $security->activeEncrypt('file');
                }
            }
        }
        return $link;
    }

    /**
     * @param Event $event
     * @param string $viewFile
     * @return null
     */
    public function beforeRenderFile(Event $event, string $viewFile)
    {
        return null;
    }

    /**
     * @param Event $event
     * @param string $viewFile
     * @return null
     */
    public function beforeRender(Event $event, string $viewFile)
    {
        return null;
    }

    /**
     * @param Event $event
     * @param string $viewFile
     * @return null
     */
    public function beforeLayout(Event $event, string $viewFile)
    {
        return null;
    }

    /**
     * @param Event $event
     * @param string $viewFile
     * @param string $content
     * @return null
     */
    public function afterRenderFile(Event $event, string $viewFile, string $content)
    {
        return null;
    }

    /**
     * @param Event $event
     * @param string $viewFile
     * @return null
     */
    public function afterRender(Event $event, string $viewFile)
    {
        return null;
    }

    /**
     * @param Event $event
     * @param string $viewFile
     * @return null
     */
    public function afterLayout(Event $event, string $viewFile)
    {
        return null;
    }
}