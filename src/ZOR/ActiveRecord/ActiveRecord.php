<?php

namespace ZOR\ActiveRecord;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\TableIdentifier;
use Zend\Db\RowGateway\AbstractRowGateway;
use Zend\Filter\StaticFilter;
use Zend\Validator\StaticValidator;
use Zend\Db\Adapter\AdapterAwareInterface;

abstract class ActiveRecord extends AbstractRowGateway implements AdapterAwareInterface {

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
     * For example:  array('ModelName' => array('class'=>'Application\Model\ModelName', 'through' => 'Item')); //through is using if n:n relationship
     * @var array
     */
    protected $has_many = array();

    /**
     * Use if contains forange key to parent Table
     * For example: array('ModelName' => array('class'=>'Application\Model\ModelName', 'foreign_key_attribute' => 'model_name_id', 'foreign_key' => null)
     * @var array
     */
    protected $belongs_to = array();

    /**
     *
     * @var type 
     */
    protected $models = array();

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
    protected $_result = array();

    /**
     * Did execute of Statment 
     * @var bool
     */
    protected $executed = false;
    protected $agg_funcs = array("SUM", "AVG", "MAX", "MIN");
    protected $messages = array();

    /**
     * SQL date format
     */
    const DATE_FORMAT = 'Y-m-d H:i:s';

    /*
     * Included traits
     */

    use Traits\Predication;

    use Traits\Validation;

    use Traits\Filter;

    /**
     * 
     * @param \Zend\Db\Adapter\Adapter $adapter
     */
    public function __construct($adapter = null) {

        if ($adapter) {
            $this->setDbAdapter($adapter);
        }

        return $this;
    }

    public function initialize() {
        $this->sql = new Sql($this->getDbAdapter(), $this->table);

        /*  if (is_null($this->predicate)) {
          $this->predicate = new \Zend\Db\Sql\Predicate\Predicate(); //$this->sql->select()->where;//$this->getSelect()->where;
          } */
        parent::initialize();
    }

    /**
     * 
     * @param \Zend\Db\Adapter\AdapterInterface $adapter
     */
    public function setDbAdapter(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->initialize();
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
        $this->populate($params);
        $this->save();
        return $this;
    }

    /**
     * Build a new row without save to table
     * @param array $params
     * @return \ZOR\ActiveRecord\ActiveRecord
     */
    public function build(array $params) {
        $this->populate($params);
    }

    public function populate(array $rowData, $rowExistsInDatabase = false) {
        parent::populate(array_merge($this->data, $rowData), $rowExistsInDatabase);
        $this->filterAll();
        return $this;
    }

    /**
     * Update value of attribute and save in the table
     * @param array $params
     * @return \ZOR\ActiveRecord\ActiveRecord
     */
    public function update_attributes(array $params) {
        $this->populate($params);
        $this->save();
        return $this;
    }

    /**
     * Get first row from table
     * @return mix
     */
    public function first($limit = 1) {
        $this->getSelect()->order(array($this->primaryKeyColumn[0] => 'ASC'))->limit($limit);
        return $this->execute();
    }

    /**
     * Get last row from table
     * @return mix
     */
    public function last($limit = 1) {
        $this->getSelect()->order(array($this->primaryKeyColumn[0] => 'DESC'))->limit($limit);
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
     * Get count number of rows
     * @param int $count
     * @return array
     */
    function take($limit) {
        return $this->first($limit);
    }

    /**
     * Where with Lazy Loading pattern
     * @param mix $params
     * @return \Zend\Db\Sql\Where
     */
    public function where($predicate = NULL) {
        if ($this->executed) {
            $this->executed = false;
        }
        if (!is_null($predicate)) {
            $this->getSelect()->where($predicate);
        }
        return $this->getSelect()->where;
    }

    /**
     * Find by primary key
     * If you find by more IDs pleas pass as array 
     * @param int|array $id
     * @return \ZOR\ActiveRecord\ActiveRecord
     */
    public function find($id) {

        if (is_array($id)) {
            $this->where()->in($this->primaryKeyColumn[0], $id);
        } else {
            $this->where()->equalTo($this->primaryKeyColumn[0], $id);
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
        return $this->execute();
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
        $result = $statement->execute();
        $result->buffer();
        return $result;
    }

    protected function createStatement() {
        if (!empty($this->belongs_to)) {
            foreach ($this->belongs_to as $value) {
                if (!empty($value['foreign_key'])) {
                    $this->getSelect()->where(array($value['foreign_key_attribute'] => $value['foreign_key']));
                }
            }
        }

        $this->executed = true;
        return $this->result($this->executeStatement());
    }

    /**
     * Applies SQL aggregate functions return a single value, calculated from values in a column.
     * @param string $func_name
     * @param string $attr
     * @param string $as
     * @return mixed
     */
    public function apply_func($func_name, $attr, $as = null) {
        $as_attr = (is_null($as)) ? $func_name : $as;
        $this->getSelect()->columns(array($as_attr => new \Zend\Db\Sql\Expression($func_name . '(' . $attr . ')')));
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
            return $this->apply_func('COUNT', $attr);
        }
    }

    /**
     * 
     * @return \ZOR\ActiveRecord\ActiveRecord|Array
     */
    public function execute() {
        return $this->createStatement();
    }

    public function __get($name) {
        if (!$this->executed) {
            $this->createStatement();
        }
        return parent::__get($name);
    }

    public function __set($name, $value) {

        if ($this->offsetExists($name)) {
            $this->_changed = true;
        }
        parent::__set($name, $this->filterIt($name, $value));
    }

    /**
     * Set result as ActiveRecord
     * @param type $result
     * @return \ZOR\ActiveRecord\ActiveRecord|\ZOR\ActiveRecord\class
     */
    protected function result($result) {
        $this->affectedRows = $result->count();
        $array = array();

        if ($this->affectedRows == 1) {
            $rowData = $result->current();
            $this->populate($rowData, true);
            return $this;
        } elseif ($this->affectedRows > 1) {
            $class = get_called_class();
            foreach ($result as $value) {
                $a = new $class($this->adapter);
                $a->populate($value, true);
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
     * @return bool
     */
    public function save() {
        $status = false;
        if (!$this->isValid()) {
            return $status;
        }

        if (!empty($this->belongs_to)) {
            foreach ($this->belongs_to as $key => $value) {
                if (!empty($value['foreign_key'])) {
                    $this->{$value['foreign_key_attribute']} = $value['foreign_key'];
                }
            }
        }

        if (!empty($this->models)) {

            foreach ($this->models as $key => $value) {
                if (key_exists($key, $this->has_many) && !empty($this->has_many[$key]['through'])) {
                    $this->saveMany($this->has_many[$key]['through'], $value['model'], $value['params']);
                } else {
                    throw new \Exception("Relationship not exist to $key");
                }
            }
        }

        $this->data['created_at'] = (!empty($this->created_at)) ? $this->created_at : date(self::DATE_FORMAT);
        $this->data['updated_at'] = date(self::DATE_FORMAT);

        if (parent::save() > 0) {
            $this->_newrecord = false;
            $status = TRUE;
        }
        return $status;
    }

    /**
     * Update existing row
     * @param array $params
     * @return \ZOR\ActiveRecord\ActiveRecord
     */
    public function update(array $params) {
        $this->populate($params, true);
        return $this->save();
    }

    protected function saveMany($through, $model, $params = array()) {
        if (key_exists($through, $this->has_many)) {
            $class = new $this->has_many[$through]['class']($this->getDbAdapter());
            $class->{$class->belongs_to[$this->table]['foreign_key_attribute']} = $this->{$this->primaryKeyColumn[0]};
            $class->{$class->belongs_to[$model->table]['foreign_key_attribute']} = $model->{$model->primaryKeyColumn[0]};
            $class->populate($params);
            $class->save();
        }
    }

    /**
     * Return true if the record is new, and not saved
     * @return bool
     */
    public function isNewRecord() {
        $this->_newrecord = ($this->rowExistsInDatabase()) ? false : true;
        return $this->_newrecord;
    }

    /**
     * Returns whether an attribute is changed
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

        if (preg_match('/(^add_)(.*)/', $func, $result)) {
            if (!empty($this->has_many && key_exists($result[2], $this->has_many))) {
                $params = (!empty($arguments[1])) ? $arguments[1] : array();
                return $this->append_model($result[2], $arguments[0], $params);
            } else {
                throw new \Exception("Relationship not exist to $result[2]");
            }
        }

        if (in_array($func, $this->agg_funcs)) {
            return $this->apply_func($func, $arguments[0]);
        }

        if (!empty($this->has_many)) {
            if (key_exists($func, $this->has_many)) {
                if (key_exists("through", $this->has_many[$func])) {
                    return $this->many_to_many($func, $this->has_many[$func]['through']);
                } else {
                    return $this->one_to_many($func);
                }
            }
        }
        if (!empty($this->belongs_to)) {
            if (key_exists($func, $this->belongs_to)) {
                return $this->getBelong($func);
            }
        }

        throw new \Exception("Undifined method $func");
    }

    protected function append_model($model_name, $model, $params = null) {
        $this->models[$model_name]['model'] = $model;
        $this->models[$model_name]['params'] = $params;
    }

    protected function getBelong($belongTo) {
        $class = new $this->belongs_to[$belongTo]['class']($this->getDbAdapter());
        return $class->find($this->{$this->belongs_to[$belongTo]['foreign_key_attribute']});
    }

    /**
     * 
     * @param sting $table
     * @param string $through
     * @return mixed
     */
    protected function many_to_many($table, $through) {
        $keys = null;
        $result = $this->one_to_many($through)->all();
        foreach ($result as $value) {
            $keys[] = $value->{$value->belongs_to[$table]['foreign_key_attribute']};
        }
        $class = $this->one_to_many($table);
        $class->in($class->primaryKeyColumn[0], $keys);
        return $class->execute();
    }

    /**
     * 
     * @param string $table
     * @return mixed
     */
    protected function one_to_many($table) {
        $class = new $this->has_many[$table]['class']($this->getDbAdapter());
        if (!empty($class->belongs_to)) {
            $classname = $this->table;
            $class->belongs_to[$classname]['foreign_key'] = $this->{$this->primaryKeyColumn[0]};
        }
        return $class;
    }

    /**
     * Delete a row with Relationship by foreinge key
     */
    public function destroy() {
        if (!empty($this->has_many)) {
            foreach ($this->has_many as $key => $value) {
                $class = $this->one_to_many($key);
                $result = $class->all();
                if (!empty($result)) {
                    if (is_array($result)) {
                        foreach ($result as $temp_value) {
                            $temp_value->delete();
                        }
                    } else {
                        $result->delete();
                    }
                }
            }
        }

        $this->delete();
    }

    public function getMessages() {
        return $this->messages;
    }

    public function __toString() {
        return get_class($this);
    }

}
