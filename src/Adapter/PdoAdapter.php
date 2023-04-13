<?php

namespace Linkedcode\DataMapper\Adapter;

use PDO;
use PDOException;
use RuntimeException;

class PdoAdapter implements DatabaseAdapterInterface
{
    protected $config = array();

    /**
     * @var PDO
     */
    protected $connection;
    protected $statement;
    protected $fetchMode = PDO::FETCH_OBJ;

    protected $joins = array(
        self::INNER_JOIN => []
    );

    public const INNER_JOIN = 'INNER JOIN';

    
    public function __construct(
        $dsn,
        $username = null,
        $password = null,
        array $driverOptions = array()
    ) {
        $this->config = compact("dsn", "username", "password", 
            "driverOptions");
    }

    public function getStatement()
    {
        if ($this->statement === null) {
            throw new PDOException(
              "There is no PDOStatement object for use.");
        } 
        return $this->statement;
    }
    
    public function connect()
    {
        // if there is a PDO object already, return early
        if ($this->connection) {
            return;
        }
 
        try {
            $this->connection = new PDO(
                $this->config["dsn"],
                $this->config["username"],
                $this->config["password"],
                $this->config["driverOptions"]
            );
            
            $this->connection->setAttribute(
                PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION
            );
            
            $this->connection->setAttribute(
                PDO::ATTR_EMULATE_PREPARES, false
            );
            
            $this->connection->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ
            );
        }
        catch (PDOException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }
    
    public function disconnect()
    {
        $this->connection = null;
    }
    
    public function prepare($sql, array $options = array())
    {
        $this->connect();
        try {
            $this->statement = $this->connection->prepare($sql, 
                $options);
            return $this;
        }
        catch (PDOException $e) {
            throw new RunTimeException($e->getMessage());
        }
    }
    
    public function execute(array $parameters = array()) {
        try {
            $this->getStatement()->execute($parameters);
            return $this;
        }
        catch (PDOException $e) {
            throw new RunTimeException($e->getMessage());
        }
    }
    
    public function countAffectedRows() {
        try {
            return $this->getStatement()->rowCount();
        }
        catch (PDOException $e) {
            throw new RunTimeException($e->getMessage());
        }
    }

    public function getLastInsertId($name = null) {
        $this->connect();
        return $this->connection->lastInsertId($name);
    }
    
    public function fetch(
        $fetchStyle = null,
        $cursorOrientation = null,
        $cursorOffset = null
    ) {
        if ($fetchStyle === null) {
            $fetchStyle = $this->fetchMode;
        }
 
        try {
            return $this->getStatement()->fetch($fetchStyle, 
                $cursorOrientation, $cursorOffset);
        }
        catch (PDOException $e) {
            throw new RunTimeException($e->getMessage());
        }
    }
     
    public function fetchAll($fetchStyle = null, $column = 0)
    {
        if ($fetchStyle === null) {
            $fetchStyle = $this->fetchMode;
        }
 
        try {
            return $fetchStyle === PDO::FETCH_COLUMN
               ? $this->getStatement()->fetchAll($fetchStyle, $column)
               : $this->getStatement()->fetchAll($fetchStyle);
        }
        catch (PDOException $e) {
            throw new RunTimeException($e->getMessage());
        }
    }
    
    public function select(
        $table,
        array $bind = array(),
        $boolOperator = "AND"
    ) {
        if ($bind) {
            $where = array();
            foreach ($bind as $col => $value) {
                unset($bind[$col]);
                if (is_array($value)) {
                    $b = array();
                    foreach ($value as $vid => $val) {
                        $bind[":" . $col.$vid] = $val;
                        $b[] = ":" . $col . $vid;
                    }
                    $where[] = $col . " IN (" . implode(",", $b) . ")";
                } else {
                    $bind[":" . $col] = $value;
                    $where[] = $col . " = :" . $col;
                }
            }
        }
 
        $joins = $this->generateJoins();

        //{$table}.
        $sql = "SELECT * FROM {$table} {$joins} "
            . (($bind) ? " WHERE "
            . implode(" " . $boolOperator . " ", $where) : " ");
        error_log($sql);
        $this->prepare($sql)
            ->execute($bind);
        return $this;
    }

    public function reset()
    {
        $this->joins = [];
    }
    
    protected function generateJoins()
    {
        $statements = [];
        foreach ($this->joins as $type => $joins) {
            foreach ($joins as $join) {
                $statements[] = $type . " " . $join['table'] . " ON " . $join['on'];
            }
        }

        return implode(" ", $statements);
    }

    public function insert($table, array $bind)
    {
        $cols = implode(", ", array_keys($bind));
        $values = implode(", :", array_keys($bind));
        foreach ($bind as $col => $value) {
            unset($bind[$col]);
            $bind[":" . $col] = $value;
        }
 
        $sql = "INSERT INTO " . $table
            . " (" . $cols . ")  VALUES (:" . $values . ")";
        return (int) $this->prepare($sql)
            ->execute($bind)
            ->getLastInsertId();
    }
    
    public function update($table, array $bind, $where = "")
    {
        $set = array();
        foreach ($bind as $col => $value) {
            unset($bind[$col]);
            $bind[":" . $col] = $value;
            $set[] = $col . " = :" . $col;
        }
 
        $sql = "UPDATE " . $table . " SET " . implode(", ", $set)
            . (($where) ? " WHERE " . $where : " ");
        return $this->prepare($sql)
            ->execute($bind)
            ->countAffectedRows();
    }
    
    public function delete($table, $where = "")
    {
        $sql = "DELETE FROM " . $table . (($where) ? " WHERE " . $where : " ");
        return $this->prepare($sql)->execute()->countAffectedRows();
    }

    public function join($table, $on, $type = self::INNER_JOIN)
    {
        $this->joins[$type][] = array(
            'table' => $table,
            'on' => $on,
        );
    }

    public function exec($sql)
    {
        $this->connect();
        return $this->connection->exec($sql);
    }
}
