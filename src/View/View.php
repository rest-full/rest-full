<?php

declare(strict_types=1);

namespace Restfull\View;

use Restfull\Authentication\Auth;
use Restfull\Container\Instances;
use Restfull\Event\EventDispatcherTrait;
use Restfull\Event\EventManager;
use Restfull\Http\Request;
use Restfull\Http\Response;
use Restfull\Utility\Translator;

/**
 *
 */
abstract class View
{

    use EventDispatcherTrait;

    /**
     * @var string
     */
    public $controller = '';

    /**
     * @var string
     */
    public $action = '';

    /**
     * @var Response
     */
    public $response;

    /**
     * @var Request
     */
    public $request;

    /**
     * @var array
     */
    public $ativationsHelpers = [];

    /**
     * @var barray
     */
    public $encrypting = [];

    /**
     * @var Instances
     */
    public $instance;

    /**
     * @var array
     */
    protected $datas = [];

    /**
     * @var Auth
     */
    protected $auth;

    /**
     * @var array
     */
    protected $configTemplate;

    /**
     * @var string
     */
    protected $pageContent;

    /**
     * @var string
     */
    protected $layout = '';

    /**
     * @var Translator
     */
    protected $Translator;

    /**
     * @var bool
     */
    protected $encrypted = false;

    /**
     * @return View
     */
    public function setCaminho(): View
    {
        $this->controller = $this->request->controller;
        $this->action = $this->request->action;
        return $this;
    }

    /**
     * @return Translator
     */
    public function translator(): Translator
    {
        return $this->Translator;
    }

    /**
     * @return View
     */
    public function instancesClass(array $classArray, array $datas): View
    {
        $count = count($classArray);
        for ($a = 0; $a < $count; $a++) {
            $format[] = '%s';
            if ($a == ($count - 1)) {
                $class = $classArray[$a];
            }
        }
        $this->{$class} = $this->instance->resolveClass(
            $this->instance->assemblyClassOrPath(
                '%s' . DS_REVERSE . implode(DS_REVERSE, $format),
                array_merge([ROOT_NAMESPACE[0]], $classArray)
            ),
            $datas
        );
        return $this;
    }

    /**
     * @return array
     */
    public function helpersInitialaze(): array
    {
        $helpers = ['Html', 'Flash', 'Form', 'Paginator', 'Email', 'Pdf', 'Optimizer'];
        foreach (array_keys($this->datas) as $key) {
            if ($key === "traces") {
                $helpers = ['Html', 'Flash', 'Optimizer'];
                break;
            }
        }
        return $helpers;
    }

    /**
     * @param array $datas
     * @param string $type
     *
     * @return View
     */
    public function viewPath(array $datas, string $type = 'layout'): View
    {
        if (stripos($datas[1], $this->action) === false) {
            $datas[1] = substr($datas[1], strripos($datas[1], DS_REVERSE) + 1) . $this->action . '.phtml';
        }
        $this->pageContent = $datas[1];
        if ($type === 'layout') {
            $this->layout = $datas[0];
        }
        return $this;
    }

    /**
     * @param string $key
     *
     * @return View
     */
    public function unsetData(string $key): View
    {
        unset($this->datas[$key]);
        return $this;
    }

    /**
     * @return array
     */
    public function datas(): array
    {
        return $this->datas;
    }

    /**
     * @param string $name
     * @param array $data
     *
     * @return View
     */
    public function setDatas(string $name, $data = []): View
    {
        $this->datas[$name] = $data;
        return $this;
    }

    /**
     * @param string $event
     * @param array $data
     * @param object|null $object
     *
     * @return mixed|EventManager|null
     */
    public function eventProcessVerification(string $event, array $data = [], object $object = null)
    {
        $event = $this->dispatchEvent($this->instance, MVC[1] . "." . $event, $data, $object);
        return $event->result();
    }

    /**
     * @return Instances
     */
    public function instance(): Instances
    {
        return $this->instance;
    }

    /**
     * @param array $data
     *
     * @return View
     */
    public function configData(array $datas): View
    {
        $config = ['js' => [], 'css' => []];
        if (isset($datas['js']) || isset($datas['scripts'])) {
            if (isset($datas['js'])) {
                $config['js'] = $datas['js'];
                unset($datas['js']);
            } else {
                $config['js'] = $datas['scripts'];
                unset($datas['scripts']);
            }
        }
        if (isset($datas['css']) || isset($datas['styles'])) {
            if (isset($datas['css'])) {
                $config['css'] = $datas['css'];
                unset($datas['css']);
            } else {
                $config['css'] = $datas['styles'];
                unset($datas['styles']);
            }
        }
        $this->configTemplate = [
            'scripts' => array_merge(
                [$this->controller === 'Error' ? strtolower($this->controller) . '.min' : 'script.min'],
                $config['js']
            ),
            'styles' => array_merge(
                [$this->controller === 'Error' ? strtolower($this->controller) . '.min' : 'style.min'],
                $config['css']
            )
        ];
        $this->datas = $datas;
        return $this;
    }

    /**
     * @param string $title
     * @param string $icon
     * @param array $optimizers
     * @return View
     */
    protected function optimizerHead(string $title, string $icon, array $optimizers): View
    {
        $calledControllerError = false;
        $keys = ['optimize', 'face', 'twitter'];
        $auth = false;
        if (isset($extra['auth'])) {
            $auth = true;
            $this->dataLayout['auth'];
            if (isset($extra['scriptTime'])) {
                $this->dataLayout['scriptTime'] = $extra['scriptTime'];
            }
            unset($extra['auth'], $extra['scriptTime']);
        }
        if ($this->controller == 'Error') {
            $keys = ['optimize'];
            $auth = false;
            $calledControllerError = !$calledControllerError;
        }
        $count = count($optimizers);
        foreach ($keys as $key) {
            $this->dataLayout[$key] = ($count > 0) ? $optimizers[$key] : [];
            if ($key == 'optimize') {
                $this->dataLayout[$key] = array_merge($this->dataLayout[$key], ['title' => $title, 'icon' => $icon]);
            }
        }
        if (!$calledControllerError && !$auth) {
            $this->dataLayout['graphics']['title'] = $title;
        }
        return $this;
    }

}
