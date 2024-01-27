<?php

declare(strict_types=1);

namespace Restfull\Controller\Component;

use Restfull\Controller\Component;
use Restfull\Controller\Controller;
use Restfull\Datasource\Pagination;
use Restfull\Error\Exceptions;

/**
 *
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
    private $params = [];

    /**
     * @param Controller $controller
     */
    public function __construct(Controller $controller)
    {
        $instance = $controller->instance();
        $this->paginator = $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Datasource' . DS_REVERSE . 'Pagination',
            ['page' => 1, 'limit' => 7, 'pages' => 3]
        );
        parent::__construct($controller);
        return $this;
    }

    /**
     * @param object $resultset
     * @param array $options
     *
     * @return Pagination
     */
    public function paginator(object $resultset, array $options): Pagination
    {
        $params['count'] = 0;
        if (isset($resultset->repository)) {
            $keys = array_keys($resultset->repository->rowsCount);
            $count = count($keys);
            for ($a = 0; $a < $count; $a++) {
                if ($resultset->repository->rowsCount[$keys[$a]] != 0 && $resultset->repository->increment[$keys[$a]] != 0) {
                    $params['count'] += $resultset->repository->rowsCount[$keys[$a]];
                }
            }
        }
        if ($params['count'] === 0) {
            $params['count'] = 1;
        }
        $this->pages($resultset, $keys);
        $params = array_merge($params, $this->params);
        $this->paginator->paramsDefault(array_merge($options, $params))->paginator();
        $this->request->ativation();
        $this->paginator->resultset($resultset);
        return $this->paginator;
    }

    /**
     * @param object $resultset
     * @param array $keys
     *
     * @return PaginatorComponent
     */
    private function pages(object $resultset, array $keys): PaginatorComponent
    {
        $rowCounts = 0;
        $default = $this->paginator->paramsDefault();
        foreach ($keys as $key) {
            $rowCounts += $resultset->repository->rowsCount[$key];
        }
        $this->params['pages'] = $rowCounts % $default['limit'] != 0 ? ceil(
            $rowCounts / $default['limit']
        ) : $rowCounts / $default['limit'];
        if ($this->params['pages'] === 0) {
            $this->params['pages'] = 1;
        }
        return $this;
    }

    /**
     * @param int $limit
     *
     * @return PaginatorComponent
     * @throws Exceptions
     */
    public function limitDefault(int $limit): PaginatorComponent
    {
        $default = $this->paginator->paramsDefault();
        $this->params(['limit' => $limit, 'page' => $default['page']]);
        return $this;
    }

    /**
     * @param array $params
     * @param bool $lastCalc
     *
     * @return PaginatorComponent
     * @throws Exceptions
     */
    public function params(array $params, bool $lastCalc = true): PaginatorComponent
    {
        if (empty($params['page'])) {
            throw new Exceptions('Parameter cannot be empty.', 404);
        }
        $default = $this->paginator->paramsDefault();
        $this->params['initial'] = (int)($params['page'] != $default['page'] ? ($params['page'] * $params['limit']) - $params['limit'] : (isset($this->params['initial']) ? $params['limit'] : 0));
        if ($lastCalc) {
            $this->params['last'] = (int)($params['limit'] != $default['limit'] ? $params['limit'] : $default['limit']);
            if ($params['limit'] != $default['limit']) {
                $default['limit'] = (int)$params['limit'];
            }
        }
        if ($params['page'] != $default['page']) {
            $default['page'] = (int)$params['page'];
        }
        $this->params['page'] = $params['page'];
        $this->paginator->paramsDefault($default);
        return $this;
    }

    /**
     * @param array $params
     * @param bool $calcLimits
     *
     * @return PaginatorComponent
     * @throws Exceptions
     */
    public function defaults(array $params, bool $calcLimits = true): PaginatorComponent
    {
        $default = $this->paginator->paramsDefault();
        $default['page'] = 1;
        $this->paginator->paramsDefault($default);
        if ($calcLimits) {
            $this->params($params);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function limit(): array
    {
        return [$this->params['initial'], $this->params['last']];
    }

}
