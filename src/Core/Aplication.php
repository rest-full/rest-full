<?php

namespace Restfull\Core;

use Restfull\Error\Exceptions;

/**
 * Class Aplication
 * @package Restfull\Core
 */
class Aplication
{

    /**
     * @var array
     */
    public $config = [];

    /**
     * @var array
     */
    public $bootstrap = [];

    /**
     * @param string|null $command
     * @return Aplication
     * @throws Exceptions
     */
    public function bootstrap(string $command = null): Aplication
    {
        if (!isset($command)) {
            $this->configsAplictions();
            ini_set('display_errors', $this->config['error']);
            if (ini_get('session.gc_maxlifetime') != $this->config['security']['time']) {
                ini_set('session.gc_maxlifetime', $this->config['security']['time']);
            }
            unset($this->config['security']['time']);
            date_default_timezone_set($this->config['app']['defaultTimezone']);
            mb_internal_encoding($this->config['app']['encoding']);
            ini_set('intl.default_locale', $this->config['app']['defaultLocale']);
            $this->bootstrapDatabase('default', []);
            Configure::checkAndInstanceUserLogged();
            Configure::email($this->config['email']);
            Configure::security($this->config['security']['salt']);
            Configure::plugins($this->config['plugins']);
            Configure::pdf($this->config['pdf']);
            $this->bootstrap = Configure::returnsConfings();
        } else {
            ini_set('intl.default_locale', '');
            Configure::close();
            unset($this->bootstrap);
        }
        return $this;
    }

    /**
     * @return Aplication
     */
    public function configsAplictions(): Aplication
    {
        require_once ROOT . 'config' . DS . 'app.php';
        $this->config = $config;
        $this->config['error'] = ($_SERVER['HTTP_HOST'] == 'localhost') || ($_SERVER['HTTP_HOST'] == '127.0.0.1');
        if ($_SERVER['PATH'] == '/usr/local/bin:/bin:/usr/bin') {
            foreach ($this->config['html'] as $key => $value) {
                if ($key != 'path') {
                    $this->config['html'][$key] = substr($value, -4);
                }
            }
        }
        return $this;
    }

    /**
     * @param string $place
     * @param array $config
     * @param string $action
     * @return bool|mixed|string
     * @throws Exceptions
     */
    public function bootstrapDatabase(string $place, array $config, string $action = 'exchange')
    {
        $checkDatabase = '';
        if ($_SERVER['HTTP_HOST'] == 'localhost') {
            $place = 'test';
        }
        require ROOT . 'config' . DS . 'database.php';
        if (in_array($place, array_keys($database)) === false) {
            throw new Exceptions("This database has not been defined in the ../config/database.php file", 405);
        }
        foreach ($database as $key => $values) {
            $keys = array_keys($values);
            for ($a = 0; $a < count($keys); $a++) {
                if (in_array($keys[$a], ['host', 'username', 'password', 'dbname', 'drive']) === false) {
                    throw new Exceptions('The ' . $key[$a] . ' key not found.');
                } elseif ($_SERVER['HTTP_HOST'] != 'localhost') {
                    if (empty($values[$keys[$a]])) {
                        throw new Exceptions('The value that ' . $keys[$a] . ' key not empty.');
                    }
                }
                if (count($config) > 0) {
                    if ($values[$keys[$a]] != $config[$keys[$a]]) {
                        $this->database[$key][$keys[$a]] = $values[$keys[$a]];
                    }
                } else {
                    $this->database[$key][$keys[$a]] = $values[$keys[$a]];
                }
            }
            $this->database[$key] = array_merge(
                    $this->database[$key],
                    [
                            'charset' => 'utf8',
                            'collation' => 'utf8_general_ci'
                    ]
            );
        }
        Configure::configureDataBase($this->database, $place);
        if ($action == 'validate') {
            $checkDatabase = Configure::validateConnection($place);
        } elseif ($action == 'conneting') {
            return Configure::returnsConfings()['database'];
        }
        return $checkDatabase;
    }

    /**
     * @param string $key
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
