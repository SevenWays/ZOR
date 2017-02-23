<?php

namespace ZOR\ActiveRecord;

/**
 * Description of Migration
 *
 * @author sergej
 */
use Zend\Db\Sql\Ddl\Column\ColumnInterface;
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
use Zend\Filter\StaticFilter;

abstract class Migration {

    private $class = null;
    private $called_method = null;
    private $add_columns = array();
    private $drop_columns = array();
    private $constraints = array();
    private $methods = array('change', 'up', 'down');
    private $dbAdapter = null;
    protected $migrations = __DIR__ . "/migrations.php";
    private $migrationName = null;
    private $migrationID = null;

    function setPathToConfigFile($path) {
        $this->migrations = $path;
        if(!file_exists($path)){
            touch($path);
        }
    }

    public function setAdapter(Adapter $dbAdapter) {
        $this->dbAdapter = $dbAdapter;
    }

    public function getAdapter() {
        if (is_null($this->dbAdapter)) {
            throw new Exception("Database Adapter is empty");
        } else {
            return $this->dbAdapter;
        }
    }

    public function isMigrated() {
        $obj = new \ReflectionClass($this);
        $filename = basename($obj->getFileName(), '.php');
        unset($obj);
        preg_match_all('/(\d+)_(\S+)/', $filename, $matches);

        $this->migrationID = $matches[1][0];
        $this->migrationName = $matches[2][0];

        if (key_exists($this->migrationName, $this->getMigrationsConfig())) {
            return true;
        }
        return false;
    }

    public function migrate() {
        if (!$this->isMigrated()) {
            $this->called_method = __FUNCTION__;
            $this->runMethods();
            $this->execute();
            $this->setAsMigrated();
            return "Migration: $this->migrationName, ID: $this->migrationID migrated!";
        }
    }

    public function rollback() {
        if ($this->isMigrated()) {
            $this->called_method = __FUNCTION__;
            $this->runMethods();
            $this->execute();
            $this->setAsRollback();
            return "Migration: $this->migrationName ID: $this->migrationID rollback!";
        }
    }

    private function execute() {

        if ($this->class instanceof CreateTable) {
            $this->setDefaultColumns();
            $this->appendColumns();
            $this->appendConstraint();
        }
        if ($this->class instanceof AlterTable) {
            $this->appendColumns();
            $this->appendDropColumns();
            $this->appendConstraint();
        }

        $this->getAdapter()->query($this->class->getSqlString($this->getAdapter()->getPlatform()), Adapter::QUERY_MODE_EXECUTE);
        echo $this->class->getSqlString($this->getAdapter()->getPlatform());
    }

    private function setDefaultColumns() {
        if (!key_exists('id', $this->add_columns)) {
            $this->addColumn('id', 'integer', null, false, null, array('attribute' => 'unsigned', 'autoincrement' => 'auto_increment'));
            $this->addIndex('primary', 'id');
        }

        if (!key_exists("created_at", $this->add_columns)) {
            $this->addColumn("created_at", "datetime");
        }
        if (!key_exists("updated_at", $this->add_columns)) {
            $this->addColumn("updated_at", 'timestamp');
        }
    }

    private function runMethods() {

        $except = ($this->called_method === "migrate") ? 'down' : 'up';
        foreach ($this->methods as $method) {
            if (method_exists($this, $method) && $method != $except) {
                $this->{$method}();
            }
        }
    }

    public function __call($name, $arg) {

        if ($name == 'createTable') {
            if ($this->called_method == 'rollback') {
                $this->class = new DropTable($arg[0]);
            } else {
                $this->class = new CreateTable($arg[0]);
            }
        }

        if ($name == 'alterTable') {
            $this->class = new AlterTable($arg[0]);
        }

        if (preg_match('/(^add_)(.*)/', $name, $result)) {
            $column_name = $result[2];
            if ($this->called_method == 'rollback') {
                $this->dropColumn($column_name);
            } else {
                $type = (!empty($arg[0])) ? $arg[0] : 'string';
                $length = (!empty($arg[1])) ? $arg[1] : null;
                $nullable = (!empty($arg[2])) ? $arg[2] : false;
                $default = (!empty($arg[3])) ? $arg[3] : null;
                $options = (!empty($arg[4])) ? $arg[4] : array();
                $this->addColumn($column_name, $type, $length, $nullable, $default, $options);
            }
        }

        if ($name == 'addIndex') {

            $columns = (!empty($arg[1])) ? (array) $arg[1] : null;


            $name = (!empty($arg[2])) ? $arg[2] : $columns[0];
            $referenceTable = (!empty($arg[3])) ? $arg[3] : null;
            $referenceColumn = (!empty($arg[4])) ? $arg[4] : null;
            $onDeleteRule = (!empty($arg[5])) ? $arg[5] : null;
            $onUpdateRule = (!empty($arg[5])) ? $arg[5] : null;

            switch ($arg[0]) {
                case 'primary':
                    $this->constraints[] = new PrimaryKey($columns, $name);
                    break;
                case 'unique':
                    $this->constraints[] = new UniqueKey($columns, $name);

                    break;
                case 'foreign':
                    $this->constraints[] = new ForeignKey($name, $columns, $referenceTable, $referenceColumn, $onDeleteRule, $onUpdateRule);

                    break;
                default:
                    new \Exception('Wrong Index Type');
                    break;
            }
        }
    }

    protected function addColumn($name, $type, $length = null, $nullable = false, $default = null, array $options = array()) {

        $column = null;
        $type = strtolower($type);
        $field_type = (empty($type) || $type == 'string') ? 'varchar' : $type;


        switch ($field_type) {
            case 'biginteger':
                $column = new BigInteger($name, $nullable, $default, $options);
                break;
            case 'integer':
                $column = new Integer($name, $nullable, $default, $options);
                break;
            case 'blob':
                $column = new Blob($name, $length, $nullable, $default, $options);
                break;
            case 'varchar':
                $length = (!empty($length)) ? $length : 255;
                $column = new Varchar($name, $length, $nullable, $default, $options);
                break;
            case 'char':
                $length = (!empty($length)) ? $length : 255;
                $column = new Char($name, $length, $nullable, $default, $options);
                break;
            case 'float':
                $d = (is_scalar($length)) ? explode('.', (string) $length) : $d = array(null, null);
                $column = new Floating($name, $d[0], $d[1], $nullable, $default, $options);
                break;
            case 'decimal':
                $d = (is_scalar($length)) ? explode('.', (string) $length) : $d = array(null, null);
                $column = new Decimal($name, $d[0], $d[1], $nullable, $default, $options);
                break;
            case 'date':
                $column = new Date($name, $nullable, $default, $options);
                break;
            case 'datetime':
                $column = new Datetime($name, $nullable, $default, $options);
                break;
            case 'time':
                $column = new Time($name, $nullable, $default, $options);
                break;
            case 'timestamp':
                $column = new Timestamp($name, $nullable, $default, $options);
                break;
            case 'boolean':
                $column = new Boolean($name, $nullable, $default, $options);
                break;
            case 'text':
                $column = new Text($name, $length, $nullable, $default, $options);
                break;
            default:
                throw new \Exception('Invalide Type of Column');
        }

        $this->setColumn($name, $column);
    }

    private function dropColumn($name) {
        $this->drop_columns[] = $name;
    }

    private function setColumn($name, ColumnInterface $column) {
        $this->add_columns[$name] = $column;
    }

    private function appendColumns() {
        if (!empty($this->add_columns) && !$this->class instanceof DropTable) {
            foreach ($this->add_columns as $column) {
                $this->class->addColumn($column);
            }
        }
    }

    private function appendDropColumns() {
        if (is_array($this->drop_columns) && $this->class instanceof AlterTable) {
            foreach ($this->drop_columns as $column) {
                $this->class->dropColumn($column);
            }
        }
    }

    private function appendConstraint() {
        if (!empty($this->constraints) && !$this->class instanceof DropTable) {
            foreach ($this->constraints as $constraint) {
                $this->class->addConstraint($constraint);
            }
        }
    }

    private function setAsMigrated() {

        $config = $this->getMigrationsConfig();
        $config[$this->migrationName] = $this->migrationID;
        if (!file_exists($this->migrations)) {
            touch($this->migrations);
        }
        file_put_contents($this->migrations, "<?php return " . \ZOR\Service\AbstractService::exportConfig($config) . ";");
    }

    private function setAsRollback() {
        $config = $this->getMigrationsConfig();
        unset($config[$this->migrationName]);
        file_put_contents($this->migrations, "<?php return " . \ZOR\Service\AbstractService::exportConfig($config) . ";");
    }

    private function getMigrationsConfig() {
        if (file_exists($this->migrations)) {
            $config = require $this->migrations;
        } else {
            $config = array();
        }
        return $config;
    }

}
