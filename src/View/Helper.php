<?php

namespace Restfull\View;

use Restfull\Core\Instances;
use Restfull\Error\Exceptions;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 * Class Helper
 * @package Restfull\View
 */
class Helper
{

    /**
     * @var View
     */
    protected $view;

    /**
     * @var string
     */
    protected $route = '';

    /**
     * @var int
     */
    protected $count_base = 0;

    /**
     * @var string
     */
    protected $renders = '';

    /**
     * @var array
     */
    protected $templater = [];

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var Instances
     */
    private $instance;

    /**
     * Helper constructor.
     * @param BaseView $view
     * @param array|null $templater
     */
    public function __construct(BaseView $view, array $templater = null)
    {
        $this->view = $view;
        $this->http($this->view->request, $this->view->response);
        $this->route();
        if (isset($templater)) {
            $this->templater = count($this->templater) > 0 ? array_merge($this->templater, $templater) : $templater;
        }
        $this->instance = $this->view->instance();
        return $this;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Helper
     */
    private function http(Request $request, Response $response): Helper
    {
        $this->request = $request;
        $this->response = $response;
        return $this;
    }

    /**
     * @return Helper
     */
    public function route(): Helper
    {
        $this->count_base = strlen($this->view->request->base);
        $cut = !empty($this->view->request->base) ? substr($this->view->request->base, 1) : 'webroot';
        $this->route = DS . substr(ROOT_PATH, stripos(ROOT_PATH, $cut), -1);
        return $this;
    }

    /**
     * @param string $helper
     * @param array|null $options
     * @return string
     * @throws Exceptions
     */
    public function formatTemplate(string $helper, array $options = null): string
    {
        $template = $this->templater[substr($helper, stripos($helper, "::") + 2)];
        for ($a = 0; $a < substr_count($template, '%s'); $a++) {
            if (!isset($options[$a])) {
                $options[$a] = null;
            }
        }
        return $this->instance->namespaceClass($template, $options, true);
    }

    /**
     * @param object $data
     * @param string $method
     * @return array
     */
    public function ordenation(object $data, string $method = 'values'): array
    {
        $newData = [];
        $keys = 0;
        switch ($method) {
            case"keys":
                foreach ($data as $value) {
                    $newData[$keys] = $value;
                    $keys++;
                }
                break;
            case"keys and values":
                sort($data);
                foreach ($data as $value) {
                    $newData[$keys] = $value;
                    $keys++;
                }
                break;
            default:
                $newData = $data;
                sort($newData);
                break;
        }
        return $newData;
    }

    /**
     * @param array $options
     * @param string|null $name
     * @return string
     * @throws Exceptions
     */
    protected function formatAttributes(array $options = [], string $name = null): string
    {
        $attr = "";
        if (!empty($options)) {
            if (isset($name)) {
                $edit = [
                        'graphcis' => ['property' => 'og'],
                        'twitter' => ['name' => 'twitter'],
                        'facebook' => ['property' => 'fb'],
                        'publisher' => ['author' => 'article']
                ];
                if (in_array($name, array_keys($edit))) {
                    foreach (array_keys($edit[$name])[0] as $prefix) {
                        foreach ($options as $key => $value) {
                            $attr .= ' ' . trim($edit[$name][$prefix] . ":" . $key) . '="' . trim(
                                            $key
                                    ) . '" content="' . trim($value) . '"';
                            unset($options[$key]);
                        }
                    }
                } else {
                    foreach ($options as $key => $value) {
                        $attr .= ' intemprop="' . trim($key) . '" content="' . trim($value) . '"';
                    }
                }
            } else {
                foreach ($options as $key => $value) {
                    if (is_array($value)) {
                        throw new Exceptions("The value of the {$key} variable is an array and must be a string.", 404);
                    }
                    $attr .= ' ' . trim($key) . '="' . trim($value) . '"';
                }
            }
        }
        return $attr;
    }

    /**
     * @param object $context
     * @return array
     */
    protected function formatInputs(object $context): array
    {
        $contextnew = [];
        $columns = $context->columns;
        if (isset($context->join)) {
            $columns = array_merge($columns, $context->join);
        }
        $keysTable = array_keys($columns);
        for ($a = 0; $a < count($keysTable); $a++) {
            $table = $keysTable[$a];
            $count = ($a > 0) ? count($contextnew) : '0';
            for ($b = $count; $b < $count + count($columns[$table]); $b++) {
                if (!in_array($columns[$table][$b - $count], $contextnew)) {
                    if (stripos($columns[$table][$b - $count]['type'], "(") !== false) {
                        $type = explode("(", str_replace(")", '', $columns[$table][$b - $count]['type']));
                        $max = array_pop($type);
                        $type = array_pop($type);
                    } else {
                        $type = $columns[$table][$b - $count]['type'];
                        $max = null;
                    }
                    $name = $columns[$table][$b - $count]['name'];
                    $value = stripos($max, ",") ? explode(",", $max) : '';
                    if ($type != 'int' && $value != '') {
                        for ($c = 0; $c < count($value); $c++) {
                            $datatype = $this->typeInput(
                                    [
                                            'type' => $type,
                                            'name' => $name,
                                            'table' => substr($table, 0, -1),
                                            'dado' => $value[$c]
                                    ]
                            );
                            $contextnew[$b] = [
                                    'required' => $columns[$table][$b - $count]['required'],
                                    'name' => $name,
                                    'type' => $datatype,
                                    'max' => $max
                            ];
                            if (in_array($datatype, ['radio', 'select', 'checkbox'])) {
                                $contextnew[$b] = array_merge($contextnew[$b], ['values' => $value[$c]]);
                            }
                        }
                    } else {
                        $datatype = $this->typeInput(
                                [
                                        'type' => $type,
                                        'name' => $name,
                                        'table' => substr($table, 0, -1),
                                        'dado' => $value
                                ]
                        );
                        $contextnew[$b] = [
                                'required' => $columns[$table][$b - $count]['required'],
                                'name' => $name,
                                'type' => $datatype,
                                'max' => $max
                        ];
                    }
                }
            }
            ksort($contextnew);
        }
        return $contextnew;
    }

    /**
     * @param array $data
     * @return string
     */
    private function typeInput(array $data = []): string
    {
        if (in_array($data['type'], ['date', 'time', 'datetime'])) {
            $type = $data['type'];
        } elseif (in_array($data['type'], ['text', 'mediumtext', 'longtext']) !== false) {
            $type = 'textarea';
        } elseif ($data['type'] == "enum") {
            $enum = count($data['dado']);
            if ($enum <= 2) {
                $type = 'radio';
            } else {
                $type = 'select';
            }
        } elseif ($data['type'] == 'set') {
            $type = 'checkbox';
        } else {
            $type = 'text';
        }
        return $type;
    }

}
