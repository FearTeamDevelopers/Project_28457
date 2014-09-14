<?php

namespace THCFrame\Model;

use THCFrame\Core\Base;
use THCFrame\Registry\Registry;
use THCFrame\Core\Inspector;
use THCFrame\Core\StringMethods;
use THCFrame\Model\Exception;

/**
 * This class allow us to isolate all the direct database communication, 
 * and most communication with third-party web services. Models can connect to any
 * number of third-party data services, and provide a simple interface for use in our controllers.
 * 
 * An ORM library creates an opaque communication layer between two data-related systems
 *
 * @author Tomy
 */
class Model extends Base
{

    /**
     * @readwrite
     */
    protected $_table;

    /**
     * @readwrite
     */
    protected $_alias = '';

    /**
     * @readwrite
     */
    protected $_connector;

    /**
     * @read
     */
    protected $_types = array(
        'auto_increment',
        'text',
        'integer',
        'tinyint',
        'decimal',
        'boolean',
        'datetime',
    );

    /**
     * @read
     */
    protected $_validators = array(
        'required' => array(
            'handler' => '_validateRequired',
            'message' => 'The {0} field is required'
        ),
        'alpha' => array(
            'handler' => '_validateAlpha',
            'message' => 'The {0} field can only contain letters'
        ),
        'numeric' => array(
            'handler' => '_validateNumeric',
            'message' => 'The {0} field can only contain numbers'
        ),
        'alphanumeric' => array(
            'handler' => '_validateAlphaNumeric',
            'message' => 'The {0} field can only contain letters and numbers'
        ),
        'max' => array(
            'handler' => '_validateMax',
            'message' => 'The {0} field must contain less than {2} characters'
        ),
        'min' => array(
            'handler' => '_validateMin',
            'message' => 'The {0} field must contain more than {2} characters'
        ),
        'email' => array(
            'handler' => '_validateEmail',
            'message' => 'The {0} field must contain valid email address'
        ),
        'url' => array(
            'handler' => '_validateUrl',
            'message' => 'The {0} field must contain valid url'
        ),
        'datetime' => array(
            'handler' => '_validateDatetime',
            'message' => 'The {0} field must contain valid date and time (yyyy-mm-dd hh:mm)'
        ),
        'date' => array(
            'handler' => '_validateDate',
            'message' => 'The {0} field must contain valid date (yyyy-mm-dd)'
        ),
        'time' => array(
            'handler' => '_validateTime',
            'message' => 'The {0} field must contain valid time (hh:mm / hh:mm:ss)'
        ),
        'html' => array(
            'handler' => '_validateHtml',
            'message' => 'The {0} field can contain these tags only (span,strong,em,s,p,div,a,ol,ul,li,img,table,caption,thead,tbody,tr,td)'
        ),
        'path' => array(
            'handler' => '_validatePath',
            'message' => 'The {0} field must contain filesystem path'
        )
    );

    /**
     * @read
     */
    protected $_errors = array();
    protected $_columns;
    protected $_primary;

    /**
     * 
     * @param type $method
     * @return \THCFrame\Model\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function _validateRequired($value)
    {
        return !empty($value) && $value !== 0;
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function _validateAlpha($value)
    {
        if ($value == '') {
            return true;
        } else {
            $pattern = preg_quote('#$%^&*()+=-[]\',./|\":?~_', '#');
            return StringMethods::match($value, "#([a-zá-žA-ZÁ-Ž{$pattern}]+)#");
        }
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function _validateNumeric($value)
    {
        if ($value == '') {
            return true;
        } else {
            $pattern = preg_quote('%^*()+=-,./:', '#');
            return StringMethods::match($value, "#([a-zá-žA-ZÁ-Ž0-9{$pattern}]+)#");
        }
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function _validateAlphaNumeric($value)
    {
        if ($value == '') {
            return true;
        } else {
            $pattern = preg_quote('#$%^&*()+=-[]\',./|\":?~_', '#');
            return StringMethods::match($value, "#([a-zá-žA-ZÁ-Ž0-9{$pattern}]+)#");
        }
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function _validateHtml($value)
    {
        if ($value == '') {
            return true;
        } else {
            $pattern = preg_quote('#$%^&*()+=-[]\',./|\":?~_', '#');
            return StringMethods::match($value, '#((<|&lt;)(strong|em|s|p|div|a|img|table|tr|td|thead|tbody|ol|li|ul|caption|span)(>|&gt;)'
                            . "[a-zá-žA-ZÁ-Ž0-9{$pattern}]+(<|&lt;)/\2(>|&gt;))*"
                            . "[a-zá-žA-ZÁ-Ž0-9{$pattern}]+#");
        }
    }

    /**
     * 
     * @param type $value
     * @return boolean
     */
    protected function _validatePath($value)
    {
        if ($value == '') {
            return true;
        } else {
            $pattern = preg_quote('()-./:_', '#');
            return StringMethods::match($value, "#^([a-zA-Z0-9{$pattern}]+)$#");
        }
    }

    /**
     * 
     * @param type $value
     * @param type $number
     * @return type
     */
    protected function _validateMax($value, $number)
    {
        return strlen($value) <= (int) $number;
    }

    /**
     * 
     * @param type $value
     * @param type $number
     * @return type
     */
    protected function _validateMin($value, $number)
    {
        return strlen($value) >= (int) $number;
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function _validateEmail($value)
    {
        if ($value == '') {
            return true;
        } else {
            return filter_var($value, FILTER_VALIDATE_EMAIL);
        }
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function _validateUrl($value)
    {
        if ($value == '') {
            return true;
        } else {
            return filter_var($value, FILTER_VALIDATE_URL);
        }
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function _validateDatetime($value)
    {
        if($value == ''){
            return true;
        }
        
        list($date, $time) = explode(' ', $value);

        $validDate = $this->_validateDate($date);
        $validTime = $this->_validateTime($time);

        if ($validDate && $validTime) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     * @param type $value
     * @return boolean 
     */
    protected function _validateDate($value)
    {
        if($value == ''){
            return true;
        }
        
        $config = Registry::get('configuration');
        $format = $config->system->dateformat;

        if (strlen($value) >= 6 && strlen($format) == 10) {

            $separator_only = str_replace(array('m', 'd', 'y'), '', $format);
            $separator = $separator_only[0]; // separator is first character 

            if ($separator && strlen($separator_only) == 2) {
                $regexp = str_replace('mm', '(0?[1-9]|1[0-2])', $format);
                $regexp = str_replace('dd', '(0?[1-9]|[1-2][0-9]|3[0-1])', $regexp);
                $regexp = str_replace('yyyy', '(19|20)?[0-9][0-9]', $regexp);
                //$regexp = str_replace($separator, "\\" . $separator, $regexp);

                if ($regexp != $value && preg_match('/' . $regexp . '\z/', $value)) {
                    $arr = explode($separator, $value);
                    $day = $arr[2];
                    $month = $arr[1];
                    $year = $arr[0];

                    if (@checkdate($month, $day, $year)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function _validateTime($value)
    {
        if($value == ''){
            return true;
        }
        
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $value);
    }

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
        //$this->load();
    }

    /**
     * Method simplifies record retrieval for us. 
     * It determines the model’s primary column and checks to see whether 
     * it is not empty. This tells us whether the primary key has been provided, 
     * which gives us aviable means of finding the intended record. 
     * 
     * If the primary key class property is empty, we assume this model instance 
     * is intended for the creation of a new record, and do nothing further. 
     * To load the database record, we get the current model’s connector, 
     * which halts execution if none is found. We create a database query 
     * for the record, based on the primary key column property’s value. 
     * 
     * If no record is found, the Model\Exception\Primary exception is raised. 
     * This happens when a primary key column value is provided, 
     * but does not represent a valid identifier for a record in the database table.
     * 
     * Finally we loop over the loaded record’s data and only set property 
     * values that were not set in the __construct() method.
     * 
     * @throws Exception\Primary
     */
    public function load()
    {
        $primary = $this->primaryColumn;

        $raw = $primary['raw'];
        $name = $primary['name'];

        if (!empty($this->$raw)) {
            $previous = $this->connector
                    ->query()
                    ->from($this->table)
                    ->setTableAlias($this->alias)
                    ->where("{$name} = ?", $this->$raw)
                    ->first();

            if ($previous == null) {
                throw new Exception\Primary('Primary key value invalid');
            }

            foreach ($previous as $key => $value) {
                $prop = "_{$key}";
                if (!empty($previous->$key) && !isset($this->$prop)) {
                    $this->$key = $previous->$key;
                }
            }
        }
    }

    /**
     * Method removes records from the database
     * 
     * @return type
     */
    public function delete()
    {
        $primary = $this->primaryColumn;

        $raw = $primary['raw'];
        $name = $primary['name'];

        if (!empty($this->$raw)) {
            $this->connector->beginTransaction();

            $state = $this->connector
                    ->query()
                    ->from($this->table)
                    ->where("{$name} = ?", $this->$raw)
                    ->delete();

            if ($state != -1) {
                $this->connector->commitTransaction();
                return $state;
            } else {
                $this->connector->rollbackTransaction();
                return $state;
            }
        }
    }

    /**
     * Method removes records from the database
     * 
     * @param type $where
     * @return type
     */
    public static function deleteAll($where = array())
    {
        $instance = new static();

        $query = $instance->connector
                ->query()
                ->from($instance->table);

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        $instance->connector->beginTransaction();

        $state = $query->delete();

        if ($state != -1) {
            $instance->connector->commitTransaction();
            return $state;
        } else {
            $instance->connector->rollbackTransaction();
            return $state;
        }
    }

    /**
     * 
     */
    public function preSave()
    {
        
    }

    /**
     * Method creates a query instance, and targets the table related to the Model class. 
     * It applies a WHERE clause if the primary key property value is not empty, 
     * and builds a data array based on columns returned by the getColumns() method. 
     * 
     * Finally, it calls the query instance’s save()method to commit the 
     * data to the database. Since the Database\Connector class executes 
     * either an INSERT or UPDATE statement, based on the WHERE clause criteria, 
     * this method will either insert a new record, or update an existing record, 
     * depending on whether the primary key property has a value or not.
     * 
     * @return type
     */
    public function save()
    {
        $this->preSave();

        $primary = $this->primaryColumn;

        $raw = $primary['raw'];
        $name = $primary['name'];

        $query = $this->connector
                ->query()
                ->from($this->table);

        if (!empty($this->$raw)) {
            $query->where("{$name} = ?", $this->$raw);
        }

        $data = array();
        foreach ($this->columns as $key => $column) {
            if (!$column['read']) {
                $prop = $column['raw'];
                $data[$key] = $this->$prop;
                continue;
            }

            if ($column != $this->primaryColumn && $column) {
                $method = 'get' . ucfirst($key);
                $data[$key] = $this->$method();
                continue;
            }
        }

        $result = $query->save($data);

        if ($result > 0) {
            $this->$raw = $result;
        }

        $this->postSave();

        return $result;
    }

    /**
     * 
     */
    public function postSave()
    {
        
    }

    /**
     * Method returns a user-defined table name based on the current 
     * Model’s class name (using PHP’s get_class() method
     * 
     * @return type
     */
    public function getTable()
    {
        if (empty($this->_table)) {
            $tablePrefix = Registry::get('configuration')->database->tablePrefix;

            if (strpos(get_class($this), '_') !== false) {
                list($module, $type, $name) = explode('_', get_class($this));
                
                if (strtolower($type) == 'model' && !empty($name)) {
                    $this->_table = strtolower($tablePrefix . $name);
                } else {
                    throw new Exception\Implementation('Model is not valid THCFrame\Model\Model');
                }
            } elseif (preg_match('#^thcframe(.*)model(.*)$#i', get_class($this))) {
                $parts = array_reverse(explode('\\', get_class($this)));
                $this->_table = strtolower($tablePrefix . $parts[0]);
            } else {
                throw new Exception\Implementation('Model is not valid THCFrame\Model\Model');
            }
        }

        return $this->_table;
    }

    /**
     * Method so that we can return the contents of the $_connector property,
     * a connector instance stored in the Registry class, or raise a Model\Exception\Connector
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
     * Method creates an Inspector instance and a utility function ($first) to return the
     * first item in a metadata array. Next, it loops through all the properties in the model, 
     * and sifts out all that have an @column flag. Any other properties are ignored at this point.
     * The column’s @type flag is checked to make sure it is valid, raising a 
     * Model\Exception\Type in the event that it is not. If the column’s type is valid, 
     * it is added to the $_columns property. Every valid $primary column leads to the 
     * incrementing of the $primaries variable, which is checked at the end 
     * of the method to make sure that exactly one primary column has been defined. 
     * In essence, this method takes the User model definition and returns an associative array of column data
     * 
     * @return array
     * @throws Exception\Type
     * @throws Exception\Primary
     */
    public function getColumns()
    {
        if (empty($this->_columns)) {
            $primaries = 0;
            $columns = array();
            $class = get_class($this);
            $types = $this->_types;

            $inspector = new Inspector($this);
            $properties = $inspector->getClassProperties();

            $first = function($array, $key) {
                if (!empty($array[$key]) && count($array[$key]) == 1) {
                    return $array[$key][0];
                }
                return null;
            };

            foreach ($properties as $property) {
                $propertyMeta = $inspector->getPropertyMeta($property);

                if (!empty($propertyMeta['@column'])) {
                    $name = preg_replace('#^_#', '', $property);
                    $primary = !empty($propertyMeta['@primary']);
                    $type = $first($propertyMeta, '@type');
                    $length = $first($propertyMeta, '@length');
                    $index = !empty($propertyMeta['@index']);
                    $unique = !empty($propertyMeta['@unique']);
                    $readwrite = !empty($propertyMeta['@readwrite']);
                    $read = !empty($propertyMeta['@read']) || $readwrite;
                    $write = !empty($propertyMeta['@write']) || $readwrite;

                    $validate = !empty($propertyMeta['@validate']) ? $propertyMeta['@validate'] : false;
                    $label = $first($propertyMeta, '@label');

                    if (!in_array($type, $types)) {
                        throw new Exception\Type(sprintf('%s is not a valid type', $type));
                    }

                    if ($primary) {
                        $primaries++;
                    }

                    $columns[$name] = array(
                        'raw' => $property,
                        'name' => $name,
                        'primary' => $primary,
                        'type' => $type,
                        'length' => $length,
                        'index' => $index,
                        'unique' => $unique,
                        'read' => $read,
                        'write' => $write,
                        'validate' => $validate,
                        'label' => $label
                    );
                }
            }

            if ($primaries !== 1) {
                throw new Exception\Primary(sprintf('%s must have exactly one @primary column', $primary));
            }

            $this->_columns = $columns;
        }

        return $this->_columns;
    }

    /**
     * Method returns a column by name. Class properties are assumed to begin 
     * with an underscore (_) character. This assumption is continued by the 
     * getColumn() method, which checks for a column without the _ character. 
     * When declared as a column property, columns will look like _firstName, 
     * but referenced by any public getters/setters/methods, 
     * they will look like setFirstName/firstName.
     * 
     * @param type $name
     * @return null
     */
    public function getColumn($name)
    {
        if (!empty($this->_columns[$name])) {
            return $this->_columns[$name];
        }
        return null;
    }

    /**
     * Method loops through the columns, returning the one marked as primary
     * 
     * @return type
     */
    public function getPrimaryColumn()
    {
        if (!isset($this->_primary)) {
            $primary;

            foreach ($this->columns as $column) {
                if ($column['primary']) {
                    $primary = $column;
                    break;
                }
            }

            $this->_primary = $primary;
        }

        return $this->_primary;
    }

    /**
     * Method is a simple, static wrapper method for the protected _first() method
     * 
     * @param type $where
     * @param type $fields
     * @param type $order
     * @param type $direction
     * @return type
     */
    public static function first($where = array(), $fields = array('*'), $order = array())
    {
        $model = new static();
        return $model->_first($where, $fields, $order);
    }

    /**
     * Method returns the first matched record
     * 
     * @param type $where
     * @param type $fields
     * @param type $order
     * @param type $direction
     * @return \THCFrame\class|null
     */
    protected function _first($where = array(), $fields = array('*'), $order = array())
    {
        $query = $this->connector
                ->query()
                ->from($this->table, $fields)
                ->setTableAlias($this->alias);

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        if (!empty($order)) {
            foreach ($order as $filed => $direction) {
                $query->order($filed, $direction);
            }
        }

        $first = $query->first();
        $class = get_class($this);

        if ($first) {
            return new $class($first);
        }

        return null;
    }

    /**
     * Method is a simple, static wrapper method for the protected _all() method
     * 
     * @param type $where
     * @param type $fields
     * @param type $order
     * @param type $direction
     * @param type $limit
     * @param type $page
     * @return type
     */
    public static function all($where = array(), $fields = array('*'), $order = array(), $limit = null, $page = null, $group = null, $having = array())
    {
        $model = new static();
        return $model->_all($where, $fields, $order, $limit, $page, $group, $having);
    }

    /**
     * Method creates a query, taking into account the various filters and flags, 
     * to return all matching records. The reason we go to the trouble of 
     * wrapping an instance method within a static method is because we have 
     * created a context wherein a model instance is equal to a database record. 
     * Multirecord operations make more sense as class methods, in this context.
     * 
     * @param type $where
     * @param type $fields
     * @param type $order
     * @param type $direction
     * @param type $limit
     * @param type $page
     * @return \THCFrame\class
     */
    protected function _all($where = array(), $fields = array('*'), $order = array(), $limit = null, $page = null, $group = null, $having = array())
    {
        $query = $this->connector
                ->query()
                ->from($this->table, $fields)
                ->setTableAlias($this->alias);

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        if ($group != null) {
            $query->groupby($group);

            if (!empty($having)) {
                foreach ($having as $clause => $value) {
                    $query->having($clause, $value);
                }
            }
        }

        if (!empty($order)) {
            foreach ($order as $filed => $direction) {
                $query->order($filed, $direction);
            }
        }

        if ($limit != null) {
            $query->limit($limit, $page);
        }

        $rows = array();
        $class = get_class($this);

        foreach ($query->all() as $row) {
            $rows[] = new $class($row);
        }

        return $rows;
    }

    /**
     * Method is a simple, static wrapper method for the protected _getQuery() method
     * 
     * @return type
     */
    public static function getQuery($fields)
    {
        $model = new static();
        return $model->_getQuery($fields);
    }

    /**
     * Method return new query instance for current model
     * 
     * @return type
     */
    protected function _getQuery($fields)
    {
        return $this->connector
                        ->query()
                        ->from($this->table, $fields)
                        ->setTableAlias($this->alias);
    }

    /**
     * 
     * @param \THCFrame\Database\Query $query
     * @return \THCFrame\Model\class
     */
    public static function initialize(\THCFrame\Database\Query $query)
    {
        $model = new static();
        $rows = array();
        $class = get_class($model);

        foreach ($query->all() as $row) {
            $rows[] = new $class($row);
        }

        if (empty($rows)) {
            return null;
        } else {
            return $rows;
        }
    }

    /**
     * Method is a simple, static wrapper method for the protected _count() method
     * 
     * @param type $where
     * @return type
     */
    public static function count($where = array())
    {
        $model = new static();
        return $model->_count($where);
    }
    
    /**
     * Method returns a count of the matched records
     * 
     * @param type $where
     * @return type
     */
    protected function _count($where = array())
    {
        $query = $this
                ->connector
                ->query()
                ->from($this->table);

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        return $query->count();
    }

    /**
     * Method begins by getting a list of columns and iterating over that list. 
     * For each column, we determine whether validation should occur. 
     * We then split the @validate metadata into a list of validation conditions. 
     * If a condition has arguments (e.g., max(100)), we extract the arguments. 
     * We then run each validation method on the column data and generate error 
     * messages for those validation conditions that failed. 
     * We return a final true/false to indicate whether the complete validation passed or failed.
     * 
     * @return type
     * @throws Exception\Validation
     */
    public function validate()
    {
        $this->_errors = array();

        foreach ($this->columns as $column) {
            if ($column['validate']) {
                $pattern = '#[a-z]+\(([a-zA-Z0-9, ]+)\)#';

                $raw = $column['raw'];
                $name = $column['name'];
                $validators = $column['validate'];
                $label = $column['label'];

                $defined = $this->getValidators();

                foreach ($validators as $validator) {
                    $function = $validator;
                    $arguments = array(
                        $this->$raw
                    );

                    $match = StringMethods::match($validator, $pattern);

                    if (count($match) > 0) {
                        $matches = StringMethods::split($match[0], ',\s*');
                        $arguments = array_merge($arguments, $matches);
                        $offset = StringMethods::indexOf($validator, '(');
                        $function = substr($validator, 0, $offset);
                    }

                    if (!isset($defined[$function])) {
                        throw new Exception\Validation(sprintf('The %s validator is not defined', $function));
                    }

                    $template = $defined[$function];

                    if (!call_user_func_array(array($this, $template['handler']), $arguments)) {
                        $replacements = array_merge(array(
                            $label ? $label : $raw
                                ), $arguments);

                        $message = $template['message'];

                        foreach ($replacements as $i => $replacement) {
                            $message = str_replace("{{$i}}", $replacement, $message);
                        }

                        if (!isset($this->_errors[$name])) {
                            $this->_errors[$name] = array();
                        }

                        $this->_errors[$name][] = $message;
                    }
                }
            }
        }

        return !count($this->errors);
    }

}
