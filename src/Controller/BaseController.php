<?php

namespace Restfull\Controller;

use Restfull\Controller\Component\AuthComponent;
use Restfull\Core\Instances;
use Restfull\Error\Exceptions;
use Restfull\Event\Event;
use Restfull\Event\EventDispatcherTrait;
use Restfull\Event\EventManager;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 * Class BaseController
 * @package Restfull\Controller
 */
class BaseController extends Controller
{

    use EventDispatcherTrait;

    /**
     * BaseController constructor.
     * @param Request $request
     * @param Response $response
     * @param Instances $instance
     */
    public function __construct(Request $request, Response $response, Instances $instance)
    {
        $this->request = $request;
        $this->response = $response;
        $this->instance = $instance;
        $this->setControl();
        $this->initialize();
        return $this;
    }

    /**
     *
     */
    public function initialize()
    {
    }

    /**
     * @return array|bool[]|false[]
     */
    public function validyAuth(): array
    {
        if (isset($this->Auth)) {
            $config = $this->Auth->config();
            if ($this->Auth->pageAutorized()) {
                $this->Flash->error("You have lost access, please re-login.");
                $this->redirectEncrypt($config['redirect']['unlogged']);
                return [true, true];
            }
            if (count($this->Auth->getData()) > 0 && $this->route == $config['redirect']['action']) {
                $this->redirectEncrypt($config['redirect']['logged']);
                return [true, true];
            }
            $this->Auth->script();
            return [true, false];
        }
        return [false];
    }

    /**
     * @param string $link
     * @return BaseController
     */
    public function redirectEncrypt(
            string $link,
            int $number = 3,
            array $encrypt = ['internal' => false, 'general' => true]
    ): BaseController {
        $encrypt['url'] = $this->encrypting;
        $this->redirect($this->encryptLinks($link, $number, $encrypt));
        return $this;
    }

    /**
     * @param string $link
     * @param string $encrypt
     * @return string
     */
    public function encryptLinks(
            string $link,
            int $number = 3,
            array $encrypt = ['internal' => false, 'general' => false],
            bool $route = true
    ): string {
        if ($route) {
            if (stripos($link, DS) === false && $link != '#') {
                $link = $this->response->routeIdentify(strtolower($link));
            }
        } else {
            $link = str_replace('+', DS, $link);
        }
        if ($this->encrypting) {
            foreach (['internal', 'general'] as $key) {
                if (!isset($encrypt[$key])) {
                    throw new Exceptions("This {$key} key does not exist.", 404);
                }
            }
            if (($this->Auth->checkAuth() && $encrypt['internal']) || $encrypt['general']) {
                $security = $this->request->bootstrap('security');
                $link = $security->encrypt($link, $number);
                if ($number >= 2) {
                    $security->activeEncrypt('file');
                }
            }
        }
        return $link;
    }

    /**
     * @param string $route
     * @return string
     */
    public function routeRedirect(string $route): string
    {
        if (isset($this->Auth)) {
            return $this->Auth->loggedRedirect($route);
        }
        return $route;
    }

    /**
     * @param string $place
     * @param array $conifg
     * @param string|null $action
     * @return BaseController
     * @throws Exceptions
     */
    public function config(string $place, array $conifg = [], string $action = null): BaseController
    {
        if (!isset($action)) {
            $this->app->bootstrapDatabase($place, $config);
            if ($place != 'default') {
                $this->Auth->writeOuther('connexion', [$place => $config]);
            }
            return $this;
        }
        if ($place != 'default' && count($config) == 0) {
            $config = $this->Auth->connectAuth()[$place];
        }
        $this->checkDatabase = $this->app->bootstrapDatabase($place, $config, $action);
        return $this;
    }

    /**
     * @param string $component
     * @param array|null $config
     * @return BaseController
     * @throws Exceptions
     */
    public function loadComponents(string $component, array $config = null): BaseController
    {
        $this->$component = $this->instance->resolveClass(
                $this->instance->extension(
                        $this->instance->namespaceClass(
                                "%s" . DS_REVERSE . "%s" . DS_REVERSE . "%s" . DS_REVERSE . "%s",
                                [substr(ROOT_APP, -4, -1), MVC[0], SUBMVC[0], $component . SUBMVC[0]]
                        )
                ),
                ['controller' => $this, 'config' => $config]
        );
        if (array_key_exists($component, $this->activeHelpers) && $this->activeHelpers[$component] == false) {
            $this->activatingHelpers($component, true);
        }
        return $this;
    }

    /**
     * @param string $events
     * @param array|null $data
     * @return mixed|EventManager|null
     * @throws Exceptions
     */
    public function eventProcessVerification(string $event, array $data = null)
    {
        $event = $this->dispatchEvent(MVC[0] . "." . $event, $data);
        if ($event->result() instanceof Response) {
            return null;
        }
        return $event->result();
    }

    /**
     * @return string
     */
    public function csrf(): string
    {
        $this->Auth->writeOuther('routeThatUsesCsrf', ['route' => $this->route]);
        return $this->request->bootstrap('security')->getSalt();
    }

    /**
     * @param string $table
     * @param array $columns
     * @return bool
     * @throws Exceptions
     */
    public function checkConstraintinserted(string $table, array $columns): bool
    {
        $keys = array_keys($columns);
        $this->model = $this->model->tableRegistory(['main' => $table]);
        foreach ($this->model->constraint($keys[0])->foreignKey as $tableResult) {
            $exist[] = false;
            $contrainst = $this->model->tableRegistory(
                    ['main' => $tableResult],
                    [
                            'fields' => ['count(*) as count'],
                            'conditions' => [$keys[0] . ' & ' => $columns[$keys[0]]],
                            'table' => ['table' => $tableResult]
                    ]
            );
            if ($this->model->excuteQuery('first', false, ['fields' => ['count(*) as count']])->count > 0) {
                $exist[count($exist) - 1] = true;
            }
        }
        if (in_array(true, $exist)) {
            return false;
        }
        return true;
    }

    /**
     * @param string $type
     * @param array $table
     * @param array $options
     * @param bool $repository
     * @param bool $lastid
     * @return false|mixed
     * @throws Exceptions
     */
    public function querys(
            string $type,
            array $table,
            array $options = [],
            bool $repository = true,
            bool $lastid = false
    ) {
        $this->configLogging($options['conditions'] ?? []);
        $this->validUserModelAndNameTable($table);
        list($table, $options, $joins, $fields, $limit) = $this->validateAndAlignData($type, $table, $options);
        $this->model = $this->model->tableRegistry(
                ['main' => $table['main'], 'join' => $joins],
                $options,
                ['request' => $this->request, 'response' => $this->response]
        );
        unset($options, $joins);
        for ($a = 0; $a < count($table['main']); $a++) {
            $nameTable[] = $table['main'][$a]['table'];
        }
        if (!$this->validateTableData($type, $this->model->optionsQuery())) {
            if ($limit > 0) {
                $this->model->editLimit($nameTable, $repository)->queryAssembly($type, $lastid);
            } else {
                $this->model->queryAssembly($type, $lastid);
            }
            if (isset($fields) && count($fields) > 0) {
                return $this->model->excuteQuery($type, $repository, $fields);
            }
            return $this->model->excuteQuery($type, $repository);
        }
        return false;
    }

    /**
     * @param array $conditions
     * @return BaseController
     * @throws Exceptions
     */
    public function configLogging(array $conditions): BaseController
    {
        if (isset($this->Auth) && $this->Auth instanceof AuthComponent) {
            if (count($conditions) > 0) {
                if (!$this->Auth->validateLogged()) {
                    if ($this->validateAuth($conditions)) {
                        throw new Exceptions("The fields weren't found to login.");
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @param object $query
     * @param int $limit
     * @return bool
     */
    public function counts(object $query, int $limit): bool
    {
        $counts = 0;
        foreach ($object as $key => $values) {
            if (in_array($key, ['repository', 'count']) === false) {
                $counts++;
            }
        }
        if ($counts > $limit) {
            return true;
        }
        return false;
    }

    /**
     * @param Event $event
     * @return null
     */
    public function beforeFilter(Event $event)
    {
        return null;
    }

    /**
     * @param Event $event
     * @param string $url
     * @param Response $response
     * @return null
     */
    public function beforeRedirect(Event $event, string $url, Response $response)
    {
        return null;
    }

    /**
     * @param Event $event
     * @return null
     */
    public function afterFilter(Event $event)
    {
        return null;
    }
}