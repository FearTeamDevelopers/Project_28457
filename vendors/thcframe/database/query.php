<?php

namespace THCFrame\Database;

use THCFrame\Core\Base;
use THCFrame\Core\ArrayMethods;
use THCFrame\Core\StringMethods;
use THCFrame\Database\Exception as Exception;
use THCFrame\Core\Core;

/**
 * Query class for OO query creating
 */
class Query extends Base
{

    /**
     * @readwrite
     */
    protected $_connector;

    /**
     * @read
     */
    protected $_from;

    /**
     * @read
     */
    protected $_alias;

    /**
     * @read
     */
    protected $_fields;

    /**
     * @read
     */
    protected $_limit;

    /**
     * @read
     */
    protected $_offset;

    /**
     * @read
     */
    protected $_order = array();

    /**
     * @read
     */
    protected $_groupby;

    /**
     * @read
     */
    protected $_join = array();

    /**
     * @read
     */
    protected $_where = array();

    /**
     * @read
     */
    protected $_wheresql;

    /**
     * @read
     */
    protected $_having = array();

    /**
     * 
     * @param type $method
     * @return \THCFrame\Database\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
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
     * 
     * @param type $value
     * @return string
     */
    protected function _quote($value)
    {
        $connector = $this->getConnector();

        if (is_string($value)) {
            $escaped = $connector->escape($value);
            return "'{$escaped}'";
        }

        if (is_array($value)) {
            $buffer = array();

            foreach ($value as $i) {
                array_push($buffer, $this->_quote($i));
            }

            $buffer = join(', ', $buffer);
            return "({$buffer})";
        }

        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return (int) $value;
        }

        return $connector->escape($value);
    }

    /**
     * 
     * @return type
     */
    protected function _buildSelect()
    {
        $fields = array();
        $where = $order = $limit = $join = '';
        $template = 'SELECT %s FROM %s %s %s %s %s %s %s %s';

        foreach ($this->fields as $table => $_fields) {
            foreach ($_fields as $field => $alias) {
                if (is_string($field)) {
                    $fields[] = "{$field} AS {$alias}";
                } else {
                    $fields[] = $alias;
                }
            }
        }

        $joinedFields = join(', ', $fields);

        $_join = $this->join;
        if (!empty($_join)) {
            $join = join(' ', $_join);
        }

        $whereConditions = '';
        if (!empty($this->_where)) {
            $whereConditions = join(' AND ', $this->_where);
        } elseif ($this->_wheresql != '') {
            $whereConditions = $this->_wheresql;
        }

        if ($whereConditions != '') {
            $where = "WHERE {$whereConditions}";
        }

        $_groupBy = $this->groupby;
        $groupBy = $having = '';
        if (!empty($_groupBy)) {
            $groupBy = "GROUP BY {$_groupBy}";

            $_having = $this->having;
            if (!empty($_having)) {
                $joinedHaving = join(' AND ', $_having);
                $having = "HAVING {$joinedHaving}";
            }
        }

        $_order = $this->order;
        if (!empty($_order)) {
            $joindeOrder = join(', ', $_order);
            $order = "ORDER BY {$joindeOrder}";
        }

        $_limit = $this->limit;
        if (!empty($_limit)) {
            $_offset = $this->offset;

            if ($_offset) {
                $limit = "LIMIT {$_offset}, {$_limit}";
            } else {
                $limit = "LIMIT {$_limit}";
            }
        }

        $input = sprintf($template, $joinedFields, $this->from, $this->alias, $join, $where, $groupBy, $having, $order, $limit);
        $output = preg_replace('/\s+/', ' ', $input);
        return $output;
    }

    /**
     * 
     * @param type $data
     * @return type
     */
    protected function _buildInsert($data)
    {
        $fields = array();
        $values = array();
        $template = 'INSERT INTO `%s` (`%s`) VALUES (%s)';

        foreach ($data as $field => $value) {
            $fields[] = $field;
            $values[] = $this->_quote($value);
        }

        $fields = join('`, `', $fields);
        $values = join(', ', $values);

        $input = sprintf($template, $this->from, $fields, $values);
        $output = preg_replace('/\s+/', ' ', $input);
        return $output;
    }

    /**
     * 
     * @param type $data
     * @return type
     */
    protected function _buildUpdate($data)
    {
        $parts = array();
        $where = $limit = '';
        $template = 'UPDATE %s SET %s %s %s';

        foreach ($data as $field => $value) {
            $parts[] = "{$field} = " . $this->_quote($value);
        }

        $parts = join(', ', $parts);

        $whereConditions = '';
        if (!empty($this->_where)) {
            $whereConditions = join(' AND ', $this->_where);
        } elseif ($this->_wheresql != '') {
            $whereConditions = $this->_wheresql;
        }

        if ($whereConditions != '') {
            $where = "WHERE {$whereConditions}";
        }

        $_limit = $this->limit;
        if (!empty($_limit)) {
            $_offset = $this->offset;
            $limit = "LIMIT {$_limit} {$_offset}";
        }

        $input = sprintf($template, $this->from, $parts, $where, $limit);
        $output = preg_replace('/\s+/', ' ', $input);
        return $output;
    }

    /**
     * 
     * @return type
     */
    protected function _buildDelete()
    {
        $where = $limit = '';
        $template = 'DELETE FROM %s %s %s';

        $whereConditions = '';
        if (!empty($this->_where)) {
            $whereConditions = join(' AND ', $this->_where);
        } elseif ($this->_wheresql != '') {
            $whereConditions = $this->_wheresql;
        }

        if ($whereConditions != '') {
            $where = "WHERE {$whereConditions}";
        }

        $_limit = $this->limit;
        if (!empty($_limit)) {
            $_offset = $this->offset;
            $limit = "LIMIT {$_limit} {$_offset}";
        }

        $input = sprintf($template, $this->from, $where, $limit);
        $output = preg_replace('/\s+/', ' ', $input);
        return $output;
    }

    /**
     * 
     * @return type
     * @throws Exception\Connector
     */
    public function getConnector()
    {
        if (empty($this->_connector)) {
            $database = Registry::get('database');

            if (!$database) {
                throw new Exception\Connector('No connector availible');
            }

            $this->_connector = $database->initialize();
        }

        return $this->_connector;
    }

    /**
     * 
     * @return type
     */
    public function getTableAlias()
    {
        return $this->_alias;
    }

    /**
     * 
     * @param type $alias
     */
    public function setTableAlias($alias)
    {
        if (StringMethods::match($alias, '#^([a-z_-]*)$#')) {
            $this->_alias = $alias;
        } else {
            throw new Exception('Table alias is not valid alias');
        }

        return $this;
    }

    /**
     * 
     * @param type $alias
     * @return \THCFrame\Database\Query
     */
    public function setAlias($alias)
    {
        $this->setTableAlias($alias);

        return $this;
    }

    /**
     * 
     * @param type $data
     * @return int
     * @throws Exception\Sql
     */
    public function save($data)
    {
        $isInsert = count($this->_where) == 0;

        if ($isInsert) {
            $sql = $this->_buildInsert($data);
        } else {
            $sql = $this->_buildUpdate($data);
        }

        $result = $this->connector->execute($sql);

        if ($result === false) {
            $err = $this->connector->getLastError();
            $this->_logError($err, $sql);
            
            if (ENV == 'dev') {
                throw new Exception\Sql(sprintf('There was an error with your SQL query: %s', $err));
            } else {
                throw new Exception\Sql('There was an error');
            }
        }

        if ($isInsert) {
            return $this->connector->getLastInsertId();
        }

        return 0;
    }

    /**
     * 
     * @return type
     * @throws Exception\Sql
     */
    public function delete()
    {
        $sql = $this->_buildDelete();
        $result = $this->connector->execute($sql);

        if ($result === false) {
            $err = $this->connector->getLastError();
            $this->_logError($err, $sql);
            
            if (ENV == 'dev') {
                throw new Exception\Sql(sprintf('There was an error with your SQL query: %s', $err));
            } else {
                throw new Exception\Sql('There was an error');
            }
        }

        return $this->connector->getAffectedRows();
    }

    /**
     * 
     * @return type
     * @throws Exception\Sql
     */
    public function update($data)
    {
        $sql = $this->_buildUpdate($data);
        $result = $this->connector->execute($sql);

        if ($result === false) {
            $err = $this->connector->getLastError();
            $this->_logError($err, $sql);
            
            if (ENV == 'dev') {
                throw new Exception\Sql(sprintf('There was an error with your SQL query: %s', $err));
            } else {
                throw new Exception\Sql('There was an error');
            }
        }

        return $this->connector->getAffectedRows();
    }

    /**
     * 
     * @param type $from
     * @param type $fields
     * @return \THCFrame\Database\Query
     * @throws Exception\Argument
     */
    public function from($from, $fields = array('*'))
    {
        if (empty($from)) {
            throw new Exception\Argument('Invalid argument');
        }

        $this->_from = $from;

        if ($fields) {
            $this->_fields[$from] = $fields;
        }

        return $this;
    }

    /**
     * 
     * @param type $join
     * @param type $on
     * @param type $fields
     * @return \THCFrame\Database\Query
     * @throws Exception\Argument
     */
    public function join($join, $on, $alias = null, $fields = array('*'))
    {
        if (empty($join) || empty($on)) {
            throw new Exception\Argument('Invalid argument');
        }

        if (NULL !== $alias) {
            $this->_fields += array($alias => $fields);
            $this->_join[] = "JOIN {$join} {$alias} ON {$on}";
        } else {
            $this->_fields += array($join => $fields);
            $this->_join[] = "JOIN {$join} ON {$on}";
        }

        return $this;
    }

    /**
     * 
     * @param type $join
     * @param type $on
     * @param type $fields
     * @return \THCFrame\Database\Query
     * @throws Exception\Argument
     */
    public function leftjoin($join, $on, $alias = null, $fields = array('*'))
    {
        if (empty($join) || empty($on)) {
            throw new Exception\Argument('Invalid argument');
        }

        if (NULL !== $alias) {
            $this->_fields += array($alias => $fields);
            $this->_join[] = "LEFT JOIN {$join} {$alias} ON {$on}";
        } else {
            $this->_fields += array($join => $fields);
            $this->_join[] = "LEFT JOIN {$join} ON {$on}";
        }

        return $this;
    }

    /**
     * 
     * @param type $join
     * @param type $on
     * @param type $fields
     * @return \THCFrame\Database\Query
     * @throws Exception\Argument
     */
    public function rightjoin($join, $on, $alias = null, $fields = array('*'))
    {
        if (empty($join) || empty($on)) {
            throw new Exception\Argument('Invalid argument');
        }

        if (NULL !== $alias) {
            $this->_fields += array($alias => $fields);
            $this->_join[] = "RIGHT JOIN {$join} {$alias} ON {$on}";
        } else {
            $this->_fields += array($join => $fields);
            $this->_join[] = "RIGHT JOIN {$join} ON {$on}";
        }

        return $this;
    }

    /**
     * 
     * @param type $limit
     * @param type $page
     * @return \THCFrame\Database\Query
     * @throws Exception\Argument
     */
    public function limit($limit, $page = 1)
    {
        if (empty($limit)) {
            throw new Exception\Argument('Invalid argument');
        }

        $this->_limit = $this->_quote($limit);
        $page = (int) $this->_quote($page);

        if ($page - 1 <= 0) {
            $this->_offset = 0;
        } else {
            $this->_offset = $limit * ($page - 1);
        }

        return $this;
    }

    /**
     * 
     * @param type $order
     * @param type $direction
     * @return \THCFrame\Database\Query
     * @throws Exception\Argument
     */
    public function order($order, $direction = 'asc')
    {
        if (empty($order)) {
            throw new Exception\Argument('Invalid argument');
        }

        $this->_order[] = $order . ' ' . $direction;

        return $this;
    }

    /**
     * 
     * @return \THCFrame\Database\Query
     * @throws Exception\Argument
     */
    public function where()
    {
        if ($this->_wheresql != '') {
            throw new Exception\Sql('You can use only one of the where methods');
        }

        $arguments = func_get_args();

        if (count($arguments) < 1) {
            throw new Exception\Argument('Invalid argument');
        }

        $arguments[0] = preg_replace('#\?#', '%s', $arguments[0]);

        foreach (array_slice($arguments, 1, null, true) as $i => $parameter) {
            $arguments[$i] = $this->_quote($arguments[$i]);
        }

        $this->_where[] = call_user_func_array('sprintf', $arguments);

        return $this;
    }

    /**
     * 
     * @return \THCFrame\Database\Query
     * @throws Exception\Sql
     */
    public function wheresql()
    {
        if (!empty($this->_where)) {
            throw new Exception\Sql('You can use only one of the where methods');
        }

        $arguments = func_get_args();

        if (count($arguments) < 1) {
            throw new Exception\Argument('Invalid argument');
        }

        $connector = $this->getConnector();
        $arguments[0] = preg_replace('#\?#', '%s', $arguments[0]);

        foreach (array_slice($arguments, 1, null, true) as $i => $parameter) {
            $arguments[$i] = $connector->escape($arguments[$i]);
        }

        $this->_wheresql = call_user_func_array('sprintf', $arguments);

        return $this;
    }

    /**
     * 
     * @return \THCFrame\Database\Query
     * @throws Exception\Argument
     */
    public function having()
    {
        $arguments = func_get_args();

        if (count($arguments) < 1) {
            throw new Exception\Argument('Invalid argument');
        }

        $arguments[0] = preg_replace('#\?#', '%s', $arguments[0]);

        foreach (array_slice($arguments, 1, null, true) as $i => $parameter) {
            $arguments[$i] = $this->_quote($arguments[$i]);
        }

        $this->_having[] = call_user_func_array('sprintf', $arguments);

        return $this;
    }

    /**
     * 
     * @param type $field
     * @return \THCFrame\Database\Query
     * @throws Exception\Argument
     */
    public function groupby($field)
    {
        if (empty($field)) {
            throw new Exception\Argument('Invalid argument');
        }

        $this->_groupby = $field;

        return $this;
    }

    /**
     * 
     * @return type
     */
    public function first()
    {
        $limit = $this->_limit;
        $offset = $this->_offset;

        $this->limit(1);

        $all = $this->all();
        $first = ArrayMethods::first($all);

        if ($limit) {
            $this->_limit = $limit;
        }
        if ($offset) {
            $this->_offset = $offset;
        }

        return $first;
    }

    /**
     * 
     * @return type
     */
    public function count()
    {
        $limit = $this->limit;
        $offset = $this->offset;
        $fields = $this->fields;

        $this->_fields = array($this->from => array('COUNT(1)' => 'rows'));

        $this->limit(1);
        $row = $this->first();

        $this->_fields = $fields;

        if ($fields) {
            $this->_fields = $fields;
        }
        if ($limit) {
            $this->_limit = $limit;
        }
        if ($offset) {
            $this->_offset = $offset;
        }

        return $row['rows'];
    }

    /**
     * 
     * @return type
     */
    public function assemble()
    {
        $sql = $this->_buildSelect();
        return (string) $sql;
    }

}
