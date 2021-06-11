<?php

namespace Restfull\Datasourse;

/**
 * Class Pagination
 * @package Restfull\Datasourse
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
    private $params = [];

    /**
     * Pagination constructor.
     * @param array $default
     */
    public function __construct(array $default)
    {
        $this->default = $default;
        return $this;
    }

    /**
     * @param array $param
     * @return $this
     */
    public function paginator(array $param): Pagination
    {
        if (!isset($param['limit'])) {
            $param['limit'] = $this->default['limit'];
        }
        $pages = $param['count'] % $param['limit'] != 0 ? intval(
                ceil($param['count'] / $param['limit'])
        ) : $param['count'] / $param['limit'];
        $pageSet = $pages <= $param['page'] ? $pages : $param['page'];
        if ($param['count'] < $param['limit']) {
            $this->params = [
                    'pageSet' => $pageSet,
                    'limitPages' => [
                            'initial' => 1,
                            'last' => $pages
                    ],
                    'pageToShow' => [
                            'initial' => 0,
                            'last' => 0,
                    ],
                    'valid' => $pages > 1,
                    'pages' => $pages <= 1 ? '1' : $pages,
                    'prevPage' => 0,
                    'nextPage' => 0
            ];
        } else {
            $this->params = [
                    'pageSet' => $pageSet,
                    'limitPages' => [
                            'initial' => 1,
                            'last' => $pages
                    ],
                    'pageToShow' => [
                            'initial' => $pageSet <= $this->default['pages'] ? 0 : $pageSet - $this->default['pages'],
                            'last' => $pageSet >= ($pages - $this->default['pages']) ? 0 : $pageSet + $this->default['pages'],
                    ],
                    'valid' => $pages > 1,
                    'pages' => $pages <= 1 ? '1' : $pages,
                    'prevPage' => $pageSet > 1 ? $pageSet - 1 : 0,
                    'nextPage' => $pages > $pageSet ? $pageSet + 1 : 0
            ];
        }
        return $this;
    }

    /**
     * @param object|null $result
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
     * @return Pagination|array
     */
    public function paramsDefault(array $default = [])
    {
        if (count($default) > 0) {
            $this->default = $default;
            return $this;
        }
        return $this->default;
    }

}
