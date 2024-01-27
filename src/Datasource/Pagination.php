<?php

declare(strict_types=1);

namespace Restfull\Datasource;

/**
 *
 */
class Pagination
{

    /**
     * @var array
     */
    private $default = [];

    /**
     * @var array
     */
    private $resultset = [];

    /**
     * @var array
     */
    private $paramsConfig = [];

    /**
     * @var array
     */
    private $params = [];

    /**
     * @param int $page
     * @param int $limit
     * @param int $pages
     */
    public function __construct(int $page, int $limit, int $pages)
    {
        $this->default = ['page' => $page, 'limit' => $limit, 'pages' => $pages];
        return $this;
    }

    /**
     * @param array $param
     *
     * @return Pagination
     */
    public function paginator(): Pagination
    {
        if (!isset($this->paramsConfig['last'])) {
            $this->paramsConfig['limit'] = $this->default['limit'];
        }
        $pageSet = $this->default['pages'] <= $this->default['page'] ? $this->default['pages'] : $this->default['page'];
        $this->params = [
            'pageSet' => $pageSet,
            'limitPages' => ['initial' => 1, 'last' => $this->default['pages']],
            'pageToShow' => ['initial' => 0, 'last' => 0,],
            'valid' => $this->default['pages'] > 1,
            'pages' => $this->default['pages'] <= 1 ? '1' : $this->default['pages'],
            'prevPage' => 0,
            'nextPage' => 0
        ];
        if ($this->paramsConfig['count'] > $this->paramsConfig['last']) {
            $this->params = [
                'pageSet' => $pageSet,
                'limitPages' => ['initial' => 1, 'last' => $this->default['pages']],
                'pageToShow' => [
                    'initial' => $pageSet >= ($this->default['pages'] - 3) ? ($pageSet <= $this->default['pages'] ? $this->default['pages'] - $pageSet : 0) : 0,
                    'last' => ($this->default['pages'] - 3) >= $pageSet ? ($pageSet <= ($this->default['pages'] - $pageSet) ? $pageSet + 3 : 0) : 0
                ],
                'valid' => $this->default['pages'] > 1,
                'pages' => $this->default['pages'] <= 1 ? '1' : $this->default['pages'],
                'prevPage' => $pageSet > 1 ? $pageSet - 1 : 0,
                'nextPage' => $this->default['pages'] > $pageSet ? $pageSet + 1 : 0
            ];
        }
        return $this;
    }

    /**
     * @param object|null $result
     *
     * @return object
     */
    public function resultset(object $result = null): object
    {
        if (isset($result)) {
            $this->resultset = $result;
            return $this;
        }
        return $this->resultset;
    }

    /**
     * @return array
     */
    public function params(): array
    {
        return $this->params;
    }

    /**
     * @param array $default
     *
     * @return mixed
     */
    public function paramsDefault(array $default = [])
    {
        if (count($default) > 0) {
            foreach ($default as $key => $value) {
                if (in_array($key, array_keys($this->default)) !== false) {
                    $this->default[$key] = $value;
                } else {
                    $this->paramsConfig[$key] = $value;
                }
            }
            return $this;
        }
        return $this->default;
    }

}
