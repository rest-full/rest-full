<?php

declare(strict_types=1);

namespace Restfull\Controller;

use App\Model\AppModel;
use Restfull\Container\Instances;
use Restfull\Core\Application;
use Restfull\Error\Exceptions;
use Restfull\Event\EventDispatcherTrait;
use Restfull\Http\Request;
use Restfull\Http\Response;

/**
 *
 */
abstract class Controller
{

    use EventDispatcherTrait;

    /**
     * @var string
     */
    public $layout = 'default';

    /**
     * @var array
     */
    public $view = [];

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var Request
     */
    public $request;

    /**
     * @var Response
     */
    public $response;

    /**
     * @var array
     */
    public $activeHelpers = ['email' => false, 'paginator' => false, 'pdf' => false];

    /**
     * @var string
     */
    public $route = '';

    /**
     * @var string
     */
    public $action = '';
    /**
     * @var bool
     */
    public $encrypted = false;
    /**
     * @var AppModel
     */
    protected $model;
    /**
     * @var bool
     */
    protected $checkAnotherDatabase = false;
    /**
     * @var Instances
     */
    protected $instance;
    /**
     * @var mixed
     */
    protected $result;
    /**
     * @var array
     */
    protected $notORM = [];
    /**
     * @var array
     */
    protected $use = ['ORM' => false, 'validation' => false];
    /**
     * @var Application
     */
    protected $app;

    /**
     * @return Controller
     */
    public function setControl(): Controller
    {
        $this->name = $this->request->controller;
        $this->action = $this->request->action;
        $this->route = $this->request->route;
        if ($this->name == 'Error') {
            $this->layout = strtolower($this->request->controller);
        }
        return $this;
    }

    /**
     * @return Instances
     */
    public function instance(): Instances
    {
        return $this->instance;
    }

    /**
     * @return Controller
     */
    public function errorHeadRender(): Controller
    {
        $this->set('title', 'Rest-Full App');
        $this->set('icon', 'favicons' . DS . 'favicon.png');
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return Controller
     */
    public function set(string $name, $value): Controller
    {
        if (count($this->view) > 0) {
            if ($this->validKeyExist($name)) {
                throw new Exceptions("This {$name} key already exists in the view attribute.", 404);
            }
            $this->view = array_merge($this->view, [$name => $value]);
            return $this;
        }
        $this->view = [$name => $value];
        return $this;
    }

    /**
     * @param mixed $names
     * @return mixed
     */
    private function validKeyExist($names)
    {
        if (is_string($names)) {
            return array_key_exists($name, $this->view);
        }
        $valid = false;
        foreach (array_keys($this->view) as $key) {
            if ($key === $names) {
                $valid = !$valid;
                $name = $key;
                break;
            }
        }
        return [$name ?? '', $valid];
    }

    /**
     * @param array $value
     *
     * @return Controller
     */
    public function sets(array $values): Controller
    {
        if (count($this->view) > 0) {
            list($name, $valid) = $this->validKeyExist(array_keys($values));
            if ($valid) {
                throw new Exceptions("This {$name} key already exists in the view attribute.", 404);
            }
            $this->view = array_merge($this->view, $values);
            return $this;
        }
        $this->view = $values;
        return $this;
    }

    /**
     * @return Instances
     */
    public function instances(): Instances
    {
        return $this->instance;
    }

    /**
     * @return Controller
     */
    public function instancesClass(array $classArray, array $datas): Controller
    {
        $count = count($classArray);
        for ($a = 0; $a < $count; $a++) {
            $format[] = '%s';
            if ($a == ($count - 1)) {
                $class = $classArray[$a];
            }
        }
        $this->{$class} = $this->instance->resolveClass(
            $this->instance->assemblyClassOrPath(
                '%s' . DS_REVERSE . implode(DS_REVERSE, $format),
                array_merge([ROOT_NAMESPACE[0]], $classArray)
            ),
            $datas
        );
        return $this;
    }

    /**
     * @param bool $startORM
     * @param bool $startValidation
     *
     * @return Controller
     */
    public function settingTrueOrFalseToUseTheModel(bool $startModel = false, bool $startValidation = true): Controller
    {
        $this->use['ORM'] = $startModel;
        if ($startModel) {
            $this->use['validation'] = $startValidation;
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function validateTableData(): bool
    {
        $typeExecuteQuery = $this->model->executionTypeForQuery();
        if ($this->use['validation']) {
            $biult = false;
            if ($typeExecuteQuery === 'query') {
                $type = substr($options[0]['query'], 0, stripos($options[0]['query'], ' '));
                if ($type === 'show') {
                    $type = 'select';
                }
            } elseif (in_array(
                    $typeExecuteQuery,
                    ['open', 'all', 'first', 'countRows', 'union', 'built', 'union and built', 'built and union']
                ) !== false) {
                if (in_array($typeExecuteQuery, ['biult', 'union and built', 'built and union']) !== false) {
                    $biult = !$biult;
                }
                $type = 'select';
            }
            if ($type != 'select') {
                $datas = [];
                $options = $this->model->optionsQuery();
                foreach ($options as $optionsDatas) {
                    unset($optionsDatas['table']);
                    if ($type === 'update') {
                        $datas = array_merge($datas, $optionsDatas['fields']);
                    }
                    $datas = $this->theQueryConditionsToBeValidated($optionsDatas['conditions'], $datas);
                }
                if ($biult) {
                    $datas = $this->theQueryConditionsToBeValidated($options['conditions'], $datas);
                }
                return $this->model->validate($datas)->validation();
            }
        }
        return false;
    }

    /**
     * @param string $type
     *
     * @return Controller
     */
    protected function executionTypeForQuery(string $executionTypeForQuery): Controller
    {
        $this->model->executionTypeForQuery($executionTypeForQuery);
        return $this;
    }

    /**
     * @param array $conditions
     * @param array $datas
     * @return array
     */
    private function theQueryConditionsToBeValidated(array $conditions, array $datas): array
    {
        $datasConditions = [];
        foreach ($conditions as $key => $value) {
            if (!isset($datas[$key])) {
                if (is_string($key)) {
                    if (stripos($key, ' ') !== false) {
                        $key = substr($key, 0, stripos($key, ' '));
                    }
                } else {
                    foreach ([' <==> ', ' !<==> ', ' () ', ' !() '] as $logicalOperator) {
                        if (stripos($value, $logicalOperator) !== false) {
                            list($key, $value) = explode($logicalOperator, $value);
                            $key .= $logicalOperator;
                        }
                    }
                }
                $datasConditions[$key] = $value;
            }
        }
        return array_merge($datas, $datasConditions);
    }

    /**
     * @param int $id
     *
     * @return Controller
     */
    public function identifyOfCreationOrAlterationOrDeletion(int $id): Controller
    {
        $typeExecuteQuery = $this->model->executionTypeForQuery();
        if ($this->model->columnsIdsRegistory()) {
            $options = $this->model->optionsQuery();
            $field = $typeExecuteQuery . 'd';
            if ($typeExecuteQuery === 'create') {
                $counts = is_array($options[0]['conditions'][array_keys($options[0]['conditions'])[0]]) ? count(
                    $options[0]['conditions'][array_keys($options[0]['conditions'])[0]]
                ) : 1;
                $date = date('Y-m-d H:i:s');
                if ($counts > 1) {
                    for ($a = 0; $a < $counts; $a++) {
                        $idsUser[] = $id;
                        $datesUser[] = $date;
                    }
                }
                $options[0]['conditions']['id' . ucwords($field)] = $idsUser ?? $id;
                $options[0]['conditions'][$field] = $datesUser ?? $date;
                $options[0]['fields'] = array_merge($options[0]['fields'], ['id' . ucwords($field), $field]);
            } else {
                $options[0]['fields'] = array_merge(
                    $options[0]['fields'],
                    ['id' . ucwords($field) => $id, $field => date('Y-m-d H:i:s')]
                );
            }
            $this->model->optionsQuery($options);
        }
        return $this;
    }

    /**
     * @param string $link
     * @param string $prefix
     * @param string $urlparams
     *
     * @return string
     * @throws Exceptions
     */
    public function encryptLinks(string $link, string $prefix, string $urlparams = ''): string
    {
        if (stripos($link, DS) === false && $link != '#') {
            $link = $this->response->identifyRouteByName(
                strtolower($link),
                $prefix
            );
            if (stripos($link, '+') !== false) {
                $link = str_replace('+', DS, $link);
            }
        }
        if ($prefix !== 'app') {
            $link = $prefix . DS . $link;
        }
        if (!empty($urlparams)) {
            $params = stripos($urlparams, DS) !== false ? explode(DS, $urlparams) : [$urlparams];
        }
        $url = explode(DS, $link);
        $a = 0;
        foreach ($url as $key => $value) {
            if (stripos($value, '{') !== false) {
                if (isset($params[$a])) {
                    $url[$key] = $params[$a];
                } else {
                    unset($url[$key]);
                }
                $a++;
            }
        }
        $link = implode(DS, $url);
        if ($this->encrypted) {
            $link = $this->encrypted($link);
        }
        return $link;
    }

    /**
     * @param string $link
     * @return string
     */
    public function encrypted(string $link): string
    {
        $hash = $this->request->bootstrap('hash');
        if (!$hash->alfanumero()) {
            $hash->changeConfig($hash->LevelEncrypt(), true);
        }
        return $hash->encrypt($link);
    }

    /**
     * @return bool
     */
    public function validEncrypt(): bool
    {
        if ($this->Auth->validateLogged()) {
            return $this->request->encryptionKeys['internal'];
        }
        if ($this->request->encryptionKeys['linkInternalWithExternalAccess']) {
            return true;
        }
        return $this->request->encryptionKeys['general'];
    }

    /**
     * @param string $event
     * @param array $data
     * @param object|null $object
     *
     * @return mixed
     * @throws Exceptions
     */
    public function eventProcessVerification(string $event, array $data = [], object $object = null)
    {
        $event = $this->dispatchEvent($this->instance, MVC[0] . "." . $event, $data, $object);
        return $event->result();
    }

    /**
     * @return array
     */
    public function validationsResult(): array
    {
        $errors = $this->model->getErrorValidate();
        if (count($errors) > 0) {
            return $errors;
        }
        return [];
    }

    /**
     * @param string $key
     * @param bool $value
     *
     * @return Controller
     * @throws Exceptions
     */
    public function activatingHelpers(string $key, bool $value = false): Controller
    {
        if (in_array($key, ['email', 'paginator', 'pdf', 'html']) === false) {
            throw new Exceptions('Can only activate the helpers mail, paginator, pdf, html of the framework.', 500);
        }
        if ($value) {
            $this->activeHelpers[$key] = $value;
            return $this;
        }
        $this->activeHelpers[$key] = $this->app->checkEmailPdfHtml($key);
        return $this;
    }

    /**
     * @return Controller
     * @throws Exceptions
     */
    public function initializeORM(): Controller
    {
        $this->app = $this->request->bootstrap('app');
        if (count($this->notORM) > 0 && in_array($this->action, $this->notORM) !== false) {
            $this->use['ORM'] = false;
        }
        if ($this->use['ORM']) {
            if ($this->request->bootstrap('database')->validNotEmpty()) {
                throw new Exceptions(
                    'One of the keys must be empty and the host, dbname, user and pass keys cannot be empty.', 600
                );
            }
            $this->model = $this->instance->resolveClass(
                ROOT_NAMESPACE[1] . DS_REVERSE . MVC[2][strtolower(
                    ROOT_NAMESPACE[1]
                )] . DS_REVERSE . ROOT_NAMESPACE[1] . MVC[2][strtolower(ROOT_NAMESPACE[1])],
                ['instance' => $this->instance, 'http' => ['request' => $this->request, 'response' => $this->response]]
            );
            $this->checkAnotherDatabase = true;
        }
        return $this;
    }

    /**
     * @param string $url
     *
     * @return Controller
     */
    public function redirect(string $url): Controller
    {
        $this->route = $url;
        $this->Auth->writeOuther('route', ['path' => $this->request->route]);
        return $this;
    }

    /**
     * @param array $datas
     *
     * @return bool
     */
    public function validTitle(array $datas): bool
    {
        $count = 0;
        $newData = [];
        $a = 0;
        foreach ($datas as $data) {
            if (is_array($data)) {
                $newCount = 0;
                $computo = count($data);;
                for ($b = 0; $b < $computo; $b++) {
                    if (in_array("array", array_map('gettype', $data[$b])) === false) {
                        $newCount++;
                    }
                    $newData[$a + $b] = $data[$b];
                }
                if ($newCount != 0) {
                    $count++;
                }
                $a += $b;
            } else {
                $count++;
                $newData[$a] = $data;
                $a++;
            }
        }
        return $count === count($newData);
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->route;
    }

    /**
     * @return string
     */
    public function newAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $typeExecuteQuery
     * @param array $table
     * @param array $options
     *
     * @return array
     */
    public function validateAndAlignData(string $typeExecuteQuery, array $table, array $options): array
    {
        $limit['count'] = 0;
        foreach ($options as $optionsKey => $optionsValues) {
            $joins = isset($optionsValues['join']) ? $this->identifyTheJoinTable(
                $optionsValues['join'],
                $table['main'][$optionsKey]['table']
            ) : [$table['main'][$optionsKey]['table'] => null];
            $datas[$optionsKey]['fields'] = $optionsValues['fields'];
            if (isset($optionsValues['limit'])) {
                $limit['count']++;
            }
        }
        if (in_array($typeExecuteQuery, ['union', 'biult', 'union and built', 'built and union']) !== false) {
            $datas = [['fields' => []]];
            if ($type === 'union') {
                foreach ($options['union'] as $key) {
                    $count = count($options[$key]['fields']);
                    for ($a = 0; $a < $count; $a++) {
                        if (in_array($options[$key]['fields'][$a], $data[0]['fields']) === false) {
                            $datas[0]['fields'][] = $options[$key]['fields'][$a];
                        }
                    }
                }
            } else {
                if (isset($options['built']['limit'])) {
                    $limit['count'] = 1;
                }
                if (isset($options['built']['fields'])) {
                    $datas[0]['fields'] = $options['built']['fields'];
                }
            }
        }
        if (isset($table['table'])) {
            $table['main'] = [$table];
            unset($table['table']);
            if (isset($table['alias'])) {
                unset($table['alias']);
            }
        }
        return [$table, $options, $joins, (isset($datas) ? $datas : []), $limit];
    }

    /**
     * @param array $joins
     * @param string $table
     * @return array
     */
    protected function identifyTheJoinTable(array $joins, string $table): array
    {
        $tables = [];
        foreach ($joins as $join) {
            if (in_array($join['table'], $tables) === false) {
                $newJoins[$table][] = [
                    'table' => $join['table'],
                    'alias' => (isset($join['alias']) ? $join['alias'] : '')
                ];
                $tables[] = $join['table'];
            }
        }
        return $newJoins;
    }

    /**
     * @param array $conditions
     *
     * @return bool
     */
    public function configLogging(array $conditions): bool
    {
        if (!$this->Auth->validateLogged()) {
            $access = 0;
            foreach ($this->Auth->config()['authenticate'] as $condition) {
                if (in_array($condition, array_keys($conditions)) === false) {
                    $access++;
                }
            }
            return $access != 0;
        }
        return false;
    }

    /**
     * @param array $conditions
     *
     * @return bool
     */
    public function validateAuth(array $conditions): bool
    {
        $datas = [];
        $deleteIdLevel = true;
        foreach ($conditions as $key => $value) {
            $datas[$key] = $value;
        }
        if (isset($conditions['idLevel'])) {
            $deleteIdLevel = !$deleteIdLevel;
        }
        if ($this->use['validation']) {
            return $this->model->validate($datas)->deletesValidsAuth($deleteIdLevel)->validation();
        }
        return false;
    }

    /**
     * @param array $tables
     * @param array $options
     *
     * @return object
     */
    public function tableRegistory(array $tables, array $options = []): object
    {
        $return = 'controller';
        if (isset($options['return'])) {
            $return = $options['return'];
            unset($options['return']);
        }
        if ($return === 'repository') {
            return $this->model->scannigTheMetadata($tables, $options)->metadataScanningExecuted();
        }
        $this->model = $this->model->scannigTheMetadata($tables, $options);
        return $this;
    }

    /**
     * @param array $table
     *
     * @return Controller
     * @throws Exceptions
     */
    public function validUserModelAndNameTable(array $table): Controller
    {
        if (!$this->use['ORM']) {
            throw new Exceptions(
                'You did not instantiate the model. To instantiate, go to the AppController and type $this->use["ORM"] = true in the initialize method.',
                404
            );
        }
        if (!array_key_exists('main', $table)) {
            throw new Exceptions('The table you are using cannot be an array with the main key.', 404);
        }
        return $this;
    }

    /**
     * @param string $name
     *
     * @return Controller
     */
    public function unsetParamSet(string $name): Controller
    {
        unset($this->view[$name]);
        return $this;
    }

    /**
     * @param string $type
     * @return Controller
     * @throws Exceptions
     */
    public function validType(string $type): Controller
    {
        if (in_array(
                $type,
                [
                    'open',
                    'all',
                    'first',
                    'countRows',
                    'union',
                    'built',
                    'union and built',
                    'built and union',
                    'create',
                    'update',
                    'delete'
                ]
            ) === false) {
            throw new Exceptions(
                "this {$type} type is not accepted, they are these types: open, all, first, countRows, union, built, union and built, built and union, create, update or delete.",
                404
            );
        }
        return $this;
    }

    /**
     * @param bool $returnDatas
     *
     * @return mixed
     */
    protected function appTable(bool $retunDatas = false)
    {
        if ($retunDatas) {
            $this->typeQuery('update');
        }
        $this->model->businessRules();
        if ($retunDatas) {
            return $this->model->optionsQuery();
        }
        return $this;
    }

    /**
     * @param int $count
     * @param array $details
     * @param array $fields
     *
     * @return Controller
     */
    protected function assemblyQuery(int $count, array $details, array $fields): Controller
    {
        $typeExecuteQuery = $this->model->executionTypeForQuery();
        if ($count > 0) {
            if ($count > 1) {
                for ($a = 0; $a < $count; $a++) {
                    $detail['deleteLimit'][$a] = $details['deleteLimit'][0];
                }
            }
            if (isset($detail)) {
                $details['deleteLimit'] = array_merge($details['deleteLimit'], $detail['deleteLimit']);
                unset($detail);
            }
            $this->model->editLimit(
                $details['deleteLimit'],
                ['fields' => $fields, 'repository' => $details['repository']],
                $typeExecuteQuery
            );
        } else {
            $this->model->queryAssembly(
                [
                    'deleteLimit' => $details['deleteLimit'],
                    'joinLimit' => $count > 0,
                    'returnResult' => $typeExecuteQuery !== 'open'
                ],
                $details['lastId']
            );
        }
        return $this;
    }

    /**
     * @param array $fields
     * @param bool $repository
     *
     * @return object
     */
    protected function excuting(array $fields, bool $repository): object
    {
        $newFields = [];
        if (in_array($this->model->executionTypeForQuery(), ['delete', 'details']) === false) {
            $count = count($fields);
            for ($a = 0; $a < $count; $a++) {
                if (isset($fields[$a]['fields'])) {
                    $newFields = array_merge($newFields, $fields[$a]['fields']);
                }
            }
        }
        return $this->model->excuteQuery($repository, $newFields);
    }

    /**
     * @param string $view
     *
     * @return Controller
     */
    protected function render(string $view): Controller
    {
        $this->action = $view;
        return $this;
    }

    /**
     * @param string $folder
     *
     * @return Controller
     */
    protected function folder(string $folder): Controller
    {
        $this->request->name = $folder;
        $this->name = $folder;
        return $this;
    }

    /**
     * @param array $details
     *
     * @return array
     */
    protected function checkDetails(string $type, array $details): array
    {
        $newDetails = [];
        foreach (
            [
                'repository',
                'lastId',
                'deleteLimit',
                'validate',
                'businessRules',
                'entityColumn',
                'notDelete',
            ] as $key
        ) {
            if (!isset($details[$key])) {
                switch ($key) {
                    case "repository":
                    case "validate":
                    case "entityColumn":
                        $newDetails[$key] = true;
                        break;
                    case "lastId":
                    case "notDelete":
                        $newDetails[$key] = false;
                        break;
                    case "deleteLimit":
                        $newDetails[$key][0] = false;
                        break;
                    case "businessRules":
                        $newDetails[$key] = in_array($type, ['delete', 'remove']) === false;
                        break;
                }
            }
        }
        return array_merge($details, $newDetails);
    }

    /**
     * @param string $action
     * @return Controller
     * @throws Exceptions
     */
    protected function builder(string $action): Controller
    {
        $email = $this->instance->resolveClass(
            ROOT_NAMESPACE[0] . DS . 'Mail' . DS . 'Email' . MVC[1]
        );
        $email->run($this, $action);
        return $this;
    }

}
