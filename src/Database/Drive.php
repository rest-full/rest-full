<?php

declare(strict_types=1);

namespace Restfull\Database;

/**
 *
 */
class Drive
{

    /**
     * @var string
     */
    private static $drive = '';

    /**
     * @return string
     */
    public static function getDrive(): string
    {
        return self::$drive;
    }

    /**
     * @param array $banco
     */
    public static function setDrive(array $banco): void
    {
        self::$drive = self::driveConnect($banco['drive'], $banco);
        return;
    }

    /**
     * @param string $drive
     * @param array $config
     *
     * @return string
     */
    public static function driveConnect(string $drive, array $config): string
    {
        unset($config['collation']);
        if ($drive != "pgsql") {
            unset($config['username'], $config['password']);
        }
        if (in_array($drive, ["pgsql", 'cubrid', 'informix'])) {
            switch ($drive) {
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
        unset($config["drive"]);
        if (in_array($drive, ["ibm", "informix"])) {
            if ($drive === "ibm") {
                $order = ['dbname', 'host', 'port'];
            } else {
                $order = ['host', 'port', 'dbname'];
            }
            foreach ($order as $value) {
                if (array_key_exists($value, $config)) {
                    $newconfig[$value] = $config[$value];
                }
            }
            if ($drive === "ibm") {
                $newconfig = araay_merge($newconfig, ['protocol' => 'PROTOCOL=TCPIP']);
            } else {
                $newconfig = araay_merge(
                    $newconfig,
                    [
                        'serve' => 'server=ids_server',
                        'protocol' => 'PROTOCOL=TCPIP',
                        'EnableScrollableCursos' => 'EnableScrollableCursos=1'
                    ]
                );
            }
        } else {
            foreach (['host', 'port', 'dbname', 'charset'] as $value) {
                if (array_key_exists($value, $config)) {
                    $newconfig[$value] = $config[$value];
                }
            }
        }
        $keys = array_keys($newconfig);
        $count = count($keys);
        for ($a = 0; $a < $count; $a++) {
            $config[$keys[$a]] = $keys[$a] . "=" . $newconfig[$keys[$a]] . ";";
        }
        return $drive . ":" . implode("", $config);
    }
}
