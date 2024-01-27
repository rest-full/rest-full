<?php

declare(strict_types=1);

namespace Restfull\Executing;

use Restfull\Container\Instances;
use Restfull\Controller\BaseController;
use Restfull\Error\Exceptions;
use Restfull\Event\EventDispatcherTrait;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 *
 */
class Dispatcher
{

    use EventDispatcherTrait;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Instances
     */
    private $instance;

    /**
     * @param Request $request
     * @param Response $response
     * @param Instances $instance
     */
    public function __construct(Request $request, Response $response, Instances $instance)
    {
        $this->response = $response;
        $this->request = $request;
        $this->instance = $instance;
        return $this;
    }

    /**
     * @return mixed
     * @throws Exceptions
     */
    public function run(): Dispatcher
    {
        $controllerPath = substr(RESTFULL, 0, -1) . DS . MVC[0] . DS . ucfirst(
                $this->request->controller . MVC[0]
            ) . '.php';
        if (empty($this->request->prefix)) {
            $this->request->prefix = 'app';
        } else {
            $controllerPath = substr(RESTFULL, 0, -1) . DS . MVC[0] . DS . ucfirst(
                    $this->request->prefix
                ) . DS . ucfirst($this->request->controller . MVC[0]) . '.php';
        }
        if (!$this->instance->validate($controllerPath, 'file')) {
            throw new Exceptions(
                "the {$this->request->controller} controller wasn't found in the controller folder.",
                405
            );
        }
        $controllerPath = $this->instance->renameClass($controllerPath);
        $controller = $this->instance->resolveClass(
            $controllerPath,
            ['request' => $this->request, 'response' => $this->response, 'instance' => $this->instance]
        );
        $controller->initializeORM();
        $controller->eventProcessVerification('beforeFilter');
        if ($this->request->route != $controller->getUrl()) {
            $this->redirect($controller);
            return $this;
        }
        $auth = $controller->validyAuth();
        if ($auth[0]) {
            if ($auth[1]) {
                if ($this->request->route != $controller->getUrl()) {
                    $this->redirect($controller);
                    return $this;
                }
            }
        } else {
            if ($this->request->controller != 'error') {
                if (in_array(
                        $this->request->action,
                        $this->instance->methods($this->instance->renameClass($controllerPath))
                    ) === false) {
                    throw new Exceptions(
                        "The {$this->request->action} action of {$this->request->controller} controller wasn't found.",
                        405
                    );
                }
            }
        }
        unset($controllerPath, $auth);
        if (count($this->request->params) > 0) {
            $controller->{$this->request->action}($this->request->params);
        } else {
            $controller->{$this->request->action}();
        }
        $controller->eventProcessVerification('afterFilter');
        if ($this->request->route != $controller->getUrl()) {
            $this->redirect($controller);
            return $this;
        }
        if ($this->request->bolleanApi()) {
            return ['redirect' => $controller->Auth->config()['redirect']['logged'], 'view' => $controller->view];
        }
        $viewBuilder = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Builders' . DS_REVERSE . MVC[1] . 'Builder',
            [
                'instance' => $this->instance,
                'request' => $this->request,
                'response' => $this->response,
                'datas' => $controller->view ?? []
            ]
        );
        $viewBuilder->config(
            [
                'activeHelpers' => $controller->activeHelpers,
                'action' => $controller->newAction(),
                'encrypted' => $controller->encrypted
            ]
        )->render($this->pathView($controller));
        $this->response = $viewBuilder->responseView();
        return $this;
    }

    /**
     * @param BaseController $controller
     *
     * @return Dispatcher
     * @throws Exceptions
     */
    public function redirect(BaseController $controller): Dispatcher
    {
        $route = $controller->getUrl();
        $hash = $this->request->bootstrap('hash');
        $newRoute = $hash->valideDecrypt($route)->decrypt();
        if ($this->request->shorten) {
            $route = $hash->shortenDB($route, ['idUser' => $controller->Auth->getData('id')]);
        }
        if (strripos($newRoute, DS) === false) {
            throw new Exceptions("the {$route} path isn't correct.", 422);
        }
        $controller->eventProcessVerification('beforeRedirect', [$route, $this->response]);
        if ($this->request->ajax) {
            $this->response->redirect($controller->routeRedirect($route), true);
            return $this;
        }
        $this->response->redirect($controller->routeRedirect($route));
        return $this;
    }

    /**
     * @param BaseController $controller
     *
     * @return array
     * @throws Exceptions
     */
    public function pathView(BaseController $controller): array
    {
        $layout = '';
        if ($controller->layout != 'notExist') {
            $layout = substr(RESTFULL, 0, -1) . DS . 'Template' . DS . 'Layout' . DS . $controller->layout . '.phtml';
            if (!$this->instance->validate($layout, 'file')) {
                throw new Exceptions("The {$controller->layout} layout wasn't found in the layout folder.", 405);
            }
        }
        $pageContent = substr(RESTFULL, 0, -1) . DS . 'Template' . DS . ucfirst(
                $controller->name
            ) . DS . $controller->action . '.phtml';
        if (empty($this->request->prefix) || $this->request->prefix != 'app') {
            $pageContent = substr(RESTFULL, 0, -1) . DS . 'Template' . DS . ucfirst(
                    $this->request->prefix
                ) . DS . ucfirst($controller->name) . DS . $controller->action . '.phtml';
        }
        if (!$this->instance->validate($pageContent, 'file')) {
            throw new Exceptions("The {$view->action} view wasn't found in the layout folder.", 405);
        }
        return [$layout, $pageContent];
    }

    /**
     * @return string
     * @throws Exceptions
     */
    public function sendResponse()
    {
        return $this->response->send();
    }

}
