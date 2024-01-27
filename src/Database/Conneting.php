<?php

declare(strict_types=1);

namespace Restfull\Database;

use PDO;
use PDOException;
use Restfull\Error\Exceptions;

/**
 *
 */
class Conneting
{

    /**
     * @var array
     */
    private static $banks = [];

    /**
     * @var array
     */
    private static $connections = [];

    /**
     * @var string
     */
    private static $usingConnection;

    /**
     * @var array
     */
    private static $timestamp = [];

    /**
     * @var PDO
     */
    private static $PDO;

    /**
     * @var string
     */
    private static $drive = '';

    /**
     * @return PDO
     * @throws Exceptions
     */
    public static function connectedDatabase(string $command): PDO
    {
        $PDO = '';
        if (self::$usingConnection != 'default') {
            if (isset(self::$connections[self::$usingConnection])) {
                $PDO = self::$connections[self::$usingConnection];
            }
        }
        if (!($PDO instanceof PDO)) {
            $PDO = self::connection($command);
            self::$connections[self::$usingConnection] = $PDO;
        }
        return $PDO;
    }

    /**
     * @return PDO
     * @throws Exceptions
     */
    private static function connection(string $command): PDO
    {
        $dbtype = self::driveConnect($command);
        $bank = self::$banks[self::$usingConnection];
        self::conexaoPDO();
        if (empty(self::$PDO)) {
            try {
                if (in_array(self::$usingConnection, ['default', 'test']) === false) {
                    $dbtype = str_replace(self::$banks['default']['host'], $bank['host'], $dbtype);
                    $dbtype = str_replace(self::$banks['default']['dbname'], $bank['dbname'], $dbtype);
                }
                if (substr($dbtype, 0, stripos($dbtype, ":")) != "pgsql") {
                    self::$PDO = new PDO($dbtype, $bank['username'], $bank['password']);
                } else {
                    self::$PDO = new PDO($dbtype);
                }
            } catch (PDOException $e) {
                throw new Exceptions($e, 500);
            }
        }
        self::$timestamp[self::$usingConnection] = strtotime(
            date('Y-m-d H:i:s') . ' +25 minutes'
        );
        self::$PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$PDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        self::$PDO->setAttribute(
            PDO::MYSQL_ATTR_INIT_COMMAND,
            "SET NAME " . $bank['charset'] . " COLLATE " . $bank['collation']
        );
        return self::$PDO;
    }

    /**
     * @param string $drive
     * @param array $config
     *
     * @return string
     */
    public static function driveConnect(string $command): string
    {
        $config = self::$banks[self::$usingConnection];
        if ($command !== 'manipulation') {
            unset($config['dbname']);
        }
        unset($config['collation']);
        if ($config['drive'] != "pgsql") {
            unset($config['username'], $config['password']);
        }
        if (in_array($config['drive'], ["pgsql", 'cubrid', 'informix'])) {
            switch ($config['drive']) {
                case "pgsql":
                    $config['port'] = 'port=5432;';
                    break;
                case "cubrid":
                    $config['port'] = 'port=33000;';
                    break;
                case "informix":
                    $config['port'] = 'service=9800;';
                    break;
            }
        }
        $newconfig = '';
        if (in_array($config['drive'], ["ibm", "informix"])) {
            $order = $config['drive'] === "ibm" ? ['dbname', 'host', 'port'] : ['host', 'port', 'dbname'];
            foreach ($order as $value) {
                if (array_key_exists($value, $config)) {
                    $newconfig .= $value . '=' . $config[$value] . ';';
                    unset($config[$value]);
                }
            }
            $newconfig .= $config['drive'] === "ibm" ? 'PROTOCOL=TCPIP;' : 'server=ids_server;PROTOCOL=TCPIP;EnableScrollableCursos=1;';
        } else {
            foreach (['host', 'port', 'dbname', 'charset'] as $value) {
                if (array_key_exists($value, $config)) {
                    $newconfig .= $value . '=' . $config[$value] . ';';
                    unset($config[$value]);
                }
            }
        }
        return $config['drive'] . ":" . $newconfig;
    }

    /**
     *
     */
    private static function conexaoPDO(): void
    {
        self::$PDO = isset(self::$connections[self::$usingConnection]) ? self::$connections[self::$usingConnection] : '';
        if (!empty(self::$PDO)) {
            if (!isset(self::$timestamp[self::$usingConnection]) || (strtotime(
                        date('Y-m-d H:i:s')
                    ) > self::$timestamp[self::$usingConnection])) {
                self::$PDO = '';
            }
        }
        return;
    }

    /**
     * @param array $banks
     */
    public static function banks(array $banks = [])
    {
        if (count($banks) === 0) {
            return self::$banks;
        }
        self::$banks = $banks;
        return;
    }

    /**
     * @param string $place
     */
    public static function connexion(string $place): void
    {
        self::$usingConnection = $place;
        return;
    }

}
