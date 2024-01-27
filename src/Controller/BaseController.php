<?php

declare(strict_types=1);

namespace Restfull\Controller;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;
use Restfull\Event\Event;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 *
 */
class BaseController extends Controller
{

    /**
     * @var bool
     */
    protected $errorValidateExist = false;

    /**
     * @param Request $request
     * @param Response $response
     * @param Instances $instance
     */
    public function __construct(Request $request, Response $response, Instances $instance)
    {
        $this->request = $request;
        $this->response = $response;
        $this->instance = $instance;
        $this->setControl()->initialize()->instancesClass(
            ['Utility', 'Translator'],
            ['lenguage' => 'pt_BR', 'instance' => $this->instance]
        );
        if ($this->validEncrypt()) {
            $this->request->bootstrap('hash')->changeConfig(3);
            $this->encrypted = !$this->encrypted;
        }
        return $this;
    }

    /**
     * @return BaseController
     */
    public function initialize(): BaseController
    {
        return $this;
    }

    /**
     * @param bool $general
     * @param bool $internal
     * @param bool $linkInternalWithExternalAccess
     *
     * @return BaseController
     */
    public function changeRequestEncryptionKeys(
        bool $general,
        bool $internal,
        bool $linkInternalWithExternalAccess
    ): BaseController {
        if ($this->request->encryptionKeys['general'] !== $general) {
            $encryptionKeys['general'] = $general;
        }
        if ($this->request->encryptionKeys['internal'] !== $internal) {
            $encryptionKeys['internal'] = $internal;
        }
        if ($this->request->encryptionKeys['linkInternalWithExternalAccess'] !== $linkInternalWithExternalAccess) {
            $encryptionKeys['linkInternalWithExternalAccess'] = $linkInternalWithExternalAccess;
        }
        $hash = $this->request->bootstrap('hash');
        $hash->changeConfig($hash->levelEncrypt(), false, $encryptionKeys);
        return $this;
    }

    /**
     * @param array $tables
     * @param string|null $field
     * @return array
     */
    public function dataColumnsRegistory(array $tables, string $field = null): array
    {
        $datasType = [];
        if (isset($tables['join'])) {
            foreach ($tables['join'] as $join) {
                $registory = $this->tableRegistory(['main' => [$join]],
                    ['return' => 'repository', 'datas' => []]);
                $datasType = count($datasType) > 0 ? array_merge(
                    $datasType,
                    $registory->columns[$join['table']]
                ) : $registory->columns[$join['table']];
            }
        }
        foreach ($tables['main'] as $main) {
            $registory = $this->tableRegistory(['main' => [$main]],
                ['return' => 'repository', 'datas' => []]);
            $datasType = count($datasType) > 0 ? array_merge(
                $datasType,
                $registory->columns[$main['table']]
            ) : $registory->columns[$main['table']];
        }
        if (!is_null($field)) {
            foreach ($datasType as $datatype) {
                if ($datatype['name'] === $field) {
                    $result = $datatype;
                }
            }
            if (!isset($result)) {
                throw new Exceptions("Column {$field} doesn't exist in the repositorys of the requested tables.", 404);
            }
            return $result;
        }
        return $datasType;
    }

    /**
     * @return array
     */
    public function validyAuth(): array
    {
        if (isset($this->Auth)) {
            $config = $this->Auth->config();
            $check = $this->Auth->validateLogged();
            if ($check) {
                if ($this->route === $config['redirect']['action']) {
                    $this->redirect($config['redirect']['logged']);
                    return [true, true];
                }
                $this->Auth->script();
                return [true, false];
            } else {
                if ($this->Auth->pageAction($check)) {
                    $this->request->bootstrap('hash')->timeExpiredHasChangeStatus();
                    $this->Flash->error("You have lost access, please re-login.");
                    $this->redirectEncrypt($config['redirect']['unlogged']);
                    $this->Auth->checkKeyIsDestroy();
                    return [true, true];
                }
            }
            return [true, false];
        }
        return [false];
    }

    /**
     * @param string $place
     * @param array $config
     *
     * @return BaseController
     * @throws Exceptions
     */
    public function config(string $place): BaseController
    {
        $newConfig = [];
        $app = $this->request->bootstrap(strtolower(ROOT_NAMESPACE[1]));
        if ($place != 'default') {
            if (!$this->Auth->checkOuther('checkAnotherDatabase')) {
                $this->checkAnotherDatabase = $app->changeConnectionDatabase($this->request, $place);
                $this->Auth->writeOuther(
                    'checkAnotherDatabase',
                    [$this->Auth->getData('id') => [strtotime(date('Y-m-d H:i:s')), $this->checkAnotherDatabase]]
                );
                return $this;
            } elseif (!isset($this->Auth->checkOuther('checkAnotherDatabase')[$this->Auth->getData('id')])) {
                $this->checkAnotherDatabase = $app->changeConnectionDatabase($this->request, $place);
                $this->Auth->writeOuther(
                    'checkAnotherDatabase',
                    [$this->Auth->getData('id') => [strtotime(date('Y-m-d H:i:s')), $this->checkAnotherDatabase]]
                );
            }
            $checkAnotherDatabase = $this->Auth->outherData('checkAnotherDatabase')[$this->Auth->getData('id')];
            if (strtotime(date('Y-m-d H:i:s') . ' -25 minutes') < $checkAnotherDatabase[0]) {
                $this->checkAnotherDatabase = $checkAnotherDatabase[1];
            } else {
                $this->checkAnotherDatabase = $app->changeConnectionDatabase($this->request, $place);
                $this->Auth->writeOuther(
                    'checkAnotherDatabase',
                    [$this->Auth->getData('id') => [strtotime(date('Y-m-d H:i:s')), $this->checkAnotherDatabase]]
                );
            }
            return $this;
        }
        $this->checkAnotherDatabase = $app->changeConnectionDatabase($this->request, $place);
        return $this;
    }

    /**
     * @param string $link
     * @param string $urlparams
     * @param string $prefix
     * @param array $options
     *
     * @return BaseController
     * @throws Exceptions
     */
    public function redirectEncrypt(
        string $link,
        string $urlparams = '',
        string $prefix = 'app',
        array $options = []
    ): BaseController {
        if (count($options) > 0) {
            $this->redirect($this->encryptLinks($link, $prefix, $urlparams, $options));
        }
        $this->redirect($this->encryptLinks($link, $prefix, $urlparams));
        return $this;
    }

    /**
     * @param mixed $mixed
     */
    public function dd($mixed)
    {
        var_dump($mixed);
        exit;
    }

    /**
     * @param bool $auth
     * @param string $local
     *
     * @return bool
     */
    public function checkTheLoggedInUserHasPageAuthorization()
    {
        $auth = $this->Auth->validateLogged();
        if ($auth) {
            $pagesNotAuth = $this->Auth->pagesNotAuth($this->name);
            $result = $this->Auth->pageAction($auth);
            if (count($pagesNotAuth) > 0) {
                if (in_array($this->action, $pagesNotAuth) !== false) {
                    return $result;
                }
                return !$result;
            }
            return $auth;
        }
        return $auth;
    }

    /**
     * @param string $route
     *
     * @return string
     */
    public function routeRedirect(string $route): string
    {
        if (isset($this->Auth)) {
            $route = $this->Auth->loggedRedirect($route);
        }
        if (!empty($this->request->base)) {
            if (stripos($route, $this->request->base) === false) {
                $route = $this->request->base . $route;
            }
        }
        return $route;
    }

    /**
     * @param string $component
     * @param array|null $config
     *
     * @return BaseController
     * @throws Exceptions
     */
    public function loadComponents(string $component, array $config = null): BaseController
    {
        if ($this->name == 'Error') {
            if (in_array($component, ['Auth', 'Flash', 'Paginator', 'TwoFactor']) === false) {
                return $this;
            }
        }
        $this->{$component} = $this->instance->resolveClass(
            $this->instance->locateTheFileWhetherItIsInTheAppOrInTheFramework(
                ROOT_NAMESPACE[1] . DS_REVERSE . MVC[0] . DS_REVERSE . SUBMVC[0] . DS_REVERSE . $component . SUBMVC[0]
            ),
            ['controller' => $this, 'config' => $config]
        );
        if (array_key_exists($component, $this->activeHelpers) && $this->activeHelpers[$component] === false) {
            $this->activatingHelpers($component, true);
        }
        return $this;
    }

    /**
     * @param string $executionTypeForQuery
     * @param array $table
     * @param array $options
     * @param array $details
     *
     * @return object
     * @throws Exceptions
     */
    public function querys(
        string $executionTypeForQuery,
        array $table,
        array $options = [],
        array $details = []
    ): object {
        $details = $this->checkDetails($executionTypeForQuery, $details);
        $this->validUserModelAndNameTable($table);
        if ($executionTypeForQuery != 'details') {
            $this->ValidType($executionTypeForQuery);
            list($table, $options, $joins, $fields, $limit) = $this->validateAndAlignData(
                $executionTypeForQuery,
                $table,
                $options
            );
            $this->executionTypeForQuery($executionTypeForQuery)->tableRegistory(
                array_merge($table, ['join' => $joins]),
                ['datas' => $options]
            );
            unset($options, $joins);
            if ($this->Auth->validateLogged() && in_array($executionTypeForQuery, ['create', 'update', 'delete']
                ) !== false) {
                $this->identifyOfCreationOrAlterationOrDeletion($this->Auth->getData('id'));
            }
            if ($details['notDelete']) {
                $this->typeQuery(
                    in_array($executionTypeForQuery, ['delete', 'remove']
                    ) !== false ? ($executionTypeForQuery === 'remove' ? 'delete' : 'update') : $executionTypeForQuery
                );
            }
            if ($details['validate']) {
                if ($this->validateTableData()) {
                    $this->typeQuery('errorValidate');
                    return $this->excuting(['result'], false);
                }
            }
            if ($details['businessRules']) {
                $this->appTable();
            }
            $this->assemblyQuery($limit['count'], $details, $fields);
            $result = $this->excuting((isset($fields) && count($fields) > 0 ? $fields : []), $details['repository']);
            return $result;
        }
        $this->executionTypeForQuery($executionTypeForQuery)->tableRegistoryDetails($table, $options);
        unset($options, $joins);
        $this->assemblyQuery(0, $details, []);
        return $this->excuting([], false);
    }

    /**
     * @param string $table
     * @param array $options
     *
     * @return array
     */
    public function conversionDatas(string $table, array $options): array
    {
        $this->tableRegistory(['main' => [['table' => $table]]], $options);
        $newOptions = $this->appTable(true);
        foreach (array_keys($options[0]['fields']) as $key) {
            $datas[$key] = $newOptions[0]['fields'][$key];
        }
        return $datas;
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
     * @param array $table
     * @param array $conditons
     *
     * @return bool
     * @throws Exceptions
     */
    public function validateAuthentication(array $table, array $joins, array $conditons): bool
    {
        $joins = $this->identifyTheJoinTable($joins, $table['main'][0]['table']);
        if ($this->configLogging($conditons)) {
            return false;
        }
        $this->tableRegistory(array_merge($table, ['join' => $joins]));
        return !$this->validateAuth($conditons);
    }

    /**
     * @param object $query
     * @param int|null $limit
     *
     * @return mixed
     */
    public function counts(object $query, int $limit = null)
    {
        $counts = 0;
        foreach ($query as $key => $values) {
            if (in_array($key, ['repository', 'count']) === false) {
                if (is_object($values)) {
                    if ($this->counts($values, $limit)) {
                        $counts++;
                    }
                } else {
                    $counts++;
                }
            }
        }
        if (isset($limit)) {
            return $counts > $limit;
        }
        return $counts;
    }

    /**
     * @param array $datas
     *
     * @return array
     * @throws Exceptions
     */
    public function checkTitle(array $datas): array
    {
        if ($this->validTitle($datas)) {
            return $datas;
        }
        throw new Exceptions('Cannot have subtitles within a subtitle.', 404);
    }

    /**
     * @param string $view
     * @param string $prefix
     * @param bool $changeRequest
     *
     * @return BaseController
     */
    public function renderPrefix(string $view, string $prefix = '', bool $changeRequest = false): BaseController
    {
        $this->render($view);
        if (!empty($prefix) && $prefix != $this->request->prefix) {
            $this->request->prefix = $prefix;
            if ($prefix === 'app') {
                $this->folder('Main');
            }
        }
        if ($changeRequest) {
            $this->request->action = $view;
        }
        return $this;
    }

    /**
     * @param string $class
     * @param string|array $method
     * @param array $options
     * @param string $type
     *
     * @return mixed
     */
    public function returnFromAbstractClassOrResult(string $class, $methods, array $options, string $type = null)
    {
        if (preg_match('/[A-Z]/i', substr($class, 0, 1)) === false) {
            $class = ucFirst($class);
        }
        $object = $this->request->bootstrap('plugins')->startClass($class);
        if ($type === 'object') {
            if (!isset($options['component'])) {
                $options['component'] = $this->instance->resolveClass(
                    ROOT_NAMESPACE[0] . DS_REVERSE . 'Controller' . DS_REVERSE . 'Component',
                    ['controller' => $this]
                );
            }
            $this->{$class} = !isset($type) ? $object->treatments($methods, $options) : $object->treatment(
                $methods,
                $options,
                $type
            );
            return $this;
        }
        return !isset($type) ? $object->treatments($methods, $options) : $object->treatment($methods, $options, $type);
    }

    /**
     * @param string $action
     * @return BaseController
     * @throws Exceptions
     */
    public function emailBuilder(string $action): BaseController
    {
        if (empty($action)) {
            throw new Exceptions("This action variable can't be empty.");
        }
        $this->builder($action);
        return $this;
    }

    /**
     * @param Event $event
     *
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
     *
     * @return null
     */
    public function beforeRedirect(Event $event, string $url, Response $response)
    {
        return null;
    }

    /**
     * @param Event $event
     *
     * @return null
     */
    public function afterFilter(Event $event)
    {
        return null;
    }

    /**
     * @param array $routes
     * @return BaseController
     */
    protected function addRoutesNotEncryption(array $routes): BaseController
    {
        $keysEncryptionAuth = $this->Auth->outherData('keysEncryption');
        $change = false;
        if (!isset($keysEncryptionAuth[$this->request->server['REMOTE_ADDR']][0]['routes'])) {
            $change = !$change;
            $keysEncryptionAuth[$this->request->server['REMOTE_ADDR']][0]['routes'] = $routes;
        } else {
            $exist = 0;
            $count = count($routes);
            for ($number = 0; $number < $count; $number++) {
                if (in_array(
                        $routes[$number],
                        $keysEncryptionAuth[$this->request->server['REMOTE_ADDR']][0]['routes']
                    ) !== false) {
                    $exist++;
                }
            }
            if ($exist === 0) {
                $change = !$change;
            }
        }
        if ($change) {
            $this->Auth->writeOuther('keysEncryption', $keysEncryptionAuth, true);
        }
        return $this;
    }

}
