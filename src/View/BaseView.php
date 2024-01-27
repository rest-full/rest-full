<?php

declare(strict_types=1);

namespace Restfull\View;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;
use Restfull\Event\Event;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 *
 */
class BaseView extends View
{

    /**
     * @var array
     */
    protected $dataLayout = [];

    /**
     * @var array
     */
    private $modal = ['name' => '', 'count' => 1];

    /**
     * @param Request $request
     * @param Response $response
     * @param Instances $instance
     * @param array $datas
     */
    public function __construct(Request $request, Response $response, Instances $instance, array $datas = [])
    {
        $this->instance = $instance;
        $this->auth = $request->auth;
        $this->request = $request;
        $this->response = $response;
        $this->setCaminho()->configData($datas)->loadHelpers()->initialize()->instancesClass(
            ['Utility', 'Translator'],
            ['lenguage' => 'pt_BR', 'instance' => $this->instance]
        );
        return $this;
    }

    /**
     * @return BaseView
     */
    public function initialize(): BaseView
    {
        return $this;
    }

    /**
     * @param string|null $helper
     *
     * @return View
     */
    public function loadHelpers(string $helper = null): View
    {
        $helpers = $this->helpersInitialaze();
        if (isset($helper)) {
            if ($this->controller === 'Error') {
                if (in_array($helper, $helpers) === false) {
                    return $this;
                }
            }
            $this->{$helper} = $this->instance->resolveClass(
                $this->instance->locateTheFileWhetherItIsInTheAppOrInTheFramework(
                    ROOT_NAMESPACE[1] . DS_REVERSE . MVC[1] . DS_REVERSE . SUBMVC[1] . DS_REVERSE . $helper . SUBMVC[1]
                ),
                ['view' => $this]
            );
            return $this;
        }
        foreach ($helpers as $helper) {
            if (array_key_exists($helper, $this->ativationsHelpers) !== false) {
                if (!$this->ativationsHelpers[$helper]) {
                    continue;
                }
            }
            $this->{$helper} = $this->instance->resolveClass(
                $this->instance->locateTheFileWhetherItIsInTheAppOrInTheFramework(
                    ROOT_NAMESPACE[1] . DS_REVERSE . MVC[1] . DS_REVERSE . SUBMVC[1] . DS_REVERSE . $helper . SUBMVC[1]
                ),
                ['view' => $this]
            );
        }
        return $this;
    }

    /**
     * @param bool $valid
     * @return $this
     */
    public function encryptValid(bool $valid): BaseView
    {
        $this->encrypted = $valid;
        return $this;
    }

    /**
     * @param mixed $mixed
     */
    public function dd($mixed)
    {
        var_dump($mixed);
        exit;
    }

    /**
     * @return string
     * @throws Exceptions
     */
    public function action(): string
    {
        ob_start();
        extract($this->configTemplate);
        if (count($this->dataLayout) > 0) {
            extract($this->dataLayout);
        }
        $this->eventProcessVerification('beforeRender', [$this->layout]);
        require_once $this->layout;
        $eventLayout = $this->eventProcessVerification('afterRender', [$this->layout]);
        if (!is_null($eventLayout)) {
            $layout = $eventLayout;
        } elseif (empty($layout)) {
            $layout = ob_get_contents();
        }
        ob_clean();
        return $layout;
    }

    /**
     * @return string
     * @throws Exceptions
     */
    public function content(): string
    {
        if (count($this->datas) > 0) {
            extract($this->datas);
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
     * @param string|null $mode
     * @param array $data
     *
     * @return string
     */
    public function modal(string $mode = null, array $data = []): string
    {
        if ($mode === $this->modal['name']) {
            $this->modal['count']++;
        }
        $count = $this->modal['count'];
        if (count($data) > 0) {
            extract($data);
        }
        ob_start();
        require substr(RESTFULL, 0, -1) . DS . 'Template' . DS . 'Layout' . DS . "modal.phtml";
        if (empty($modal)) {
            $modal = ob_get_contents();
        }
        ob_clean();
        if (empty($this->modal['name']) || $mode !== $this->modal['name']) {
            $this->modal['name'] = $mode;
        }
        return $modal;
    }

    /**
     * @param string $link
     * @param string $prefix
     * @param array $urlparams
     *
     * @return string
     * @throws Exceptions
     */
    public function encryptLinks(string $link, string $prefix, array $urlparams = []): string
    {
        if ($link !== '#') {
            if (stripos($link, DS) === false) {
                $link = $this->response->identifyRouteByName(strtolower($link), $prefix);
                if (stripos($link, '+') !== false) {
                    $link = ucwords(str_replace('+', DS, $link));
                }
            }
            if ($prefix !== 'app') {
                $link = $prefix . DS . $link;
            }
            if (!empty($urlparams[0])) {
                $params = stripos($urlparams[0], DS) !== false ? explode(DS, $urlparams[0]) : [$urlparams[0]];
            }
            $link = explode(DS, $link);
            $a = 0;
            foreach ($link as $key => $value) {
                if (stripos($value, '{') !== false) {
                    if (isset($params[$a])) {
                        $link[$key] = $params[$a];
                    } else {
                        unset($link[$key]);
                    }
                    $a++;
                }
            }
            $link = implode(DS, $link);
            if ($this->encrypted) {
                $hash = $this->request->bootstrap('hash');
                if (!$hash->alfanumero()) {
                    $hash->changeConfig($hash->LevelEncrypt(), true);
                }
                $link = $hash->encrypt($link);
                if ($this->request->shorten) {
                    $link = $hash->shortenDB(
                        $link,
                        [
                            'idUser' => $this->datas['auth']['id'],
                            'methodused' => $urlparams[1] ?? 'normal'
                        ]
                    );
                }
            }
        }
        return $link;
    }

    /**
     * @param string $class
     * @param string|array $method
     * @param array $options
     * @param string $type
     *
     * @return mixed
     */
    public function returnFromAbstractClassOrResult(string $class, $methods, array $options)
    {
        if (preg_match('/[A-Z]/i', substr($class, 0, 1)) === false) {
            $class = ucFirst($class);
        }
        $object = $this->request->bootstrap('plugins')->startClass($class);
        if (!isset($options['helper'])) {
            $options['helper'] = $this->instance->resolveClass(
                ROOT_NAMESPACE[0] . DS_REVERSE . 'View' . DS_REVERSE . 'Helper',
                ['view' => $this]
            );
        }
        $this->{$class} = !isset($type) ? $object->treatments($methods, $options) : $object->treatment(
            $methods,
            $options,
            'object'
        );
        return $this;
    }

    /**
     * @param Event $event
     * @param string $viewFile
     *
     * @return null
     */
    public function beforeRenderFile(Event $event, string $viewFile)
    {
        return null;
    }

    /**
     * @param Event $event
     * @param string $viewFile
     *
     * @return null
     */
    public function beforeRender(Event $event, string $viewFile)
    {
        return null;
    }

    /**
     * @param Event $event
     * @param string $viewFile
     *
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
     *
     * @return null
     */
    public function afterRenderFile(
        Event $event,
        string $viewFile,
        string $content
    ) {
        return null;
    }

    /**
     * @param Event $event
     * @param string $viewFile
     *
     * @return null
     */
    public function afterRender(Event $event, string $viewFile)
    {
        return null;
    }

    /**
     * @param Event $event
     * @param string $viewFile
     *
     * @return null
     */
    public function afterLayout(Event $event, string $viewFile)
    {
        return null;
    }

    /**
     * @return BaseView
     */
    protected function identifyDatasLayout(array $keys = []): BaseView
    {
        $keys = $this->controller === 'Error' ? ['title', 'icon'] : array_merge([
            'title',
            'icon',
            'description',
            'url',
            'app',
            'author',
            'page',
            'creator',
            'site',
            'domain',
            'auth',
            'scriptTime'
        ], $keys);
        $extra = [];
        foreach ($keys as $key) {
            if (isset($this->datas[$key])) {
                if (in_array($key, ['title', 'icon']) !== false) {
                    $$key = $this->datas[$key];
                } elseif (in_array($key, ['auth', 'scriptTime']) !== false) {
                    $extra[$key] = $this->datas[$key];
                } else {
                    $keyFather = in_array($key, ['description', 'url']) !== false ? 'optimize' : (in_array(
                        $key,
                        ['app', 'author', 'page']
                    ) !== false ? 'face' : 'twitter');
                    $extra[$keyFather][$key] = $this->datas[$key];
                }
                unset($this->datas[$key]);
            }
        }
        $this->optimizerHead($title, $icon, $extra);
        return $this;
    }

}
