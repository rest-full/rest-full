<?php

namespace Restfull\Controller\Component;

use Restfull\Controller\BaseController;
use Restfull\Controller\Component;
use Restfull\Datasourse\Pagination;
use Restfull\Error\Exceptions;
use stdClass;

/**
 * Class PaginatorComponent
 * @package Restfull\Controller\Component
 */
class PaginatorComponent extends Component
{

    /**
     * @var Pagination
     */
    private $paginator;

    /**
     * @var array
     */
    private $param = [];

    /**
     * @var array
     */
    private $default = ['page' => 1, 'limit' => 7, 'pages' => 3];

    /**
     * PaginatorComponent constructor.
     * @param BaseController $controller
     */
    public function __construct(BaseController $controller)
    {
        parent::__construct($controller);
        $this->paginator = new Pagination($this->default);
        return $this;
    }

    /**
     * @param object $resultset
     * @param array $options
     * @return Pagination
     * @throws Exceptions
     */
    public function paginator(object $resultset, array $options): Pagination
    {
        $this->params($options);
        $result = new stdClass();
        if (isset($resultset->repository)) {
            if ($resultset->repository->rowsCount <= 7) {
                $options['page'] = 1;
            }
            if ($resultset->repository->rowsCount == 0 && $resultset->repository->increment == 0) {
                $options['count'] = 0;
            } else {
                $options['count'] = $resultset->repository->rowsCount == 0 ? 1 : $resultset->repository->rowsCount;
            }
        } else {
            $options['count'] = 0;
        }
        $this->paginator->paginator($options);
        $this->request->ativation();
        $count = 0;
        foreach ($resultset as $key => $value) {
            if ($key != 'repository') {
                if ($count < $this->param['last']) {
                    $result->$key = $value;
                }
                $count++;
            } else {
                $result->$key = $value;
            }
        }
        $this->paginator->resultset($result);
        return $this->paginator;
    }

    /**
     * @param array $params
     * @return $this
     * @throws Exceptions
     */
    public function params(array $params): PaginatorComponent
    {
        if (empty($params['page']) || empty($params['limit'])) {
            throw new Exceptions('Parameter cannot be empty.', 404);
        }
        $default = $this->paginator->paramsDefault();
        if ($params['limit'] != $default['limit']) {
            $default['limit'] = (int)$params['limit'];
        }
        if (!isset($this->default['initial'], $this->default['last'])) {
            $this->param['initial'] = $params['page'] != $default['page'] ? ($params['page'] * $params['limit']) - $params['limit'] : 0;
            $this->param['last'] = $params['limit'] == $default['limit'] ? $params['limit'] : $default['limit'];
        }
        if (is_string($this->param['last'])) {
            $this->param['last'] = (int)$this->param['last'];
        }
        $this->paginator->paramsDefault($default);
        return $this;
    }

    /**
     * @return array
     */
    public function limit(): array
    {
        return [$this->param['initial'], $this->param['last']];
    }

}
