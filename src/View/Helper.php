<?php

declare(strict_types=1);

namespace Restfull\View;

use ErrorException;
use Restfull\Container\Instances;
use Restfull\Error\Exceptions;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 *
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
    protected $template = [];

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var array
     */
    protected $repositories = [];

    /**
     * @var Instances
     */
    private $instance;

    /**
     * @param BaseView $view
     * @param array|null $templater
     */
    public function __construct(BaseView $view, array $templater = null)
    {
        $this->view = $view;
        $this->http($this->view->request, $this->view->response);
        $this->route();
        if (isset($templater)) {
            $this->template($templater);
        }
        $this->instance = $this->view->instance();
        return $this;
    }

    /**
     * @param Request $request
     * @param Response $response
     *
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
        $this->route = DS . substr(ROOT_PATH, stripos(ROOT_PATH, $cut));
        return $this;
    }

    /**
     * @param array $templater
     *
     * @return Helper
     */
    public function template(array $templater): Helper
    {
        $this->template = count($this->template) > 0 ? array_merge($this->template, $templater) : $templater;
        return $this;
    }

    /**
     * @return Instances
     */
    public function instance()
    {
        return $this->instance;
    }

    /**
     * @param string $helper
     * @param array|null $options
     *
     * @return string
     */
    public function formatTemplate(string $helper, array $options = null): string
    {
        $template = stripos($helper, '::') !== false ? $this->template[substr(
            $helper,
            stripos($helper, "::") + 2
        )] : $helper;
        for ($a = 0; $a < substr_count($template, '%s'); $a++) {
            if (!isset($options[$a])) {
                $options[$a] = null;
            }
        }
        return $this->instance->assemblyClassOrPath($template, $options, true);
    }

    /**
     * @param object $query
     *
     * @return int
     */
    public function counts(object $object, bool $repository = true): int
    {
        $counts = 0;
        if ($repository) {
            foreach ($object as $key => $values) {
                if (in_array($key, ['repository', 'count']) === false) {
                    $counts++;
                }
            }
            return $counts;
        }
        foreach ($object as $key => $values) {
            $counts++;
        }
        return $counts;
    }

    /**
     * @param BaseView $view
     *
     * @return array
     */
    public function findRepositoryInViewData(array $datas): array
    {
        foreach ($datas as $name => $data) {
            if (is_object($data)) {
                $this->repositories[$name] = $data->repository;
                unset($data->repository);
            }
        }
        return $datas;
    }

    /**
     * @param string $text
     *
     * @return string
     * @throws ErrorException
     */
    public function translator(string $text, string $convert = '', bool $translate = true): string
    {
        if ($translate) {
            return $this->view->translator()->translation($text, $convert);
        }
        if (!empty($convert)) {
            $text = $this->view->translator()->convert($text, $convert);
        }
        return $text;
    }

    /**
     * @param string $helper
     *
     * @return object
     */
    public function ohterHelper(string $helper): object
    {
        if (strtoupper($helper[0]) !== $helper[0]) {
            $helper = ucfirst($helper);
        }
        return $this->view->{$helper};
    }

    /**
     * @param string $icon
     *
     * @return array
     * @throws Exceptions
     */
    public function favicon(string $icon): array
    {
        $icons = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Utility' . DS_REVERSE . 'Icons',
            ['instance' => $this->instance, 'icon' => $icon]
        );
        foreach ($icons->addIco() as $icon) {
            $baseUrl = URL . DS;
            if (strripos($icon, 'webroot') !== false) {
                $icon = substr($icon, strripos($icon, 'webroot'));
            }
            if (!empty($this->request->base)) {
                $baseUrl .= $this->request->base . DS;
            }
            $favicon[] = $icons->typeOptions($baseUrl . $icon);
        }
        $favicon[] = ['name' => 'msapplication-TileColor', 'content' => '#FFFFFF'];
        $favicon[] = ['name' => 'msapplication-TileImage', 'content' => $baseUrl . 'webroot/favicons/favicon-144.png'];
        $favicon[] = ['name' => 'msapplication-config', 'content' => $baseUrl . 'webroot/favicons/browserconfig.xml'];
        return $favicon;
    }

    /**
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    protected function formatAttributes(array $options = []): string
    {
        $attr = "";
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                if (is_array($value)) {
                    throw new Exceptions("The value of the {$key} variable is an array and must be a string.", 404);
                }
                if ($key === 'id') {
                    $value = $this->utf8Fix($value);
                }
                $attr .= ' ' . trim($key) . '="' . trim($value) . '"';
            }
        }
        return $attr;
    }

    /**
     * @param string $msg
     *
     * @return string
     */
    public function utf8Fix(string $msg): string
    {
        $notUtf8 = [
            'á' => 'a',
            'à' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
            'ç' => 'c',
            'Á' => 'A',
            'À' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'É' => 'E',
            'È' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Í' => 'I',
            'Ì' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ó' => 'O',
            'Ò' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'Ú' => 'U',
            'Ù' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
            'Ç' => 'C'
        ];
        $utf8 = array_keys($notUtf8);
        $count = count($utf8);
        for ($a = 0; $a < $count; $a++) {
            if (stripos($msg, $utf8[$a])) {
                $msg = str_replace($notUtf8[$utf8[$a]], $utf8[$a], $msg);
            }
        }
        return $msg;
    }

    /**
     * @param object $context
     *
     * @return array
     */
    protected function formatInputs(array $values): array
    {
        foreach ($this->repositories as $table => $context) {
            $object = $context->typeExecuteQuery == 'all' ? $values[$table]->{0} : $values[$table];
            $columns = $context->columns;
            $outhersInput[$table] = $contextnew[$table] = $join = $keys = [];
            foreach (explode(', ', $context->name) as $name) {
                if ($context->connectColumnNameWithTableName) {
                    $join = array_keys($context->join[$name]);
                    $columns = array_merge($columns, $context->join[$name]);
                }
                $tableOfTheseColumns = array_keys($columns);
                $computo = count($tableOfTheseColumns);
                for ($a = 0; $a < $computo; $a++) {
                    $count = ($a > 0) ? count($contextnew[$table]) : '0';
                    $countComputo = $count + count($columns[$tableOfTheseColumns[$a]]);
                    for ($b = $count; $b < $countComputo; $b++) {
                        $column = $columns[$tableOfTheseColumns[$a]][$b - $count]['name'];
                        $key = $this->nameColumn($column, $table, $join, $keys);
                        if (isset($object->{$key})) {
                            $keys[] = $key;
                            if (!in_array($columns[$tableOfTheseColumns[$a]][$b - $count], $contextnew[$table])) {
                                $datatype = $columns[$tableOfTheseColumns[$a]][$b - $count]['type'];
                                if (stripos($datatype, 'enum') !== false) {
                                    $type = substr($datatype, 0, stripos($datatype, '('));
                                    $outhersInput[$table][$column] = explode(
                                        ',',
                                        str_replace(
                                            "'",
                                            '',
                                            str_replace(['(', ')'], '', substr($datatype, stripos($datatype, '(')))
                                        )
                                    );
                                    $max = null;
                                } else {
                                    if (stripos($datatype, "(") !== false) {
                                        $type = explode("(", str_replace(")", '', $datatype));
                                        $max = array_pop($type);
                                        $type = array_pop($type);
                                    } else {
                                        $type = $columns[$tableOfTheseColumns[$a]][$b - $count]['type'];
                                        $max = null;
                                    }
                                    $outhersInput[$table][$column] = '';
                                }
                                $contextnew[$table][$key] = [
                                    'required' => $columns[$tableOfTheseColumns[$a]][$b - $count]['required'],
                                    'type' => $this->typeInput($type),
                                    'max' => $max
                                ];
                            }
                        }
                    }
                }
            }
            $newValues[$table] = $values[$table];
        }
        return ['newColumns' => $contextnew, 'selects' => $outhersInput, 'values' => $newValues];
    }

    /**
     * @param string $column
     * @param string $table
     * @param array $joins
     * @param array $columns
     * @return string
     */
    private function nameColumn(string $column, string $table, array $joins, array $columns): string
    {
        if (count($columns) > 0) {
            $nameTable = '';
            if (in_array($table, $joins) !== false) {
                $nameTable = ucfirst($table);
            }
            if ($column === 'id') {
                return $column . $nameTable;
            } else {
                if (in_array($column, $columns) !== false && !empty($nameTable)) {
                    return $column . $nameTable;
                }
                return $column;
            }
        }
        return $column;
    }

    /**
     * @param string $dataType
     *
     * @return string
     */
    private function typeInput(string $dataType): string
    {
        if (in_array($dataType, ['date', 'time', 'datetime'])) {
            $type = 'text';
        } elseif (in_array($dataType, ['text', 'mediumtext', 'longtext']) !== false) {
            $type = 'textarea';
        } elseif ($dataType === "enum") {
            $type = 'select';
        } elseif ($dataType === 'set') {
            $type = 'checkbox';
        } else {
            $type = 'text';
        }
        return $type;
    }

}
