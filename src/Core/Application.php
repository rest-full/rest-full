<?php

declare(strict_types=1);

namespace Restfull\Core;

use Restfull\Container\Instances;
use Restfull\Error\Exceptions;
use Restfull\Http\Request;

/**
 *
 */
class Application
{

    /**
     * @var Configure
     */
    public $config;

    /**
     * @var array
     */
    public $bootstrap = [];

    /**
     * @var Instances
     */
    private $instance;

    /**
     * @param Instances $instance
     * @throws Exceptions
     */
    public function __construct(Instances $instance)
    {
        $this->config = $instance->resolveClass(
            ROOT_NAMESPACE[0] . DS_REVERSE . 'Core' . DS_REVERSE . 'Configure',
            ['instance' => $instance]
        );
        $this->instance = $instance;
        return $this;
    }

    /**
     * @param string|null $command
     *
     * @return Application
     * @throws Exceptions
     */
    public function bootstrap(string $command = null): Application
    {
        if (!isset($command)) {
            $config = $this->configsAplictions();
            ini_set('display_errors', $config['error']);
            if (ini_get('session.gc_maxlifetime') != $config['security']['time']) {
                ini_set('session.gc_maxlifetime', $config['security']['time']);
            }
            unset($config['security']['time']);
            date_default_timezone_set($config[strtolower(ROOT_NAMESPACE[1])]['defaultTimezone']);
            mb_internal_encoding($config[strtolower(ROOT_NAMESPACE[1])]['encoding']);
            ini_set('intl.default_locale', $config[strtolower(ROOT_NAMESPACE[1])]['defaultLocale']);
            $this->bootstrapDatabase();
            $this->config->checkAndInstanceUserLogged();
            $this->config->cacheActive($config['cache']['active'], $config['cache']['expirationTime']);
            $this->config->email($config['email']);
            $this->config->security($config['security']['salt']);
            $this->config->plugins($this->instance, $config['plugins']);
            $this->config->pdf($config['pdf']);
            $this->config->otherMiddleware($config['middleware']);
            $this->bootstrap = $this->config->returnsConfings();
            $this->bootstrap[strtolower(ROOT_NAMESPACE[1])] = $this;
        } else {
            ini_set('intl.default_locale', '');
            $this->config->close();
            unset($this->bootstrap);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function configsAplictions(): array
    {
        require_once ROOT . 'config' . DS . 'app.php';
        $config['error'] = ($_SERVER['SERVER_NAME'] === 'localhost') || ($_SERVER['HTTP_HOST'] === '127.0.0.1');
        if ($_SERVER['PATH'] === '/usr/local/bin:/bin:/usr/bin') {
            foreach ($config['html'] as $key => $value) {
                if ($key != 'path') {
                    $config['html'][$key] = substr($value, -4);
                }
            }
        }
        return $config;
    }

    /**
     * @param string $place
     * @param array $config
     * @param string $action
     *
     * @return bool|mixed|string
     * @throws Exceptions
     */
    public function bootstrapDatabase(string $action = 'exchange'): Application
    {
        $checkDatabase = '';
        if ($_SERVER['HTTP_HOST'] === 'localhost') {
            $place = 'test';
        }
        require ROOT . 'config' . DS . 'database.php';
        if (in_array($place, array_keys($databases)) === false) {
            throw new Exceptions("This database has not been defined in the ../config/database.php file", 405);
        }
        foreach ($databases as $key => $values) {
            $keys = array_keys($values);
            $count = count($keys);
            for ($a = 0; $a < $count; $a++) {
                if ($keys[$a] != 'port') {
                    if (in_array($keys[$a], ['host', 'username', 'password', 'dbname', 'drive']) === false) {
                        throw new Exceptions('The ' . $keys[$a] . ' key not found.');
                    } elseif (empty($values[$keys[$a]])) {
                        throw new Exceptions('The value that ' . $keys[$a] . ' key not empty.');
                    }
                }
            }
            $databases[$key] = array_merge($databases[$key], ['charset' => 'utf8', 'collation' => 'utf8_general_ci']);
        }
        $this->config->configureDatabase($databases, $place);
        return $this;
    }

    /**
     * @param Request $requset
     * @param string $place
     * @return bool
     */
    public function changeConnectionDatabase(Request $requset, string $place): bool
    {
        if ($place !== 'default' || $place !== 'test') {
            $database = $requset->bootstrap('database');
            if ($database->placeInstantiated() !== $place) {
                $checkDatabase = false;
                $database->place($place);
                if ($database->validateExistingConnectionData()) {
                    $checkDatabase = $database->validateConnection();
                    if (!$checkDatabase) {
                        $checkDatabase = true;
                        $database->pdo('manipulation');
                    }
                }
                $requset->changeBootstrap('database', $database);
                return $checkDatabase;
            }
            return false;
        }
        return false;
    }

    /**
     * @return string
     */
    public function returnDatabase(): string
    {
        return $this->config->returnsConfings()['database'];
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function checkEmailPdfHtml(string $key): bool
    {
        if (isset($this->bootstrap[$key])) {
            $values = $this->bootstrap[$key];
            switch ($key) {
//                case "html":
                case "pdf":
                    $data = $this->bootstrap['pdf']->getGeration();
                case "email":
                    $data = $this->bootstrap['email']->getMail();
            }
            return $data['active'];
        }
        return false;
    }

}
