<?php

declare(strict_types=1);

namespace Restfull\Builders;

use Restfull\Container\Instances;
use Restfull\Http\Request;
use Restfull\Http\Response;
use Restfull\View\BaseView;

/**
 *
 */
class ViewBuilder
{

    /**
     * @var BaseView
     */
    private $view;


    /**
     * @param Instance $instance
     * @param Request $request
     * @param Response $response
     * @param array $datas
     */
    public function __construct(Instances $instance, Request $request, Response $response, array $datas = [])
    {
        $this->view = $instance->resolveClass(
            ROOT_NAMESPACE[1] . DS_REVERSE . MVC[1] . DS_REVERSE . ROOT_NAMESPACE[1] . MVC[1],
            ['request' => $request, 'response' => $response, 'instance' => $instance, 'datas' => $datas]
        );
    }

    /**
     * @param array $config
     * @return ViewBuilder
     */
    public function config(array $config): viewBuilder
    {
        $this->view->ativationsHelpers = $config['activeHelpers'];
        if (($this->view->request->action != "login") && ($this->view->request->action != $config['action'])) {
            $this->view->action = $config['action'];
        }
        $this->view->encryptValid($config['encrypted']);
        return $this;
    }

    /**
     * @param array $path
     *
     * @return ViewBuilder
     */
    public function render(array $path): ViewBuilder
    {
        if ($this->view->controller === 'FlashView') {
            $this->view->response->body($this->view->Flash->render());
            return $this;
        }
        if (!empty($path[0])) {
            $this->view->viewPath($path);
            $this->view->eventProcessVerification('beforeLayout', [$path[0]]);
            $this->view->response->body($this->view->action());
            $this->view->eventProcessVerification('afterLayout', [$path[0]]);
            return $this;
        }
        $this->view->viewPath($path, 'content');
        $this->view->response->body($this->view->content());
        return $this;
    }

    /**
     * @return Request
     */
    public function responseView(): Response
    {
        return $this->view->response;
    }

}