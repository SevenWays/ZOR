<?php

/**
 * Copyright (c) 2016 SevenWays IT Solutions.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in
 * the documentation and/or other materials provided with the
 * distribution.
 *
 * * Neither the names of the copyright holders nor the names of the
 * contributors may be used to endorse or promote products derived
 * from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @link http://seven-ways.net
 * @version 0.0.1 from 27.04.2016
 */

namespace ZOR\Db\Sql\Ddl;

use Zend\Db\Adapter\Adapter;
use ZOR\Db\Sql\Ddl\Column\Integer;
use Zend\Db\Sql\Ddl\Column\BigInteger;
use Zend\Db\Sql\Ddl\Column\Blob;
use Zend\Db\Sql\Ddl\Column\Varchar;
use Zend\Db\Sql\Ddl\Column\Char;
use Zend\Db\Sql\Ddl\Column\Floating;
use Zend\Db\Sql\Ddl\Column\Decimal;
use Zend\Db\Sql\Ddl\Column\Date;
use Zend\Db\Sql\Ddl\Column\Datetime;
use Zend\Db\Sql\Ddl\Column\Time;
use Zend\Db\Sql\Ddl\Column\Timestamp;
use Zend\Db\Sql\Ddl\Column\Text;
use Zend\Db\Sql\Ddl\Column\Boolean;
use Zend\Db\Sql\Ddl\Constraint\PrimaryKey;
use Zend\Db\Sql\Ddl\Constraint\UniqueKey;
use Zend\Db\Sql\Ddl\Constraint\ForeignKey;
use Zend\Db\Sql\Ddl\AlterTable;
use Zend\Db\Sql\Ddl\CreateTable;
use Zend\Db\Sql\Ddl\DropTable;

class CreateNewTable {

    /**
     *
     * @var \Zend\Db\Sql\Ddl\Column\Column
     */
    protected $columns = array();
    protected $constraint = null;
    protected $table_name = null;
    protected $field_name = NULL;
    protected $primaryKeyField = null;

    public function createColumnFromString($string) {
        $array = explode(',', $string);
        $this->table_name = array_shift($array);
        if (is_array($array)) {

            foreach ($array as $value) {
                $result = explode(':', $value);


                foreach ($result as $key => $value) {

                    switch ($key) {
                        case 0:
                            $this->field_name = $value;

                            break;
                        case 1:
                            $this->createType($value);

                            break;
                        case 2:
                            $this->createOption('attribute', $value);

                            break;
                        case 3:
                            $this->setNullable($value);

                            break;
                        case 4:
                            $this->setDefault($value);

                            break;
                        case 5:
                            $this->createOption($value, true);

                            break;
                        case 6:
                            $this->addConstraint($value, $this->field_name, $this->field_name);

                            break;

                        default:
                            break;
                    }
                }
            }
        }
    }

    public function setPrimaryKeyColumn($pk) {
        $this->primaryKeyField = $pk;
    }

    public function getPrimaryKeyColumn() {
        if (is_null($this->primaryKeyField)) {
            $this->setPrimaryKeyColumn('id');
        }
        return $this->primaryKeyField;
    }

    public function getTableName() {
        return $this->table_name;
    }

    public function createTable() {
        $sett = array(
            'driver' => 'Pdo',
            'dsn' => 'mysql:dbname=zor;host=localhost',
            'username' => 'root',
            'password' => 'emily2007',
            'driver_options' => array(
                \Pdo::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
        ));
        $adapter = new Adapter($sett);

        if (empty($this->table_name) || empty($this->columns)) {
            throw new \Exception("Table name can't be empty");
        }

        $tb = new CreateTable($this->table_name);
        if (is_array($this->columns)) {
            foreach ($this->columns as $column) {
                $tb->addColumn($column);
            }
        }

        if (is_array($this->constraint)) {
            foreach ($this->constraint as $constraint) {
                $tb->addConstraint($constraint);
            }
        }

        $adapter->query($tb->getSqlString($adapter->getPlatform()), Adapter::QUERY_MODE_EXECUTE);

        return $tb->getSqlString($adapter->getPlatform());
    }

    public function addConstraint($type, $columns = null, $name = null) {
        switch (strtolower($type)) {

            case 'primerykey':
                $this->constraint[] = new PrimaryKey($columns, $name);
                $this->setPrimaryKeyColumn($columns);
                break;
            case 'foreignkey':
                $this->constraint[] = new ForeignKey($columns, $name);

                break;
            case 'uniquekey':
                $this->constraint[] = new UniqueKey($this->field_name, $this->field_name);

                break;

            default:
                break;
        }
    }

    protected function createOption($key, $value) {
        $key = ($key == 'auto_increment') ? 'auto increment' : $key;
        $this->columns[$this->field_name]->setOption($key, $value);
    }

    protected function setNullable($param) {
        if ($param == 'null') {
            $this->columns[$this->field_name]->setNullable(true);
        } else {
            $this->columns[$this->field_name]->setNullable(FALSE);
        }
    }

    protected function setDefault($param) {
        if ($param != null)
            $this->columns[$this->field_name]->setDefault($param);
    }

    protected function createType($type) {
        preg_match_all('/(\w+)/', $type, $match);

        $field_type = (isset($match[0][0])) ? ucfirst($match[0][0]) : 'Varchar';
        $length = (isset($match[0][1])) ? $match[0][1] : null;
        $decimal = (isset($match[0][2])) ? $match[0][2] : null;



        switch ($field_type) {
            case 'Biginteger':
                $this->createIntegerColumn($this->field_name, $length);
                break;
            case 'Integer':
                $this->createIntegerColumn($this->field_name, $length);
                break;
            case 'Blob':
                $this->createBlobColumn($this->field_name, $length);
                break;
            case 'Varchar':
                $this->createVarcharColumn($this->field_name, $length);
                break;
            case 'Char':
                $this->createCharColumn($this->field_name, $length);
                break;
            case 'Float':
                $this->createFloatingColumn($this->field_name, $length, $decimal);
                break;
            case 'Decimal':
                $this->columens[$this->field_name] = new Decimal($this->field_name, $length, $decimal);
                break;
            case 'Date':
                $this->createDateColumn($this->field_name);
                break;
            case 'Datetime':
                $this->createDateTimeColumn($this->field_name);
                break;
            case 'Time':
                $this->createTimeColumn($this->field_name);
                break;
            case 'Timestamp':
                $this->createTimestampColumn($this->field_name);
                break;
            case 'Boolean':
                $this->createBooleanColumn($this->field_name);
                break;
            case 'Text':
                $this->createBooleanColumn($this->field_name);
                break;
            default:
                throw new Exception('Invalide Type of Column');
                break;
        }
    }

    public function createIntegerColumn($name, $length = null, $nullable = false, $default = null, array $options = array()) {
        $column = new Integer($name, $nullable, $default, $options);
        if ($length) {
            $column->setOption('length', $length);
        }
        $this->createColumn($name, $column);
    }

    public function createBigIntegerColumn($name, $length = null, $nullable = false, $default = null, array $options = array()) {
        $column = new BigInteger($name, $nullable, $default, $options);
        if ($length) {
            $column->setOption('length', $length);
        }
        $this->createColumn($name, $column);
    }

    public function createBlobColumn($name, $length = null, $nullable = false, $default = null, array $options = array()) {
        $column = new Blob($name, $length, $nullable, $default, $options);
        $this->createColumn($name, $column);
    }

    public function createCharColumn($name, $length = null, $nullable = false, $default = null, array $options = array()) {
        $column = new Char($name, $length, $nullable, $default, $options);
        $this->createColumn($name, $column);
    }

    public function createVarcharColumn($name, $length = null, $nullable = false, $default = null, array $options = array()) {
        $column = new Varchar($name, $length, $nullable, $default, $options);
        $this->createColumn($name, $column);
    }

    public function createFloatingColumn($name, $digits = null, $decimal = null, $nullable = false, $default = null, array $options = array()) {
        $column = new Floating($name, $digits, $decimal, $nullable, $default, $options);
        $this->createColumn($name, $column);
    }

    public function createDecimalColumn($name, $digits = null, $decimal = null, $nullable = false, $default = null, array $options = array()) {
        $column = new Decimal($name, $digits, $decimal, $nullable, $default, $options);
        $this->createColumn($name, $column);
    }

    public function createDateColumn($name = null, $nullable = false, $default = null, array $options = array()) {
        $column = new Date($name, $nullable, $default, $options);
        $this->createColumn($name, $column);
    }

    public function createDateTimeColumn($name = null, $nullable = false, $default = null, array $options = array()) {
        $column = new Datetime($name, $nullable, $default, $options);
        $this->createColumn($name, $column);
    }

    public function createTimeColumn($name = null, $nullable = false, $default = null, array $options = array()) {
        $column = new Time($name, $nullable, $default, $options);
        $this->createColumn($name, $column);
    }

    public function createTimestampColumn($name = null, $nullable = false, $default = null, array $options = array()) {
        $column = new Timestamp($name, $nullable, $default, $options);
        $this->createColumn($name, $column);
    }

    public function createBooleanColumn($name = null, $nullable = false, $default = null, array $options = array()) {
        $column = new Boolean($name, $nullable, $default, $options);
        $this->createColumn($name, $column);
    }

    public function createTextColumn($name = null, $nullable = false, $default = null, array $options = array()) {
        $column = new Text($name, $nullable, $default, $options);
        $this->createColumn($name, $column);
    }

    public function createColumn($name, \Zend\Db\Sql\Ddl\Column\ColumnInterface $column) {
        $this->columns[$name] = $column;
    }

}
