<?php

declare(strict_types=1);

namespace Restfull\View\Helper;

use Restfull\Datasource\Pagination;
use Restfull\Error\Exceptions;
use Restfull\View\Helper;
use Restfull\View\View;

/**
 *
 */
class PaginatorHelper extends Helper
{

    /**
     * @var string
     */
    private $urlParams = '';

    /**
     * @var string
     */
    private $paramsLink = '';

    /**
     * @var Pagination
     */
    private $paginator;

    /**
     * @param View $view
     */
    public function __construct(View $view)
    {
        parent::__construct($view, [
            'link' => "<a href='%s'%s>%s</a>",
            'ul' => "<ul%s>%s</ul>",
            'li' => "<li%s>%s</li>",
            'div' => "<div%s>%s</div>",
        ]);
        foreach ($this->view->datas() as $key => $value) {
            if ($value instanceof Pagination) {
                $data = $value->resultset();
                unset($data->repository);
                $this->view->setDatas($key, $data);
                $this->paginator = $value->params();
            }
        }
        return $this;
    }

    /**
     * @param string $content
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    public function div(string $content, array $options = []): string
    {
        return $this->formatTemplate(__METHOD__, [$this->formatAttributes($options), $content]);
    }

    /**
     * @param string $params
     *
     * @return PaginatorHelper
     */
    public function paramsLink(string $params): PaginatorHelper
    {
        $this->paramsLink = $params;
        return $this;
    }

    /**
     * @param array $options
     * @param bool $encrypt
     * @param array $title
     *
     * @return string
     * @throws Exceptions
     */
    public function pagination(array $options = [], bool $encrypt = false, array $title = []): string
    {
        if (isset($options['shortenEnable'])) {
            $this->urlParams = $options['shortenEnable'] !== '|ajax|nao' ? $options['shortenEnable'] : '|ajax|nao';
            unset($options['shortenEnable']);
        }
        $paginator = $this->limitPage($title['limit'][0] ?? '|<<', 'initial', ($options['limitPages'] ?? $options));
        $paginator .= $this->pageNotShow(
            $title['pageNotShow'][0] ?? '<...',
            'initial',
            ($options['pageToShow'] ?? $options)
        );
        $paginator .= $this->prev($title['prev'] ?? '<<', ($options['prev'] ?? $options));
        $paginator .= $this->numbers(($options['numbers'] ?? $options));
        $paginator .= $this->next($title['next'] ?? '>>', ($options['next'] ?? $options));
        $paginator .= $this->pageNotShow(
            $title['pageNotShow'][0] ?? '...>',
            'last',
            ($options['pageToShow'] ?? $options)
        );
        $paginator .= $this->limitPage($title['limit'][1] ?? '>>|', 'last', ($options['limitPages'] ?? $options));
        unset($options['next'], $options['numbers'], $options['prev'], $options['pageToShow'], $options['limitPages']);
        return $this->formatTemplate(
            str_replace('pagination', 'ul', __METHOD__),
            [$this->formatAttributes($options), $paginator]
        );
    }

    /**
     * @param string $name
     * @param string $key
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    public function limitPage(string $name, string $key, array $options = []): string
    {
        if ($this->paginator['limitPages'][$key] === $this->paginator['pageSet']) {
            $options['class'] = isset($options['class']) ? $options['class'] . ' page-item-disabled' : 'page-item-disabled';
        }
        $link = strtolower($this->request->controller) . '+' . $this->request->action;
        $params = !empty($this->paramsLink) ? $this->paramsLink . $this->paginator['limitPages'][$key] : $this->paginator['limitPages'][$key];
        $order = $this->order();
        if (!empty($order)) {
            $params = $order . DS . $params;
        }
        if (isset($options['link']['table'])) {
            $params = $options['link']['table'] . DS . $params;
            unset($options['link']['table']);
        }
        $options['link'] = array_merge($options['link'], $this->prefix());
        $link = $this->link(
            $name,
            $link,
            array_merge($options['link'] ?? [], ['urlParams' => $params . $this->urlParams])
        );
        if (isset($options['link'])) {
            unset($options['link']);
        }
        return $this->formatTemplate(
            str_replace('limitPage', 'li', __METHOD__),
            [$this->formatAttributes($options), $link]
        );
    }

    /**
     * @return string
     */
    public function order(): string
    {
        $param = '';
        if (isset($this->request->params)) {
            if (in_array('order', array_keys($this->request->params))) {
                $param = $this->request->params['order'];
            }
        }
        return $param;
    }

    /**
     * @return array
     */
    private function prefix(): array
    {
        return ['prefix' => $this->request->prefix];
    }

    /**
     * @param string $name
     * @param string $url
     * @param array $options
     * @param string $params
     *
     * @return string
     * @throws Exceptions
     */
    private function link(string $name, string $url, array $options = [], string $params = ''): string
    {
        $url = $this->view->encryptLinks(
            $url,
            $options['prefix'] ?? 'app',
            isset($options['urlParams']) ? explode('|', $options['urlParams']) : ['']
        );
        unset($options['urlParams']);
        if ($url != "#" && in_array(substr($url, 0, 6), ['mailto', 'http:/', 'https:']) === false) {
            if (!isset($options['id'])) {
                $options['id'] = substr($url, strripos($url, DS) + 1);
            }
            if (is_bool($options['id'])) {
                unset($options['id']);
            }
            if (stripos($url, $this->request->server['REQUEST_SCHEME']) === false) {
                $baseUrl = URL . DS;
                if (!empty($this->request->base)) {
                    $baseUrl .= $this->request->base . DS;
                }
                $url = $baseUrl . $url;
            }
        }
        if (isset($options['class']) && stripos($options['class'], 'disabled')) {
            $url = '';
        }
        if (empty($name)) {
            return $url;
        }
        if (isset($options['div'])) {
            $div = $options['div'];
            unset($options['div']);
            $link = $this->formatTemplate(__METHOD__, [$url, $this->formatAttributes($options), $name]);
            return $this->tag($link, 'div', $div);
        }
        return $this->formatTemplate(__METHOD__, [$url, $this->formatAttributes($options), $name]);
    }

    /**
     * @param string $name
     * @param string $key
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    private function pageNotShow(string $name, string $key, array $options = []): string
    {
        if ($this->paginator['pageToShow'][$key] === 0) {
            $options['class'] = isset($options['class']) ? $options['class'] . ' page-item-disabled' : 'page-item-disabled';
        }
        $link = strtolower($this->request->controller) . '+' . $this->request->action;
        $params = !empty($this->paramsLink) ? $this->paramsLink . $this->paginator['pageToShow'][$key] : $this->paginator['pageToShow'][$key];
        $order = $this->order();
        if (!empty($order)) {
            $params = $order . DS . $params;
        }
        if (isset($options['link']['table'])) {
            $params = $options['link']['table'] . DS . $params;
            unset($options['link']['table']);
        }
        $options['link'] = array_merge($options['link'], $this->prefix());
        $link = $this->link(
            $name,
            $link,
            array_merge($options['link'] ?? [], ['urlParams' => $params . $this->urlParams])
        );
        if (isset($options['link'])) {
            unset($options['link']);
        }
        return $this->formatTemplate(
            str_replace('pageNotShow', 'li', __METHOD__),
            [$this->formatAttributes($options), $link]
        );
    }

    /**
     * @param string $name
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    private function prev(string $name, array $options = []): string
    {
        if ($this->paginator['prevPage'] === 0) {
            $options['class'] = isset($options['class']) ? $options['class']
                . ' page-item-disabled' : 'page-item-disabled';
        }
        $link = strtolower($this->request->controller) . '+' . $this->request->action;
        $params = !empty($this->paramsLink) ? $this->paramsLink . $this->paginator['prevPage'] : $this->paginator['prevPage'];
        $order = $this->order();
        if (!empty($order)) {
            $params = $order . DS . $params;
        }
        if (isset($options['link']['table'])) {
            $params = $options['link']['table'] . DS . $params;
            unset($options['link']['table']);
        }
        $options['link'] = array_merge($options['link'], $this->prefix());
        $link = $this->link(
            $name,
            $link,
            array_merge($options['link'] ?? [], ['urlParams' => $params . $this->urlParams])
        );
        if (isset($options['link'])) {
            unset($options['link']);
        }
        return $this->formatTemplate(str_replace('prev', 'li', __METHOD__), [$this->formatAttributes($options), $link]);
    }

    /**
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    private function numbers(array $options = []): string
    {
        $classPage = $options['class'];
        if ($this->paginator['pages'] === 1) {
            $options['class'] = isset($options['class']) ? $options['class'] . ' page-item-disabled' : 'page-item-disabled';
        }
        if (isset($options['link']['table'])) {
            $table = $options['link']['table'];
            unset($options['link']['table']);
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
                        $options['class'] = $classPage;
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
                $options['class'] .= stripos(
                    $options['class'],
                    'page-item-disabled'
                ) !== false ? ' page-item-active-disabled' : ' page-item-active';
                $page = $this->paginator['pageSet'];
            }
            if ($resp) {
                if ($page <= $this->paginator['pages']) {
                    $link = strtolower($this->request->controller) . '+' . $this->request->action;
                    $params = !empty($this->paramsLink) ? $this->paramsLink . $page : $page;
                    $order = $this->order();
                    if (!empty($order)) {
                        $params = $order . DS . $params;
                    }
                    if (isset($table)) {
                        $params = $table . DS . $params;
                    }
                    if (isset($options['link'])) {
                        $linkOptions = array_merge($options['link'], $this->prefix());
                        unset($options['link']);
                    }
                    $link = $this->link(
                        $page,
                        $link,
                        array_merge($linkOptions ?? [], ['urlParams' => $params . $this->urlParams])
                    );
                    $out[] = $this->formatTemplate(
                        str_replace('numbers', 'li', __METHOD__),
                        [$this->formatAttributes($options), $link]
                    );
                }
            }
        }
        if (isset($this->request->params)) {
            $colorClass = true;
            $count = count($out);
            for ($a = 0; $a < $count; $a++) {
                if (stripos($out[$a], ' page-item-active') !== false) {
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
                    ) . " page-item-active" . substr($context, stripos($context, 'page-item') + 9);
            }
        }
        return implode("", $out);
    }

    /**
     * @param string $name
     * @param array $options
     *
     * @return string
     * @throws Exceptions
     */
    private function next(string $name, array $options = []): string
    {
        if ($this->paginator['nextPage'] === 0) {
            $options['class'] = isset($options['class']) ? $options['class'] . ' page-item-disabled' : 'page-item-disabled';
        }
        $link = strtolower($this->request->controller) . '+' . $this->request->action;
        $params = !empty($this->paramsLink) ? $this->paramsLink . $this->paginator['nextPage'] : $this->paginator['nextPage'];
        $order = $this->order();
        if (!empty($order)) {
            $params = $order . DS . $params;
        }
        if (isset($options['link']['table'])) {
            $params = $options['link']['table'] . DS . $params;
            unset($options['link']['table']);
        }
        $options['link'] = array_merge($options['link'], $this->prefix());
        $link = $this->link(
            $name,
            $link,
            array_merge($options['link'] ?? [], ['urlParams' => $params . $this->urlParams])
        );
        if (isset($options['link'])) {
            unset($options['link']);
        }
        return $this->formatTemplate(str_replace('next', 'li', __METHOD__), [$this->formatAttributes($options), $link]);
    }

    /**
     * @return int
     */
    public function pagesTotal(): int
    {
        return $this->paginator['pages'];
    }

}
