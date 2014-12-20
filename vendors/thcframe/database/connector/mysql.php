<?php

namespace THCFrame\Database\Connector;

use THCFrame\Database as Database;
use THCFrame\Database\Exception as Exception;
use THCFrame\Profiler\Profiler;
use THCFrame\Model\Model;
use THCFrame\Core\Core;

/**
 * The Database\Connector\Mysql class defines a handful of adaptable 
 * properties and methods used to perform MySQLi class-specific functions, 
 * and return MySQLi class-specific properties. We want to isolate these from 
 * the outside so that our system is essentially plug-and-play
 */
class Mysql extends Database\Connector
{

    protected $_service;

    /**
     * @readwrite
     */
    protected $_host;

    /**
     * @readwrite
     */
    protected $_username;

    /**
     * @readwrite
     */
    protected $_password;

    /**
     * @readwrite
     */
    protected $_schema;

    /**
     * @readwrite
     */
    protected $_port = '3306';

    /**
     * @readwrite
     */
    protected $_charset = 'utf8';

    /**
     * @readwrite
     */
    protected $_engine = 'InnoDB';

    /**
     * @readwrite
     */
    protected $_isConnected = false;

    /**
     * @read
     */
    protected $_magicQuotesActive;

    /**
     * @read
     */
    protected $_realEscapeStringExists;

    /**
     * Class constructor
     * 
     * @param array $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->_magicQuotesActive = get_magic_quotes_gpc();
        $this->_realEscapeStringExists = function_exists('mysqli_real_escape_string');
    }

    /**
     * 
     * @param type $error
     * @param type $sql
     */
    protected function _logError($error, $sql)
    {
        $errMessage = sprintf('There was an error in the query %s', $error) . PHP_EOL;
        $errMessage .= 'SQL: ' . $sql;

        Core::getLogger()->log($errMessage);
    }
    
    /**
     * Method is used to ensure that the value of the
     * $_service is a valid MySQLi instance
     * 
     * @return boolean
     */
    protected function _isValidService()
    {
        $isEmpty = empty($this->_service);
        $isInstance = $this->_service instanceof \MySQLi;

        if ($this->isConnected && $isInstance && !$isEmpty) {
            return true;
        }
        return false;
    }

    /**
     * Method attempts to connect to the MySQL server at the specified host/port
     * 
     * @return \THCFrame\Database\Connector\Mysql
     * @throws Exception\Service
     */
    public function connect()
    {
        if (!$this->_isValidService()) {
            $this->_service = new \MySQLi(
                    $this->_host, $this->_username, $this->_password, $this->_schema, $this->_port
            );

            if ($this->_service->connect_error) {
                throw new Exception\Service('Unable to connect to database service');
            }

            $this->_service->set_charset('utf8');
            $this->_service->query("SET NAMES 'utf8' COLLATE 'utf8_general_ci'");

            $this->isConnected = true;
            unset($this->_password);
        }

        return $this;
    }

    /**
     * Method attempts to disconnect the $_service instance from the MySQL service
     * 
     * @return \THCFrame\Database\Connector\Mysql
     */
    public function disconnect()
    {
        if ($this->_isValidService()) {
            $this->isConnected = false;
            $this->_service->close();
        }

        return $this;
    }

    /**
     * Return query object for specific connector
     * 
     * @return \THCFrame\Database\Database\Query\Mysql
     */
    public function query()
    {
        return new Database\Query\Mysql(array(
            'connector' => $this
        ));
    }

    /**
     * Method execute sql query by using prepared statements
     * 
     * @param string $sql
     * @return mysqli_stmt
     * @throws Exception\Service
     */
    public function execute($sql)
    {
        if (!$this->_isValidService()) {
            throw new Exception\Service('Not connected to a valid database service');
        }

        $profiler = Profiler::getInstance();

        $args = func_get_args();

        if (count($args) == 1) {
            $profiler->dbQueryStart($sql);
            $result = $this->_service->query($sql);
            $profiler->dbQueryStop($this->getAffectedRows());

            return $result;
        }

        $profiler->dbQueryStart($sql);
        if (!$stmt = $this->_service->prepare($sql)) {
            $this->_logError($this->_service->error, $sql);

            if (ENV == 'dev') {
                throw new Exception\Sql(sprintf('There was an error in the query %s', $this->_service->error));
            } else {
                throw new Exception\Sql('There was an error in the query');
            }
        }

        array_shift($args); //remove sql from args

        $bindParamsReferences = array();

        foreach ($args as $key => $value) {
            $bindParamsReferences[$key] = &$args[$key];
        }

        $types = str_repeat('s', count($args)); //all params are strings, works well on MySQL and SQLite
        array_unshift($bindParamsReferences, $types);

        $bindParamsMethod = new \ReflectionMethod('mysqli_stmt', 'bind_param');
        $bindParamsMethod->invokeArgs($stmt, $bindParamsReferences);

        $stmt->execute();
        $profiler->dbQueryStop($stmt->affected_rows);
        $meta = $stmt->result_metadata();

        if ($meta) {
            $stmtRow = array();
            $rowReferences = array();

            while ($field = $meta->fetch_field()) {
                $rowReferences[] = &$stmtRow[$field->name];
            }

            $bindResultMethod = new \ReflectionMethod('mysqli_stmt', 'bind_result');
            $bindResultMethod->invokeArgs($stmt, $rowReferences);

            $result = array();
            while ($stmt->fetch()) {
                foreach ($stmtRow as $key => $value) {
                    $row[$key] = $value;
                }
                $result[] = $row;
            }

            $stmt->free_result();
            $stmt->close();

            return $result;
        } else {
            return null;
        }
    }

    /**
     * Escapes values
     * 
     * @param mixed $value
     * @return mixed
     * @throws Exception\Service
     */
    public function escape($value)
    {
        if (!$this->_isValidService()) {
            throw new Exception\Service('Not connected to a valid database service');
        }

        if ($this->realEscapeStringExists) {
            if ($this->magicQuotesActive) {
                $value = stripslashes($value);
            }
            $value = $this->_service->real_escape_string($value);
        } else {
            if (!$this->magicQuotesActive) {
                $value = addslashes($value);
            }
        }

        return $value;
    }

    /**
     * Returns last inserted id
     * 
     * @return integer
     * @throws Exception\Service
     */
    public function getLastInsertId()
    {
        if (!$this->_isValidService()) {
            throw new Exception\Service('Not connected to a valid database service');
        }

        return $this->_service->insert_id;
    }

    /**
     * Returns count of affected rows by last query
     * 
     * @return integer
     * @throws Exception\Service
     */
    public function getAffectedRows()
    {
        if (!$this->_isValidService()) {
            throw new Exception\Service('Not connected to a valid database service');
        }

        return $this->_service->affected_rows;
    }

    /**
     * Return last error
     * 
     * @return string
     * @throws Exception\Service
     */
    public function getLastError()
    {
        if (!$this->_isValidService()) {
            throw new Exception\Service('Not connected to a valid database service');
        }

        return $this->_service->error;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        $this->_service->autocommit(FALSE);
    }

    /**
     * Commit transaction
     */
    public function commitTransaction()
    {
        $this->_service->commit();
        $this->_service->autocommit(TRUE);
    }

    /**
     * Rollback transaction
     */
    public function rollbackTransaction()
    {
        $this->_service->rollback();
        $this->_service->autocommit(TRUE);
    }

    /**
     * Method converts the class/properties into a valid SQL query, and 
     * ultimately into a physical database table. It does this by first 
     * getting a list of the columns, by calling the model’s getColumns() method. 
     * Looping over the columns, it creates arrays of indices and field strings.
     * After all the field strings have been created, they are joined (along with the indices), 
     * and applied to the CREATE TABLE $template string.
     * 
     * @param Model $model
     * @return \THCFrame\Database\Connector\Mysql
     * @throws Exception\Sql
     */
    public function sync(Model $model)
    {
        $lines = array();
        $indices = array();
        $columns = $model->columns;
        $template = 'CREATE TABLE `%s` (\n%s,\n%s\n) ENGINE=%s DEFAULT CHARSET=%s;';

        foreach ($columns as $column) {
            $raw = $column['raw'];
            $name = $column['name'];
            $type = $column['type'];
            $length = $column['length'];

            if ($column['primary']) {
                $indices[] = "PRIMARY KEY (`{$name}`)";
            }
            if ($column['index']) {
                $indices[] = "KEY `ix_{$name}` (`{$name}`)";
            }
            if ($column['unique']) {
                $indices[] = "UNIQUE KEY (`{$name}`)";
            }

            switch ($type) {
                case 'auto_increment': {
                        $lines[] = "`{$name}` int(11) UNSIGNED NOT NULL AUTO_INCREMENT";
                        break;
                    }
                case 'text': {
                        if ($length !== null && $length <= 255) {
                            $lines[] = "`{$name}` varchar({$length}) NOT NULL DEFAULT ''";
                        } else {
                            $lines[] = "`{$name}` text";
                        }
                        break;
                    }
                case 'integer': {
                        $lines[] = "`{$name}` int(11) NOT NULL DEFAULT 0";
                        break;
                    }
                case 'tinyint': {
                        $lines[] = "`{$name}` tinyint(4) NOT NULL DEFAULT 0";
                        break;
                    }
                case 'decimal': {
                        $lines[] = "`{$name}` float NOT NULL DEFAULT 0.0";
                        break;
                    }
                case 'boolean': {
                        $lines[] = "`{$name}` tinyint(4) NOT NULL DEFAULT 0";
                        break;
                    }
                case 'datetime': {
                        $lines[] = "`{$name}` datetime DEFAULT NULL";
                        break;
                    }
            }
        }

        $table = $model->table;
        $sql = sprintf(
                $template, $table, join(',\n', $lines), join(',\n', $indices), $this->_engine, $this->_charset
        );

        $result = $this->execute("DROP TABLE IF EXISTS {$table};");
        if ($result === false) {
            $this->_logError($this->_service->error, $sql);
            
            if (ENV == 'dev') {
                $error = $this->getLastError();
                throw new Exception\Sql(sprintf('There was an error in the query: %s', $error));
            } else {
                throw new Exception\Sql(sprintf('There was an error in the query'));
            }
        }

        $result2 = $this->execute($sql);
        if ($result2 === false) {
            $this->_logError($this->_service->error, $sql);
            
            if (ENV == 'dev') {
                $error = $this->getLastError();
                throw new Exception\Sql(sprintf('There was an error in the query: %s', $error));
            } else {
                throw new Exception\Sql(sprintf('There was an error in the query'));
            }
        }

        return $this;
    }

}
