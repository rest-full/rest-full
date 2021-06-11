<?php

namespace Restfull\Controller;

use App\Model\AppModel;
use Restfull\Core\Aplication;
use Restfull\Error\Exceptions;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 * Class Controller
 * @package Restfull\Controller
 */
abstract class Controller
{

    /**
     * @var string
     */
    public $layout = '';

    /**
     * @var array
     */
    public $view = [];

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var Request
     */
    public $request;

    /**
     * @var Response
     */
    public $response;

    /**
     * @var bool
     */
    public $encrypting = false;

    /**
     * @var array
     */
    public $activeHelpers = ['email' => false, 'paginator' => false, 'pdf' => false];

    /**
     * @var AppModel
     */
    protected $model;

    /**
     * @var string
     */
    protected $action = '';

    /**
     * @var bool
     */
    protected $checkDatabase = false;

    /**
     * @var InstanceClass
     */
    protected $instance;

    /**
     * @var mixed
     */
    protected $result;

    /**
     * @var array
     */
    protected $notORM = [];

    /**
     * @var bool
     */
    protected $useORM = false;

    /**
     * @var Aplication
     */
    protected $app;

    /**
     * @var string
     */
    protected $route = '';

    /**
     * @return Controller
     */
    public function setControl(): Controller
    {
        $this->name = $this->request->controller;
        $this->action = $this->request->action;
        $this->route = $this->request->route;
        return $this;
    }

    /**
     * @param bool $start
     * @return Controller
     */
    public function stratORM(bool $start = false): Controller
    {
        $this->useORM = $start;
        return $this;
    }

    /**
     * @param bool $start
     * @return Controller
     */
    public function stratEcrypt(bool $start = false): Controller
    {
        $this->encrypting = $start;
        return $this;
    }

    /**
     * @param string $type
     * @param array $options
     * @return bool
     * @throws Exceptions
     */
    public function validateTableData(string $type, array $options): bool
    {
        unset($options['table']);
        if ($type == 'query') {
            $type = substr($options['query'], 0, stripos($options['query'], ' '));
            if ($type == 'show') {
                $type = 'select';
            }
        } elseif (in_array(
                        $type,
                        ['open', 'all', 'first', 'countRows', 'union', 'nested', 'union and nested']
                ) !== false) {
            $type = 'select';
        }
        if ($type != 'select') {
            $datas = [];
            switch ($type) {
                case "create":
                    for ($a = 0; $a < count($options['fields']); $a++) {
                        $datas[$options['fields'][$a]] = $options['conditions'][$a];
                    }
                    break;
                case "update":
                    $datas = array_merge($options['fileds'], $options['conditions']);
                    break;
                case "delete":
                    $datas = $options['conditions'];
                    break;
            }
            return $this->model->validate($datas);
        }
        return false;
    }

    /**
     * @return array
     */
    public function validationsResult(): array
    {
        return $this->model->getErrorValidate();
    }

    /**
     * @param string $key
     * @param bool $value
     * @return Controller
     * @throws Exceptions
     */
    public function activatingHelpers(string $key, bool $value = false): Controller
    {
        if (in_array($key, ['email', 'paginator', 'pdf', 'html']) === false) {
            throw new Exceptions('Can only activate the helpers mail, paginator, pdf, html of the framework.', 500);
        }
        if ($value) {
            $this->activeHelpers[$key] = $value;
            return $this;
        }
        $this->activeHelpers[$key] = $this->app->checkEmailPdfHtml($key);
        return $this;
    }

    /**
     * @return Controller
     * @throws Exceptions
     */
    public function initializeORM(): Controller
    {
        if (count($this->notORM) > 0 && in_array($this->action, $this->notORM) !== false) {
            $this->useORM = false;
        }
        if ($this->useORM) {
            if ($this->request->bootstrap('database')->validNotEmpty()) {
                throw new Exceptions(
                        'One of the keys must be empty and the host, dbname, user and pass keys cannot be empty.', 600
                );
            }
            $this->model = $this->instance->resolveClass(
                    $this->instance->namespaceClass(
                            "%s" . DS_REVERSE . "%s" . DS_REVERSE . "App%s",
                            [substr(ROOT_APP, -4, -1), MVC[2]['app'], MVC[2]['app']]
                    ),
                    ['http' => ['request' => $this->request, 'response' => $this->response]]
            );
            $this->app = new Aplication();
            $this->checkDatabase = $this->app->bootstrapDatabase('default', [], 'validate');
        }
        return $this;
    }

    /**
     * @param string $url
     * @return Controller
     */
    public function redirect(string $url): Controller
    {
        $this->route = $url;
        return $this;
    }

    /**
     * @param string $name
     * @param null $value
     * @return Controller
     */
    public function set(string $name, $value = null): Controller
    {
        if (isset($this->view)) {
            $this->view = array_merge($this->view, [$name => $value]);
        } else {
            $this->view[$name] = $value;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->route;
    }

    /**
     * @return string
     */
    public function newAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $type
     * @param array $table
     * @param array $options
     * @return array
     */
    public function validateAndAlignData(string $type, array $table, array $options): array
    {
        if (isset($table['table'])) {
            $table['main'] = [$table];
            unset($table['table']);
            if (isset($table['alias'])) {
                unset($table['alias']);
            }
        }
        $count = 0;
        foreach (array_keys($options) as $key) {
            if (in_array($key, ['nested', 'unin']) === false) {
                if (is_string($key)) {
                    $count++;
                }
            }
        }
        if (!isset($options['query'])) {
            if (isset($options['nested'])) {
                $nested = $options['nested'];
                unset($options['nested']);
            }
            if (isset($options['union'])) {
                $union = $options['union'];
                unset($options['union']);
            }

            if ($count > 0) {
                $keys = ['fields', 'join', 'conditions', 'group', 'having', 'order', 'limit'];
                foreach ($keys as $key) {
                    if (isset($options[$key])) {
                        $newOptions[0][$key] = $options[$key];
                        unset($options[$key]);
                    }
                }
                $options = $newOptions;
            }
        }
        $limit = 0;
        for ($a = 0; $a < count($table['main']); $a++) {
            if (isset($options[$a])) {
                $data[$a] = [];
                if (isset($options[$a]['fields'])) {
                    foreach ($options[$a]['fields'] as $value) {
                        $data[$a]['fields'][] = $value;
                    }
                }
                if (isset($options[$a]['limit'])) {
                    $limit++;
                }
            }
            if (isset($options[$a]['join'])) {
                foreach ($options[$a]['join'] as $join) {
                    $joins[$table['main'][$a]['table']][] = [
                            'table' => $join['table'],
                            'alias' => (isset($join['alias']) ? $join['alias'] : '')
                    ];
                }
            } else {
                $joins[$table['main'][$a]['table']] = null;
            }
        }
        if (in_array($type, ['union', 'nested', 'union and nested', 'nested and union']) !== false) {
            if (stripos($type, 'and')) {
                $datas = explode('and', $type);
                for ($a = 0; $a < count($datas); $a++) {
                    $newData = trim($datas[$a]);
                    if ($newData == 'union') {
                        if (!isset(${$newData})) {
                            ${$newData} = 0;
                        }
                    }
                    $options[$newData] = ${$newData};
                }
            } else {
                if ($type == 'union') {
                    if (!isset(${$type})) {
                        ${$type} = 0;
                    }
                }
                $options[$type] = ${$type};
            }
            if (isset($options['nested']['fields'])) {
                $data = [];
                $data[0]['fields'] = $options['nested']['fields'];
            }
        }
        return [$table, $options, $joins, (isset($data) ? $data : []), $limit];
    }

    /**
     * @param array $conditions
     * @return bool
     */
    public function validateAuth(array $conditions): bool
    {
        $access = 0;
        if (isset($options['conditions'])) {
            foreach (array_keys($options['conditions']) as $condition) {
                if (!in_array(substr($condition, 0, -2), $this->Auth->config()['authenticate'])) {
                    $access++;
                }
            }
        }
        if ($access != 0) {
            return true;
        }
        return false;
    }


    /**
     * @param array $table
     * @throws Exceptions
     */
    public function validUserModelAndNameTable(array $table): Controller
    {
        if (!$this->useORM) {
            throw new Exceptions(
                    'You did not instantiate the model. To instantiate, go to the AppController and type $this->useORM = true in the initialize method.',
                    404
            );
        }
        if (!array_key_exists('main', $table)) {
            throw new Exceptions('The table you are using cannot be an array with the main key.', 404);
        }
        return $this;
    }

    /**
     * @param string $view
     * @param bool $changeRequest
     * @return Controller
     */
    protected function render(string $view, bool $changeRequest = false): Controller
    {
        $this->action = $view;
        if ($changeRequest) {
            $this->request->action = $view;
        }
        return $this;
    }

}