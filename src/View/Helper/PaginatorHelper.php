<?php

namespace Restfull\View\Helper;

use Restfull\Datasourse\Pagination;
use Restfull\Error\Exceptions;
use Restfull\View\BaseView;
use Restfull\View\Helper;

/**
 * Class PaginatorHelper
 * @package Restfull\View\Helper
 */
class PaginatorHelper extends Helper
{

    /**
     * @var array
     */
    protected $templater = [
            'link' => "<a href='%s'%s>%s</a>",
            'ul' => "<ul%s>%s</ul>",
            'li' => "<li%s>%s</li>",
            'div' => "<div%s>%s</div>",
    ];

    /**
     * @var bool
     */
    private $encrypting = false;

    /**
     * @var Pagination
     */
    private $paginator;

    /**
     * PaginatorHelper constructor.
     * @param BaseView $view
     */
    public function __construct(BaseView $view)
    {
        foreach ($view->data() as $key => $value) {
            if ($value instanceof Pagination) {
                $data = $value->resultset();
                unset($data->repository);
                $view->setData($key, $data);
                $this->paginator = $value->params();
            }
        }
        parent::__construct($view, $this->templater);
        return $this;
    }

    /**
     * @param string $content
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public function div(string $content, array $options = []): string
    {
        return $this->formatTemplate(
                __METHOD__,
                [
                        $this->formatAttributes($options),
                        $content
                ]
        );
    }

    /**
     * @param array $title
     * @param array $options
     * @param bool $encrypt
     * @return string
     * @throws Exceptions
     */
    public function pagination(array $title, array $options = [], bool $encrypt = false): string
    {
        $this->encrypting = $encrypt;
        $next = isset($options['next']) ? $options['next'] : $options;
        $limitPages = isset($options['limitPages']) ? $options['limitPages'] : $options;
        $pageToShow = isset($options['pageToShow']) ? $options['pageToShow'] : $options;
        $numbers = isset($options['numbers']) ? $options['numbers'] : $options;
        $prev = isset($options['prev']) ? $options['prev'] : $options;
        unset($options['next'], $options['numbers'], $options['prev'], $options['pageToShow'], $options['limitPages']);
        $paginator = $this->limitPage('|<<', 'initial', $limitPages);
        $paginator .= $this->pageNotShow('<...', 'initial', $pageToShow);
        $paginator .= $this->prev($title[0], $prev);
        $paginator .= $this->numbers($numbers);
        $paginator .= $this->next($title[1], $next);
        $paginator .= $this->pageNotShow('...>', 'last', $pageToShow);
        $paginator .= $this->limitPage('>>|', 'last', $limitPages);
        return $this->formatTemplate(
                str_replace('pagination', 'ul', __METHOD__),
                [$this->formatAttributes($options), $paginator]
        );
    }

    /**
     * @param string $name
     * @param string $key
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    public function limitPage(string $name, string $key, array $options = []): string
    {
        if ($this->paginator['limitPages'][$key] == $this->paginator['pageSet']) {
            $options['class'] = isset($options['class']) ? $options['class'] . ' page-disabled' : 'page-disabled';
        }
        $optionsLink = [];
        if (isset($options['link'])) {
            $optionsLink = $options['link'];
            unset($options['link']);
        }
        return $this->formatTemplate(
                str_replace('limitPage', 'li', __METHOD__),
                [
                        $this->formatAttributes($options),
                        $this->link(
                                $name,
                                strtolower(
                                        $this->request->controller
                                ) . '+' . $this->request->action,
                                $this->params('order', 'limitPage', $key),
                                $optionsLink
                        )
                ]
        );
    }

    /**
     * @param string $name
     * @param string $url
     * @param string $params
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    private function link(string $name, string $url, string $params, array $options = []): string
    {
        $url = $this->view->encryptLinks(
                $url,
                [
                        'number' => $options['number'] ?? 3,
                        'encrypt' => $options['encrypt'] ?? ['internal' => false, 'general' => false]
                ],
                $params ?? ''
        );
        if (!isset($options['id'])) {
            $options['id'] = substr($url, 1);
        }
        if (stripos($url, $this->request->server['REQUEST_SCHEME']) === false) {
            if (!empty($this->request->base)) {
                $url = DS . ".." . $this->request->base . $url;
            } else {
                $url = DS . ".." . $url;
            }
        }
        return $this->formatTemplate(
                __METHOD__,
                [
                        $url,
                        $this->formatAttributes($options),
                        $name
                ]
        );
    }

    /**
     * @param string $key
     * @param string $page
     * @param string|null $param
     * @return string
     */
    public function params(string $key, string $page, string $param = null): string
    {
        $result = '';
        if (isset($this->request->params)) {
            if (in_array($key, array_keys($this->request->params))) {
                $result = DS . $this->request->params[$key];
            }
        }
        $data = $this->paginator[$page];
        if (!is_null($param)) {
            $data = $data[$param];
        }
        if ($this->encrypting) {
            $result .= DS . base64_encode($data);
            return $param;
        }
        $result .= DS . $data;
        return $result;
    }

    /**
     * @param string $name
     * @param string $key
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    private function pageNotShow(string $name, string $key, array $options = []): string
    {
        if ($this->paginator['pageToShow'][$key] == 0) {
            $options['class'] = isset($options['class']) ? $options['class'] . ' page-disabled' : 'page-disabled';
        }
        $optionsLink = [];
        if (isset($options['link'])) {
            $optionsLink = $options['link'];
            unset($options['link']);
        }
        return $this->formatTemplate(
                str_replace('pageNotShow', 'li', __METHOD__),
                [
                        $this->formatAttributes($options),
                        $this->link(
                                $name,
                                strtolower(
                                        $this->request->controller
                                ) . '+' . $this->request->action,
                                $this->params('order', 'pageNotShow', $key),
                                $optionsLink
                        )
                ]
        );
    }

    /**
     * @param string $name
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    private function prev(string $name, array $options = []): string
    {
        if ($this->paginator['prevPage'] == 0) {
            $options['class'] = isset($options['class']) ? $options['class'] . ' page-disabled' : 'page-disabled';
        }
        $optionsLink = [];
        if (isset($options['link'])) {
            $optionsLink = $options['link'];
            unset($options['link']);
        }
        return $this->formatTemplate(
                str_replace('prev', 'li', __METHOD__),
                [
                        $this->formatAttributes($options),
                        $this->link(
                                $name,
                                strtolower(
                                        $this->request->controller
                                ) . '+' . $this->request->action,
                                $this->params('order', 'prevPage'),
                                $optionsLink
                        )
                ]
        );
    }

    /**
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    private function numbers(array $options = []): string
    {
        $optionsLink = [];
        if (isset($options['link'])) {
            $optionsLink = $options['link'];
            unset($options['link']);
        }
        if (count($this->paginator) == 0 || $this->paginator['pageSet'] == 0) {
            $options['class'] = isset($options['class']) ? $options['class'] . ' disabled active active-paginator' : 'disabled active active-paginator';
            unset($options['link']);
            return $this->formatTemplate(
                    str_replace('numbers', 'li', __METHOD__),
                    [
                            $this->formatAttributes($options),
                            $this->link(
                                    "1",
                                    strtolower(
                                            $this->request->controller
                                    ) . '+' . $this->request->action,
                                    $this->params('order') . DS . "1",
                                    $optionsLink
                            )
                    ]
            );
        }
        if ($this->paginator['pages'] == 1) {
            $options['class'] = isset($options['class']) ? $options['class'] . ' page-disabled' : 'page-disabled';
        }
        $resp = false;
        for ($a = 0; $a < 5; $a++) {
            if ($a < 2) {
                if ($this->paginator['pageSet'] > 1) {
                    $resp = true;
                    $page = $this->paginator['pageSet'] - (2 - $a);
                    if ($page <= 0) {
                        $resp = false;
                    }
                } else {
                    if ($resp) {
                        $resp = false;
                    }
                }
            } elseif ($a >= 3) {
                if ($this->paginator['pageSet'] < $this->paginator['pages']) {
                    $resp = true;
                    $page = $this->paginator['pageSet'] + ($a - 2);
                    if (stripos($options['class'], "active") !== false) {
                        $options['class'] = substr($options['class'], 0, stripos($options['class'], ' active'));
                    }
                    if ($page <= 0) {
                        $resp = false;
                    }
                } else {
                    if ($resp) {
                        $resp = false;
                    }
                }
            } else {
                $resp = true;
                $options['class'] .= " active active-paginator";
                $page = $this->paginator['pageSet'];
            }
            if ($resp) {
                if ($page <= $this->paginator['pages']) {
                    $newPage = $this->encrypting ? base64_encode($page) : $page;
                    $out[] = $this->formatTemplate(
                            str_replace('numbers', 'li', __METHOD__),
                            [
                                    $this->formatAttributes($options),
                                    $this->link(
                                            $page,
                                            strtolower(
                                                    $this->request->controller
                                            ) . '+' . $this->request->action,
                                            $this->params('order') . DS . $newPage,
                                            $optionsLink
                                    )
                            ]
                    );
                }
            }
        }
        if (isset($this->request->params)) {
            $colorClass = true;
            for ($a = 0; $a < count($out); $a++) {
                if (stripos($out[$a], ' active active-paginator') !== false) {
                    $colorClass = false;
                    break;
                }
            }
            if ($colorClass) {
                $context = $out[count($out) - 1];
                $out[count($out) - 1] = substr(
                                $context,
                                0,
                                stripos($context, "page-item") + 9
                        ) . " active active-paginator" . substr($context, stripos($context, 'page-item') + 9);
            }
        }
        return implode("", $out);
    }

    /**
     * @param string $name
     * @param array $options
     * @return string
     * @throws Exceptions
     */
    private function next(string $name, array $options = []): string
    {
        if ($this->paginator['nextPage'] == 0) {
            $options['class'] = isset($options['class']) ? $options['class'] . ' page-disabled' : 'page-disabled';
        }
        $optionsLink = [];
        if (isset($options['link'])) {
            $optionsLink = $options['link'];
            unset($options['link']);
        }
        return $this->formatTemplate(
                str_replace('next', 'li', __METHOD__),
                [
                        $this->formatAttributes($options),
                        $this->link(
                                $name,
                                DS . strtolower(
                                        $this->request->controller
                                ) . '+' . $this->request->action,
                                $this->params('order', 'nextPage'),
                                $optionsLink
                        )
                ]
        );
    }
}
