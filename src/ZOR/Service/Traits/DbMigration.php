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

    protected static $migration_path = APP_ROOT_DIR . '/data/database/migrations';


    function getMigrationsPath() {
        return self::migration_path;
    }

    function setMigrationsPath($migration_path=self::migration_path) {
        $this->mkdir($migration_path);
        $this->migration_path = $migration_path;
    }

    public function generateMigration($name, $columns) {

        if ($this->hasMigrations($name))
            return;

        $array = explode(',', $columns);

        if (is_array($array)) {

            foreach ($array as $value) {
                $args = explode(':', $value);

                if (!is_null($args[0]))
                    throw new Exception("Columnname is empty");

                $type_match = (!is_null($args[1])) ? $args[1] : null;
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
        $this->class->addMethod('change', array(), MethodGenerator::FLAG_PUBLIC, $body);

        $this->executeFileGenerate($this->getMigrationsPath() . '/' . time() . '_' . $name . '.php');

        $this->setMessage('Migration ' . $name . ' created');
    }

    private function makeMethodBody($param, $attributes) {

        if ($param[1] == 'Add' || $param[1] == 'Drop') {
            $body = '$this->alterTable("' . $param[4] . '");';
            $func = strtolower($param[1]);
        }
        if ($param[1] == 'Create') {
            $body = '$this->createTable("' . $param[2] . '");';
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
     * 
     * @param string $type = migrate|rollback
     * @param  $db
     */
    public function runMigration($type, $db = null) {
        $rb = false;
        $sort = ($type == 'rollback') ? 1 : 0;
        $migrations = scandir($this->getMigrationsPath(), $sort);

        foreach ($migrations as $file) {
            if ($file == "." || $file == "..")
                continue;

            preg_match_all('/(\d+)_(\S+)(.php)/', $file, $matches);

            require $this->getMigrationsPath() . '/' . $file;

            $class_name = '\\' . $matches[2][0];
            $a = new $class_name();
            $a->setAdapter($db);
            $a->setPathToConfigFile($this->getMigrationsPath()."/../migrations.php");


            if ($type === 'migrate') {
                $this->setMessage($a->migrate());
            } elseif (!$rb && $a->isMigrated($matches[2][0])) {
                $this->setMessage($a->rollback());
                $rb = true;
            }
        }
    }
}
