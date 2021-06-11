<?php

namespace Restfull\Executing;

use Restfull\Controller\BaseController;
use Restfull\Core\Instances;
use Restfull\Error\Exceptions;
use Restfull\Event\EventDispatcherTrait;
use Restfull\Filesystem\File;
use Restfull\Http\Request;
use Restfull\Http\Response;
use Restfull\View\BaseView;

/**
 * Class Dispatcher
 * @package Restfull\Executing
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
     * Dispatcher constructor.
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
     * @return $this|array
     * @throws Exceptions
     */
    public function run()
    {
        if (!(new File(
                $this->instance->namespaceClass(
                        "%s" . DS . "%s" . DS . "%s.php",
                        [
                                substr(str_replace('App', 'src', ROOT_APP), 0, -1),
                                MVC[0],
                                ucfirst($this->request->controller . MVC[0])
                        ]
                )
        ))->exists()) {
            throw new Exceptions("the {$this->request->controller} controller wasn't found in the controller folder.", 405);
        }
        $controller = $this->instance->resolveClass(
                $this->instance->namespaceClass(
                        "%s" . DS_REVERSE . "%s" . DS_REVERSE . "%s",
                        [
                                substr(ROOT_APP, -4, -1),
                                MVC[0],
                                $this->request->controller . MVC[0]
                        ]
                ),
                ['request' => $this->request, 'response' => $this->response, 'instance' => $this->instance]
        );
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
                                $this->instance->getMethods(
                                        $this->instance->namespaceClass(
                                                "%s" . DS_REVERSE . "%s" . DS_REVERSE . "%s",
                                                [
                                                        substr(ROOT_APP, -4, -1),
                                                        MVC[0],
                                                        ucfirst($controller->name . MVC[0])
                                                ]
                                        )
                                )
                        ) === false) {
                    throw new Exceptions(
                            "The {$this->request->action} action of {$this->request->controller} controller wasn't found.",
                            405
                    );
                }
            }
        }
        $controller->initializeORM();
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
        } else {
            $view = $this->instance->resolveClass(
                    $this->instance->namespaceClass(
                            "%s" . DS_REVERSE . "%s" . DS_REVERSE . "App%s",
                            [substr(ROOT_APP, -4, -1), MVC[1], MVC[1]]
                    ),
                    [
                            'request' => $this->request,
                            'response' => $this->response,
                            'instance' => $this->instance,
                            'data' => (isset($controller->view)) ? $controller->view : []
                    ]
            );
            $view->ativationsHelpers = $controller->activeHelpers;
            if (($this->request->action != "login") && ($this->request->action != $controller->newAction())) {
                $view->action = $controller->newAction();
            }
            $view->encrypt($controller->encrypting);
            $view->layout($controller->layout);
            $this->render($view);
        }
        return $this;
    }

    /**
     * @param BaseController $controller
     * @return $this
     * @throws Exceptions
     */
    public function redirect(BaseController $controller): Dispatcher
    {
        $route = $controller->getUrl();
        if (stripos($route, '+') !== false) {
            $route = $this->response->routeIdentify($route);
        }
        $newRoute = $route;
        if ($controller->encrypting) {
            $security = $this->request->bootstrap('security');
            if ($security->valideDecryptBase64($route)) {
                $newRoute = $security->decrypt($route, 3, 'file');
            }
        }
        if (strripos($newRoute, DS) === false) {
            throw new Exceptions("the {$route} path isn't correct.", 422);
        }
        $controller->eventProcessVerification('beforeRedirect', [$route, $this->response]);
        if ($this->request->base != '') {
            $route = $this->request->base . DS . $route;
        }
        if($this->request->ajax) {
            $this->response->redirect($controller->routeRedirect($route), true);
            return $this;
        }
        $this->response->redirect($controller->routeRedirect($route));
        return $this;
    }

    /**
     * @param BaseView $view
     * @return $this
     * @throws Exceptions
     */
    public function render(BaseView $view): Dispatcher
    {
        if ($view->controller == 'FlashView') {
            $this->response->body($view->Flash->render());
            return $this;
        }
        $path = $this->pathView($view);
        $view->eventProcessVerification('beforeLayout', [$path[0]]);
        $this->response->body($view->viewPath($path)->action());
        $view->eventProcessVerification('afterLayout', [$path[0]]);
        return $this;
    }

    /**
     * @param BaseView $view
     * @return array
     * @throws Exceptions
     */
    public function pathView(BaseView $view): array
    {
        $layout = $this->instance->namespaceClass(
                "%s" . DS . "Template" . DS . "Layout" . DS . "%s.phtml",
                [substr(str_replace('App', 'src', ROOT_APP), 0, -1), $view->layout]
        );
        if (!(new File($layout))->exists()) {
            throw new Exceptions("The {$view->layout} layout wasn't found in the layout folder.", 405);
        }
        $idenfier = !empty($this->request->prefix) ? [
                substr(str_replace('App', 'src', ROOT_APP), 0, -1),
                $this->request->prefix,
                ucfirst($view->controller),
                $view->action
        ] : [
                substr(str_replace('App', 'src', ROOT_APP), 0, -1),
                ucfirst($view->controller),
                $view->action
        ];
        $pageContent = $this->instance->namespaceClass(
                (!empty($this->request->prefix) ? "%s" . DS . "Template" . DS . "%s" . DS . "%s" . DS . "%s.phtml" : "%s" . DS . "Template" . DS . "%s" . DS . "%s.phtml"),
                $idenfier
        );
        if (!(new File($pageContent))->exists()) {
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
