<?php

declare(strict_types=1);

namespace Restfull\Mail;

use Restfull\Controller\BaseController;
use Restfull\Error\Exceptions;

/**
 *
 */
class EmailView
{

    /**
     * @param BaseController $controller
     * @param string $layout
     * @param string $action
     * @return BaseController
     * @throws Exceptions
     */
    public function run(BaseController $controller, string $layout, string $action): BaseController
    {
        $viewBuilder = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Builders' . DS_REVERSE . MVC[1] . 'Builder',
            [
                'request' => $this->request,
                'response' => $this->response,
                'instance' => $this->instance,
                'data' => $controller->view ?? []
            ]
        );
        $viewBuilder->config(
            ['activeHelpers' => $controller->activeHelpers, 'action' => $action, 'encrypted' => $controller->encrypted]
        )->render($this->pathView($action));
        $controller->response = $viewBuilder->responseView();
        return $controller;
    }

    /**
     * @param BaseController $controller
     *
     * @return array
     * @throws Exceptions
     */
    public function pathView(string $action): array
    {
        $layout = substr(RESTFULL, 0, -1) . DS . 'Template' . DS . 'Layout' . DS . 'mail.phtml';
        $pageContent = substr(RESTFULL, 0, -1) . DS . 'Template' . DS . "Mail" . DS . $action . '.phtml';
        if (!$this->instance->validate($pageContent, 'file')) {
            throw new Exceptions("The {$action} view wasn't found in the layout folder.", 405);
        }
        return [$layout, $pageContent];
    }

}