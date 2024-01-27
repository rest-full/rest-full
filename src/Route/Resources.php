<?php

declare(strict_types=1);

namespace Restfull\Route;

use Restfull\Container\Instances;

/**
 *
 */
class Resources
{
    /**
     * @var array
     */
    private $actions = [
        'list',
        'record',
        'edit',
        'view',
        'register',
        'change',
        'delete',
        'ordination',
        'pagination',
        'filter'
    ];

    /**
     * @var array
     */
    private $names = [
        'listing',
        'register record',
        'edit record',
        'view record',
        'new register',
        'change register',
        'delete register',
        'ordered listing',
        'pagination listing',
        'filter listing'
    ];

    /**
     * @var array
     */
    private $parameters = ['filter' => ['order', 'page'], 'pagination' => ['page'], 'ordination' => ['order', 'page']];

    /**
     * @var array
     */
    private $methods = [
        ['GET'],
        ['GET'],
        ['GET'],
        ['GET'],
        ['POST'],
        ['POST'],
        ['GET'],
        ['GET', 'POST'],
        ['POST'],
        ['POST']
    ];

    /**
     * @var array
     */
    private $name = [];

    /**
     * @var array
     */
    private $uri = [];

    /**
     * @var Instances
     */
    private $instance;

    /**
     * @param Instances $instance
     */
    public function __construct(Instances $instance)
    {
        $this->instance = $instance;
        return $this;
    }

    /**
     * @param string $controller
     * @param bool $active
     * @param bool $activeParamsList
     *
     * @return Resources
     */
    public function resources(string $controller, bool $active, bool $activeParamsList = false): Resources
    {
        foreach ($this->actions as $action) {
            $path = DS . strtolower($controller) . DS . $action;
            $method = 'createRoute' . ucwords($action);
            if ($action === 'list') {
                $this->{$method}($path, $controller, $active, $activeParamsList);
            } else {
                $this->{$method}($path, $controller, $active);
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getName(): array
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getUri(): array
    {
        return $this->uri;
    }

    /**
     * @param string $path
     * @param string $handler
     * @param bool $activeName
     * @param bool $activeParams
     *
     * @return Resources
     */
    private function createRouteList(string $path, string $handler, bool $activeName, bool $activeParams): Resources
    {
        if ($activeParams) {
            $newPath = $path;
        }
        $handler .= '.index';
        foreach ($this->methods[0] as $method) {
            $this->add($method, $path, $handler);
        }
        if ($activeName) {
            $this->name[$path] = $this->names[0];
        }
        if ($activeParams) {
            $this->methods[1] = array_merge($this->methods[1], ['POST']);
            $this->createRouteFilter($newPath, $handler, $activeName);
        }
        return $this;
    }

    /**
     * @param string $method
     * @param string $route
     * @param string $handler
     * @param null $callback
     *
     * @return Resources
     */
    private function add(string $method, string $route, string $handler, $callback = null): Resources
    {
        $url = explode(DS, $route);
        if (isset($callback)) {
            $this->request->callback[$route] = [$callback, $this->instance->anonymousFunction($callback)];
            return $this;
        }
        $data = explode(".", $handler);
        $count = count($data);
        for ($a = 0; $a < $count; $a++) {
            if ($a === 0) {
                $dispatcher['controller'] = $data[$a];
            } elseif ($a === 1) {
                $dispatcher['action'] = $data[$a];
            }
        }
        $this->uri[$method][$url[0]][$route] = $dispatcher;
        return $this;
    }

    /**
     * @param string $path
     * @param string $handler
     * @param bool $activeName
     *
     * @return Resources
     */
    private function createRouteFilter(string $path, string $handler, bool $activeName): Resources
    {
        if (stripos($handler, 'index') === false) {
            $handler .= '.index';
        }
        for ($a = 1; $a >= 0; $a--) {
            $newPath = $path . DS . '{' . $this->parameters['filter'][$a] . '}';
            $name = $a === 1 ? ' with pagination' : ' with ordenation and pagination';
            if ($a === 0) {
                $newPath .= DS . '{' . $this->parameters['filter'][($a + 1)] . '}';
            }
            foreach ((stripos($path, 'list') ? $this->methods[0] : $this->methods[9]) as $method) {
                $this->add($method, $newPath, $handler);
            }
            if ($activeName) {
                $this->name[$newPath] = (stripos($path, 'list') ? $this->names[0] : $this->names[9]) . $name;
            }
        }
        return $this;
    }

    /**
     * @param string $path
     * @param string $handler
     * @param bool $activeName
     *
     * @return Resources
     */
    private function createRouteRecord(string $path, string $handler, bool $activeName): Resources
    {
        $handler .= '.recorde';
        foreach ($this->methods[1] as $method) {
            $this->add($method, $path, $handler);
        }
        if ($activeName) {
            $this->name[$path] = $this->names[1];
        }
        return $this;
    }

    /**
     * @param string $path
     * @param string $handler
     * @param bool $activeName
     *
     * @return Resources
     */
    private function createRouteEdit(string $path, string $handler, bool $activeName): Resources
    {
        $path .= DS . '{id}';
        $handler .= '.edit';
        foreach ($this->methods[2] as $method) {
            $this->add($method, $path, $handler);
        }
        if ($activeName) {
            $this->name[$path] = $this->names[2];
        }
        return $this;
    }

    /**
     * @param string $path
     * @param string $handler
     * @param bool $activeName
     *
     * @return Resources
     */
    private function createRouteView(string $path, string $handler, bool $activeName): Resources
    {
        $path .= DS . '{id}';
        $handler .= '.view';
        foreach ($this->methods[3] as $method) {
            $this->add($method, $path, $handler);
        }
        if ($activeName) {
            $this->name[$path] = $this->names[3];
        }
        return $this;
    }

    /**
     * @param string $path
     * @param string $handler
     * @param bool $activeName
     *
     * @return Resources
     */
    private function createRouteRegister(string $path, string $handler, bool $activeName): Resources
    {
        $handler .= '.register';
        foreach ($this->methods[4] as $method) {
            $this->add($method, $path, $handler);
        }
        if ($activeName) {
            $this->name[$path] = $this->names[4];
        }
        return $this;
    }

    /**
     * @param string $path
     * @param string $handler
     * @param bool $activeName
     *
     * @return Resources
     */
    private function createRouteChange(string $path, string $handler, bool $activeName): Resources
    {
        $handler .= '.change';
        foreach ($this->methods[5] as $method) {
            $this->add($method, $path, $handler);
        }
        if ($activeName) {
            $this->name[$path] = $this->names[5];
        }
        return $this;
    }

    /**
     * @param string $path
     * @param string $handler
     * @param bool $activeName
     *
     * @return Resources
     */
    private function createRouteDelete(string $path, string $handler, bool $activeName): Resources
    {
        $path .= DS . '{id}';
        $handler .= '.delete';
        foreach ($this->methods[6] as $method) {
            $this->add($method, $path, $handler);
        }
        if ($activeName) {
            $this->name[$path] = $this->names[6];
        }
        return $this;
    }

    /**
     * @param string $path
     * @param string $handler
     * @param bool $activeName
     *
     * @return Resources
     */
    private function createRouteOrdination(string $path, string $handler, bool $activeName): Resources
    {
        $path .= DS . '{' . $this->parameters['ordination'][0] . '}';
        $path .= DS . '{' . $this->parameters['ordination'][1] . '}';
        $handler .= '.index';
        foreach ($this->methods[7] as $method) {
            $this->add($method, $path, $handler);
        }
        if ($activeName) {
            $this->name[$path] = $this->names[7] . ' with pagination';
        }
        return $this;
    }

    /**
     * @param string $path
     * @param string $handler
     * @param bool $activeName
     *
     * @return Resources
     */
    private function createRoutePagination(string $path, string $handler, bool $activeName): Resources
    {
        $path .= DS . '{' . $this->parameters['pagination'][0] . '}';
        $handler .= '.index';
        foreach ($this->methods[8] as $method) {
            $this->add($method, $path, $handler);
        }
        if ($activeName) {
            $this->name[$path] = $this->names[8];
        }
        return $this;
    }
}
