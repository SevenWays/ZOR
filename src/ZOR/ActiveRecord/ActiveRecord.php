<?php

namespace ZOR\ActiveRecord;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\TableIdentifier;
use Zend\Db\RowGateway\RowGateway;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

abstract class ActiveRecord extends RowGateway {

    /**
     * Default name of primaryKey
     * @var string
     */
    protected $primaryKeyColumn = 'id';

    /**
     *
     * @var \Zend\Db\Adapter\Adapter 
     */
    protected $adapter = null;

    /**
     * Contains number of affected rows
     * @var int
     */
    protected $affectedRows = 0;

    /**
     * Use if contains forange key to many Tables
     * @var array
     */
    protected $hasMany = array();

    /**
     * Use if contains forange key to parent Table
     * @var array
     */
    protected $belongsTo = array();

    /**
     * Contains sql query
     * @var string
     */
    protected $sqlString = "";

    /**
     * If add new record without save in DB 
     * @var bool
     */
    protected $_newrecord = true;

    /**
     * If did change a value in the row, rerurn TRUE
     * @var bool
     */
    protected $_changed = false;

    /**
     * @var \Zend\Db\Sql\Select
     */
    protected $sqlCombine = null;

    /**
     * Return result of Statment
     * @var mix
     */
    protected $_result = null;

    /**
     * Did execute of Statment 
     * @var bool
     */
    protected $executed = false;

    /**
     *
     * @var \Zend\Db\Sql\Predicate\Predicate 
     */
    protected $predicate = null;
    
    protected $agg_funcs = array("SUM", "AVG", "MAX", "MIN");

    /**
     * SQL date format
     */
    const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * 
     * @param \Zend\Db\Adapter\Adapter $adapter
     */
    public function __construct($adapter) {
        $table = new TableIdentifier($this->table);
        $this->setDbAdapter($adapter);
        parent::__construct($this->primaryKeyColumn, $table, $this->getDbAdapter());

        if (is_null($this->predicate)) {
            $this->predicate = $this->getSelect()->where;
        }
    }

    /**
     * 
     * @param \Zend\Db\Adapter\AdapterInterface $adapter
     */
    public function setDbAdapter(\Zend\Db\Adapter\AdapterInterface $adapter) {
        $this->adapter = $adapter;
    }

    /**
     * 
     * @return \Zend\Db\Adapter\Adapter
     */
    public function getDbAdapter() {
        return $this->adapter;
    }

    /**
     * Create a new row to table
     * @param array $params
     * @return \ZOR\ActiveRecord\ActiveRecord
     */
    public function create(array $params) {
        $this->build($params);
        $this->save();
        return $this;
    }

    /**
     * Build a new row without save to table
     * @param array $params
     * @return \ZOR\ActiveRecord\ActiveRecord
     */
    public function build(array $params) {
        $params['created_at'] = (!empty($params['created_at'])) ? $params['created_at'] : date(self::DATE_FORMAT);
        $params['updated_at'] = (!empty($params['updated_at'])) ? $params['updated_at'] : date(self::DATE_FORMAT);

        $params = array_merge($this->data, $params);
        $this->populate($params);

        return $this;
    }

    /**
     * Update value of attribute and save in the table
     * @param array $params
     * @return \ZOR\ActiveRecord\ActiveRecord
     */
    public function update_attributes(array $params) {
        foreach ($params as $key => $value) {
            $this->$key = $value;
        }
        $this->save();
        return $this;
    }

    /**
     * Get first row from table
     * @return mix
     */
    public function first() {
        $this->getSelect()->order(array($this->primaryKeyColumn[0] => 'ASC'))->limit(1);
        return $this->execute();
    }

    /**
     * Get last row from table
     * @return mix
     */
    public function last() {
        $this->getSelect()->order(array($this->primaryKeyColumn[0] => 'DESC'))->limit(1);
        return $this->execute();
    }

    /**
     * Get all records from table
     * @return array
     */
    public function all() {
        return $this->execute();
    }

    /**
     * Where with Lazy Loading pattern
     * @param mix $params
     * @return \Zend\Db\Sql\Select
     */
    public function where($predicate = array()) {

        $this->getSelect()->where($predicate);
        return $this->getSelect();
    }

    /**
     * Create "Equal To" predicate
     *
     * Utilizes Operator predicate
     *
     * @param  int|float|bool|string $left
     * @param  int|float|bool|string $right
     * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER {@see allowedTypes}
     * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE {@see allowedTypes}
     * @return Predicate
     */
    public function equalTo($left, $right, $leftType = 'identifier', $rightType = 'value') {
        $this->predicate->equalTo($left, $right, $leftType, $rightType);
        return $this;
    }

    /**
     * Create "Not Equal To" predicate
     *
     * Utilizes Operator predicate
     *
     * @param  int|float|bool|string $left
     * @param  int|float|bool|string $right
     * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER {@see allowedTypes}
     * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE {@see allowedTypes}
     * @return Predicate
     */
    public function notEqualTo($left, $right, $leftType = 'identifier', $rightType = 'value') {
        $this->predicate->notEqualTo($left, $right, $leftType, $rightType);
        return $this;
    }

    /**
     * Create "Less Than" predicate
     *
     * Utilizes Operator predicate
     *
     * @param  int|float|bool|string $left
     * @param  int|float|bool|string $right
     * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER {@see allowedTypes}
     * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE {@see allowedTypes}
     * @return Predicate
     */
    public function lessThan($left, $right, $leftType = 'identifier', $rightType = 'value') {
        $this->predicate->lessThan($left, $right, $leftType, $rightType);
        return $this;
    }

    /**
     * Create "Greater Than" predicate
     *
     * Utilizes Operator predicate
     *
     * @param  int|float|bool|string $left
     * @param  int|float|bool|string $right
     * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER {@see allowedTypes}
     * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE {@see allowedTypes}
     * @return Predicate
     */
    public function greaterThan($left, $right, $leftType = 'identifier', $rightType = 'value') {
        $this->predicate->greaterThan($left, $right, $leftType, $rightType);
        return $this;
    }

    /**
     * Create "Less Than Or Equal To" predicate
     *
     * Utilizes Operator predicate
     *
     * @param  int|float|bool|string $left
     * @param  int|float|bool|string $right
     * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER {@see allowedTypes}
     * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE {@see allowedTypes}
     * @return Predicate
     */
    public function lessThanOrEqualTo($left, $right, $leftType = 'identifier', $rightType = 'value') {
        $this->predicate->lessThanOrEqualTo($left, $right, $leftType, $rightType);
        return $this;
    }

    /**
     * Create "Greater Than Or Equal To" predicate
     *
     * Utilizes Operator predicate
     *
     * @param  int|float|bool|string $left
     * @param  int|float|bool|string $right
     * @param  string $leftType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_IDENTIFIER {@see allowedTypes}
     * @param  string $rightType TYPE_IDENTIFIER or TYPE_VALUE by default TYPE_VALUE {@see allowedTypes}
     * @return Predicate
     */
    public function greaterThanOrEqualTo($left, $right, $leftType = 'identifier', $rightType = 'value') {
        $this->predicate->greaterThanOrEqualTo($left, $right, $leftType, $rightType);
        return $this;
    }

    /**
     * Create "Like" predicate
     *
     * Utilizes Like predicate
     *
     * @param  string $identifier
     * @param  string $like
     * @return Predicate
     */
    public function like($identifier, $like) {
        $this->predicate->like($identifier, $like);
        return $this;
    }

    /**
     * Create "notLike" predicate
     *
     * Utilizes In predicate
     *
     * @param  string $identifier
     * @param  string $notLike
     * @return Predicate
     */
    public function notLike($identifier, $notLike) {
        $this->predicate->notLike($identifier, $notLike);
        return $this;
    }

    /**
     * Create an expression, with parameter placeholders
     *
     * @param $expression
     * @param $parameters
     * @return $this
     */
    public function expression($expression, $parameters) {
        $this->predicate->expression($expression, $parameters);
        return $this;
    }

    /**
     * Create "Literal" predicate
     *
     * Literal predicate, for parameters, use expression()
     *
     * @param  string $literal
     * @return Predicate
     */
    public function literal($literal) {
        $this->predicate->literal($literal);
        return $this;
    }

    /**
     * Create "IS NULL" predicate
     *
     * Utilizes IsNull predicate
     *
     * @param  string $identifier
     * @return Predicate
     */
    public function isNull($identifier) {
        $this->predicate->isNull($identifier);
        return $this;
    }

    /**
     * Create "IS NOT NULL" predicate
     *
     * Utilizes IsNotNull predicate
     *
     * @param  string $identifier
     * @return Predicate
     */
    public function isNotNull($identifier) {
        $this->predicate->isNotNull($identifier);
        return $this;
    }

    /**
     * Create "NOT IN" predicate
     *
     * Utilizes NotIn predicate
     *
     * @param  string $identifier
     * @param  array|\Zend\Db\Sql\Select $valueSet
     * @return Predicate
     */
    public function notIn($identifier, $valueSet = null) {
        $this->predicate->notIn($identifier, $valueSet);
        return $this;
    }

    /**
     * Create "IN" predicate
     *
     * Utilizes In predicate
     *
     * @param  string $identifier
     * @param  array|\Zend\Db\Sql\Select $valueSet
     * @return Predicate
     */
    public function in($identifier, $valueSet = null) {
        $this->predicate->in($identifier, $valueSet);
        return $this;
    }

    /**
     * Create "between" predicate
     *
     * Utilizes Between predicate
     *
     * @param  string $identifier
     * @param  int|float|string $minValue
     * @param  int|float|string $maxValue
     * @return \Zend\Db\Sql\Predicate\Predicate
     */
    public function between($identifier, $minValue, $maxValue) {
        $this->predicate->between($identifier, $minValue, $maxValue);
        return $this;
    }

    /**
     * Use given predicate directly
     *
     * Contrary to {@link addPredicate()} this method respects formerly set
     * AND / OR combination operator, thus allowing generic predicates to be
     * used fluently within where chains as any other concrete predicate.
     *
     * @param  PredicateInterface $predicate
     * @return Predicate
     */
    public function predicate(PredicateInterface $predicate) {
        $this->predicate->predicate($predicate);
        return $this;
    }

    /**
     * Add order to query
     * @param type $order
     * @return \ZOR\ActiveRecord\ActiveRecord
     */
    /* public function order($order) {
      if (is_array($order)) {
      $this->order = $order;
      } elseif (is_string($order)) {
      $this->order = array($order);
      }
      return $this;
      } */

    /**
     * Find by primary key
     * If you find by more IDs pleas pass as array 
     * @param int|array $id
     * @return \ZOR\ActiveRecord\ActiveRecord
     */
    public function find($id) {

        if (is_array($id)) {
            $this->in($this->primaryKeyColumn[0], $id);
        } else {
            $this->equalTo($this->primaryKeyColumn[0], $id);
        }
        return $this->execute();
    }

    /**
     * Find by attributename
     * @param string $name
     * @param mix $argument
     * @return \ZOR\ActiveRecord\ActiveRecord
     */
    public function find_by_attribute($name, $argument) {
        $att_name = ($name != 'pk') ? $name : $this->primaryKeyColumn[0];
        $this->equalTo($att_name, $argument);
        $this->execute();
        return $this;
    }

    /**
     * Find or create by attribunt
     * @param type $column
     * @param type $argument
     * @param type $_
     * @return \ZOR\ActiveRecord\ActiveRecord
     */
    public function find_or_create_by_attribute($column, $argument, $_ = array()) {
        $this->equalTo($column, $argument);
        $this->execute();
        if ($this->affectedRows == 0) {
            if (is_array($_)) {
                if (!array_key_exists($column, $_)) {
                    $_[$column] = $argument;
                }
            }
            $this->create($_);
        }
        return $this;
    }

    /**
     * @return Zend\Db\Sql\Sql
     */
    public function getSelect() {
        if (is_null($this->sqlCombine)) {
            $this->sqlCombine = $this->sql->select();
        }
        return $this->sqlCombine;
    }

    /**
     * 
     * @param type $sql
     * @return type
     */
    protected function executeStatement() {
        $statement = $this->sql->prepareStatementForSqlObject($this->getSelect());
        return $statement->execute();
    }

    protected function createStatement() {
        if (!empty($this->belongsTo)) {
            foreach ($this->belongsTo as $key => $value) {
                if (!empty($value['key'])) {
                    $this->getSelect()->where(array($value['attribut'] => $value['key']));
                }
            }
        }

        $result = $this->executeStatement();

        $this->affectedRows = $result->getAffectedRows();
        $this->executed = true;

        // make sure data and original data are in sync after save
        return $this->result($result);
    }

    public function add_func($func_name, $attr, $as = null) {
        $as_attr = (is_null($as)) ? $func_name : $as;
        $sql = $this->getSelect()->columns(array($as_attr => new \Zend\Db\Sql\Expression($func_name . '(' . $attr . ')')));
        $result = $this->executeStatement();
        $rowData = $result->current();
        return $rowData[$as_attr];
    }

    /**
     * Return rows count 
     * @param string $attr
     * @return int
     */
    public function COUNT($attr = '*') {
        if (!is_null($this->_result)) {
            if (is_array($this->_result)) {
                return count($this->_result);
            }
            return 1;
        } else {
            return $this->add_func('COUNT', $attr);
        }
    }

    /**
     * 
     * @return \ZOR\ActiveRecord\ActiveRecord|Array
     */
    public function execute() {
        if (!$this->executed) {
            return $this->createStatement();
        }
        return $this;
    }

    /* public function __invoke() {
      $this->executeStatement($this->getSelect());
      return $this;
      } */

    public function __get($name) {
        $this->execute();
        return parent::__get($name);
    }

    public function __set($name, $value) {

        if ($this->offsetExists($name)) {
            $this->_changed = true;
        }
        parent::__set($name, $value);
    }

    /**
     * Set result as ActiveRecord
     * @param type $result
     * @return \ZOR\ActiveRecord\ActiveRecord|\ZOR\ActiveRecord\class
     */
    protected function result($result) {
        $array = array();
        if ($this->affectedRows == 1) {
            $rowData = $result->current();
            $this->populate($rowData, true);
            $this->_result = $this;
        } elseif ($this->affectedRows > 1) {
            $class = get_called_class();
            foreach ($result as $value) {
                $a = new $class($this->adapter);
                $a->populate($value);
                $array[] = $a;
                unset($a); // cleanup           
            }
            unset($result);
            $this->_result = $array;
        }
        return $this->_result;
    }

    public function getResult() {
        return $this->_result;
    }

    /**
     * Save a row to table
     * @return \ZOR\ActiveRecord\ActiveRecord
     */
    public function save() {
        if (!empty($this->belongsTo)) {
            foreach ($this->belongsTo as $key => $value) {
                if (!empty($value['key'])) {
                    $this->{$value['attribut']} = $value['key'];
                }
            }
        }
        $this->_newrecord = (parent::save() > 0) ? false : true;
        return $this;
    }

    /**
     * Return whether Record is new, and not saved
     * @return bool
     */
    public function isNewRecord() {
        return $this->_newrecord;
    }

    /**
     * Return whether a attribute is changed
     * @return bool
     */
    public function isChanged() {
        return $this->_changed;
    }

    /**
     * Get last generated sql string
     * @return type
     */
    public function getSqlString() {
        return $this->getSelect()->getSqlString();
    }

    public function __call($func = "find_by_pk", $arguments = array()) {

        if (preg_match('/(^find_by_)(.*)/', $func, $result)) {
            return $this->find_by_attribute($result[2], $arguments);
        }
        if (preg_match('/(^find_or_create_by_)(.*)/', $func, $result)) {
            $arguments = func_get_args();
            $_ = (!empty($arguments[1][1])) ? $arguments[1][1] : array();
            return $this->find_or_create_by_attribute($result[2], $arguments[1][0], $_);
        }

        if (in_array($func, $this->agg_funcs)) {
            return $this->add_func($func, $arguments[0]);
        }

        if (!empty($this->hasMany)) {
            if (key_exists($func, $this->hasMany)) {
                return $this->create_many($func);
            }
        }
        /*  if (key_exists($func, $this->belongsTo)) {
          // return $this->create_many($func);
          } */
    }

    /**
     * 
     * @param type $table
     * @return type
     */
    protected function create_many($table) {

        $class = new $this->hasMany[$table][0]($this->getDbAdapter());
        $classname = strtolower(end(explode("\\", get_called_class())));
        $class->belongsTo[$classname]['key'] = $this->{$this->primaryKeyColumn[0]};

        return $class;
    }

}
