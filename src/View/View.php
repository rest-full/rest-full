<?php

namespace Restfull\View;

use Restfull\Authentication\Auth;
use Restfull\Core\Instances;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 * Class View
 * @package Restfull\View
 */
abstract class View
{

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
     * @var string
     */
    public $layout = 'default';

    /**
     * @var array
     */
    public $ativationsHelpers = [];
    /**
     * @var array
     */
    protected $data = [];
    /**
     * @var array
     */
    protected $transferBlock = [];
    /**
     * @var Auth
     */
    protected $auth;
    /**
     * @var Instances
     */
    protected $instance;
    /**
     * @var array
     */
    protected $configTemplate;
    /**
     * @var bool
     */
    protected $encrypting;
    /**
     * @var string
     */
    protected $pageContent;
    /**
     * @var string
     */
    protected $layoutPath;

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
     * @return array
     */
    public function helpersInitialaze(): array
    {
        $helpers = ['Html', 'Flash', 'Form', 'Paginator', 'Email', 'Pdf'];
        foreach (array_keys($this->data) as $key) {
            if ($key == "traces") {
                $helpers = ['Html', 'Flash'];
                break;
            }
        }
        return $helpers;
    }

    /**
     * @param array $datas
     * @return View
     */
    public function viewPath(array $datas): View
    {
        $this->pageContent = $datas[1];
        $this->layoutPath = $datas[0];
        return $this;
    }

    /**
     * @return array
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * @param string $layout
     * @return View
     */
    public function layout(string $layout): View
    {
        if (!empty($layout)) {
            $this->layout = $layout;
        }
        return $this;
    }

    /**
     * @param string $name
     * @param array $data
     * @return View
     */
    public function setData(string $name, $data = []): View
    {
        $this->data[$name] = $data;
        return $this;
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
     * @return View
     */
    public function configData(array $data): View
    {
        $config = ['js' => [], 'css' => []];
        if (isset($data['js']) || isset($data['scripts'])) {
            if (isset($data['js'])) {
                $config['js'] = $data['js'];
                unset($data['js']);
            } else {
                $config['js'] = $data['scripts'];
                unset($data['scripts']);
            }
        }
        if (isset($data['css']) || isset($data['styles'])) {
            if (isset($data['css'])) {
                $config['css'] = $data['css'];
                unset($data['css']);
            } else {
                $config['css'] = $data['styles'];
                unset($data['styles']);
            }
        }
        foreach ($config as $key => $values) {
            $length = $key == 'js' ? -3 : -4;
            $Values = [];
            for ($a = 0; $a < count($values); $a++) {
                $Values[] = stripos($values[$a], '.' . $key) !== false ? substr($values[$a], 0, $length) : $values[$a];
            }
            $Config[$key] = $Values;
        }
        $this->configTemplate = [
                'scripts' => array_merge(['script.min'], $Config['js']),
                'styles' => array_merge(['style.min'], $Config['css'])
        ];
        $this->data = $data;
        return $this;
    }

    /**
     * @param bool $encryt
     * @return View
     */
    public function encrypt(bool $encryt): View
    {
        $this->encrypting = $encryt;
        return $this;
    }

}
