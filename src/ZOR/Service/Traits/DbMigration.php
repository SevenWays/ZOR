<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ZOR\Service\Traits;

/**
 * Description of DbMigration
 *
 * @author sergej
 */
trait DbMigration {

    /**
     * Return path of migrations
     * @return string
     */
    function getMigrationsPath() {
        return $this->dbDir . "/migrations";
    }

    /**
     * Set path of migrations
     * @param string $migration_path
     */
    function setMigrationsPath($migration_path = null) {
        $this->mkdir($migration_path);
        $this->dbDir = $migration_path;
    }

    /**
     * Autogenerate migration from string
     * @param string $name
     * @param string $columns //column_name1[:datatyp][:index],column_name2[:datatyp][:index]
     * @return boolean
     */
    public function generateMigration($name, $columns) {

        if ($this->hasMigrations($name))
            return false;

        $array = explode(',', $columns);

        if (is_array($array)) {

            foreach ($array as $value) {
                $args = explode(':', $value);

                $type_match = (!empty($args[1])) ? $args[1] : null;
                preg_match_all('/(\w+)|{(\S+)}/', $type_match, $match);

                $type = (isset($match[1][0])) ? ucfirst($match[1][0]) : null;
                $length = (isset($match[2][1])) ? $match[2][1] : null;
                $index = (isset($args[2])) ? $args[2] : null;

                $attributes[] = array('name' => $args[0], 'type' => $type, 'length' => $length, 'index' => $index);
            }
        }

        if (preg_match('/(^Add)(.*)(To)(.*)/', $name, $result) || preg_match('/(^Drop)(.*)(From)(.*)/', $name, $result) || preg_match('/(^Create)(.*)/', $name, $result)) {
            $body = $this->makeMethodBody($result, $attributes);
        } elseif (preg_match('/(^Drop)(.*)/', $name, $result)) {
            $body = '$this->dropTable("' . $result[2] . '");';
        }


        $this->class = $this->generateClass($name, null, 'Migration');
        $this->class->addUse('ZOR\ActiveRecord\Migration');
        $this->class->addMethod('change', array(), null, $body);

        $this->executeFileGenerate($this->getMigrationsPath() . '/' . time() . '_' . $name . '.php');

        $this->setMessage('Migration ' . $name . ' created');
        return true;
    }

    /**
     * Make method's body of change methode
     * @param array $param
     * @param array $attributes
     * @return string
     */
    private function makeMethodBody($param, $attributes) {

        if ($param[1] == 'Add' || $param[1] == 'Drop') {
            $body = '$this->alterTable("' . $this->camelCaseToUnderscore($param[4]) . '");';
            $func = strtolower($param[1]);
        }
        if ($param[1] == 'Create') {
            $body = '$this->createTable("' . $this->camelCaseToUnderscore($param[2]) . '");';
            $func = 'add';
        }


        foreach ($attributes as $value) {
            $s = (!empty($value['type'])) ? '"' . $value['type'] . '"' : null;
            $s .= (!empty($value['length'])) ? ', ' . $value['length'] : null;
            $body .= '$this->' . $func . '_' . $value['name'] . '(' . $s . ');';

            if (!empty($value['index'])) {
                $body .= '$this->addIndex("' . $value['index'] . '", "' . $value['name'] . '");';
            }
        }

        return $body;
    }

    private function hasMigrations($name = null) {
        $migrations = scandir($this->getMigrationsPath());
        foreach ($migrations as $file) {
            if (preg_match_all('/(\d+)_(\S+)(.php)/', $file, $matches) && $matches[2][0] == $name) {
                $this->setMessage('Migration ' . $name . ' already exist', 'error');
                return true;
            }
        }
        return false;
    }

    /**
     * Run or rollback of migration
     * @param string $type = migrate|rollback
     * @param  $db
     */
    public function runMigration($type, $db = null, $version = null) {

        $rb = false;
        $sort = ($type == 'rollback') ? 1 : 0;
        $migrations = scandir($this->getMigrationsPath(), $sort);

        foreach ($migrations as $file) {
            if ($file == "." || $file == "..")
                continue;

            preg_match_all('/(\d+)_(\S+)(.php)/', $file, $matches);

            if (!is_null($version) && $version != $matches[1][0] && $version != 'any')
                continue;

            require $this->getMigrationsPath() . '/' . $file;

            $class_name = '\\' . $matches[2][0];
            $a = new $class_name();
            $a->setAdapter($db);
            $a->setPathToConfigFile($this->dbDir . "/migrations.php");


            if ($type === 'migrate') {
                $this->setMessage($a->migrate());
            } elseif (!$rb && $a->isMigrated($matches[2][0])) {
                $this->setMessage($a->rollback());
                $rb = ($version == 'any') ? false : true;
            }
            $this->setMessage($a->getGeneratedSql, 'warning');
            if ($a->error) {
                $this->setMessage($a->error, 'error');
            }
        }
    }

}
