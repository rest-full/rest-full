<?php

declare(strict_types=1);

namespace Restfull\Database;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PDO;
use stdClass;

/**
 *
 */
class Query
{

    /**
     * @var string
     */
    protected $query = '';

    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var bool
     */
    private $transactionActive = false;

    /**
     * @var bool
     */
    private $execute = true;

    /**
     * @param array $http
     */
    public function __Construct(Database $database, string $command)
    {
        $this->pdo = $database->pdo($command);
        if ($database->place('', true) != 'test') {
            $this->transactionActive = !$this->transactionActive;
            $this->pdo->beginTransaction();
        }
        return $this;
    }

    /**
     * @param Table $data
     *
     * @return object
     */
    public function save(Table $data): object
    {
        $sql = $data->query();
        $this->query = $this->pdo->prepare($sql);
        if ($data->count() > 0) {
            foreach ($data->bindValues() as $key => $value) {
                $param = PDO::PARAM_STR;
                if ($value === 'null') {
                    $value = null;
                    $param = PDO::PARAM_NULL;
                    $sql = str_replace($key, "null", $sql);
                } else {
                    $sql = str_replace($key, "'" . trim($value) . "'", $sql);
                }
                $this->query->bindValue($key, $value, $param);
            }
        }
        if (stripos($sql, 'INFORMATION_SCHEMA.COLUMNS') === false) {
            $log = new Logger("Erros");
            $log->pushHandler(new StreamHandler(ROOT . DS . "log" . DS . "sql.log"));
            $log->log('500', $sql);
        }
        $this->query->execute();
        if (!$execute && $this->transactionActive) {
            $this->execute = !$this->execute;
        }
        $execute = new stdClass();
        $execute->result = true;
        return $execute;
    }

    /**
     * @param Table $data
     *
     * @return object
     */
    public function query(Table $data): object
    {
        $this->query = $this->pdo->query($data->query());
        $this->query->execute();
        if (!$execute && $this->transactionActive) {
            $this->execute = !$this->execute;
        }
        $execute = new stdClass();
        $execute->result = true;
        return $execute;
    }

    /**
     * @return object
     */
    public function count(): object
    {
        $execute = new stdClass();
        $execute->countRows = $this->query->rowCount();
        return $execute;
    }

    /**
     * @param Table $data
     *
     * @return object
     */
    public function update(Table $data): object
    {
        $sql = $data->query();
        $this->query = $this->pdo->prepare($sql);
        if ($data->count() > 0) {
            foreach ($data->bindValues() as $key => $value) {
                $param = PDO::PARAM_STR;
                if ($value === 'null') {
                    $value = null;
                    $param = PDO::PARAM_NULL;
                    $sql = str_replace($key, "null", $sql);
                } else {
                    $sql = str_replace($key, "'" . trim($value) . "'", $sql);
                }
                $this->query->bindValue($key, trim($value), $param);
            }
        }
        if (stripos($sql, 'INFORMATION_SCHEMA.COLUMNS') === false) {
            $log = new Logger("Erros");
            $log->pushHandler(new StreamHandler(ROOT . DS . "log" . DS . "sql.log"));
            $log->log('500', $sql);
        }
        $this->query->execute();
        if (!$execute && $this->transactionActive) {
            $this->execute = !$this->execute;
        }
        $execute = new stdClass();
        $execute->result = true;
        return $execute;
    }

    /**
     * @param Table $data
     *
     * @return object
     */
    public function delete(Table $data): object
    {
        $sql = $data->query();
        $this->query = $this->pdo->prepare($sql);
        if ($data->count() > 0) {
            foreach ($data->bindValues() as $key => $value) {
                $param = PDO::PARAM_STR;
                if ($value === 'null') {
                    $value = null;
                    $param = PDO::PARAM_NULL;
                    $sql = str_replace($key, "null", $sql);
                } else {
                    $sql = str_replace($key, "'" . trim($value) . "'", $sql);
                }
                $this->query->bindValue($key, trim($value), $param);
            }
        }
        if (stripos($sql, 'INFORMATION_SCHEMA.COLUMNS') === false) {
            $log = new Logger("Erros");
            $log->pushHandler(new StreamHandler(ROOT . DS . "log" . DS . "sql.log"));
            $log->log('500', $sql);
        }
        $this->query->execute();
        if (!$execute && $this->transactionActive) {
            $this->execute = !$this->execute;
        }
        $execute = new stdClass();
        $execute->result = true;
        return $execute;
    }

    /**
     * @param Table $data
     *
     * @return Query
     */
    public function select(Table $data): Query
    {
        $sql = $data->query();
        $this->query = $this->pdo->prepare($data->query());
        if ($data->count() > 0) {
            foreach ($data->bindValues() as $key => $value) {
                $param = PDO::PARAM_STR;
                if ($value === 'null') {
                    $value = null;
                    $param = PDO::PARAM_NULL;
                    $sql = str_replace($key, "null", $sql);
                } else {
                    $sql = str_replace($key, "'" . trim($value) . "'", $sql);
                }
                $this->query->bindValue($key, trim($value), $param);
            }
        }
        if (stripos($sql, 'INFORMATION_SCHEMA.COLUMNS') === false) {
            $log = new Logger("Erros");
            $log->pushHandler(new StreamHandler(ROOT . DS . "log" . DS . "sql.log"));
            $log->log('500', $sql);
        }
        $execute = $this->query->execute();
        if (!$execute && $this->transactionActive) {
            $this->execute = !$this->execute;
        }
        return $this;
    }

    /**
     * @param Table $data
     *
     * @return object
     */
    public function truncate(Table $data): object
    {
        $this->query = $this->pdo->prepare($data->query());
        $this->query->execute();
        if (!$execute && $this->transactionActive) {
            $this->execute = !$this->execute;
        }
        $execute = new stdClass();
        $execute->result = true;
        return $execute;
    }

    /**
     * @return object
     */
    public function all(): object
    {
        $result = new StdClass();
        foreach ($this->query->fetchAll() as $key => $values) {
            $result->{$key} = $values;
        }
        return $result;
    }

    /**
     * @return object
     */
    public function first(): object
    {
        $return = $this->query->fetch();
        if (is_bool($return)) {
            $return = new stdClass();
        }
        return $return;
    }

    /**
     * @return object
     */
    public function lastPrimaryKey(): object
    {
        $execute = new stdClass();
        $execute->lastId = $this->pdo->lastInsertId();
        return $excute;
    }

    /**
     *
     */
    public function __destruct()
    {
        if ($this->transactionActive) {
            if ($this->execute) {
                $this->pdo->commit();
            } else {
                $this->pdo->rollBack();
            }
        }
    }

}
