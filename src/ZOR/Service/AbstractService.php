<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ZOR\Service;

use Zend\Code\Generator\ValueGenerator;
use Zend\Filter\StaticFilter;

abstract class AbstractService {

    protected $dbDir = APP_ROOT_DIR . '/data/database';
    protected $namespace;
    protected $controllerName;
    protected $moduleName;
    protected $viewFolder;
    protected $modulePath;
    protected $controllerPath;
    protected $actions = array();
    protected $messages = array();
    private $_app_root_dir = null;
    protected static $valueGenerator;

    public function getMessages() {
        return $this->messages;
    }

    public function setMessage($msg, $type = "sucesse") {
        $this->messages[$type][] = $msg;
        return $this;
    }

    public function setAppRootDir($path = null) {
        $this->_app_root_dir = (!is_null($path)) ? $path : APP_ROOT_DIR;
    }

    public function getAppRootDir() {
        if (is_null($this->_app_root_dir)) {
            $this->setAppRootDir();
        }
        return $this->_app_root_dir;
    }

    public static function exportConfig($config, $indent = 0) {
        if (empty(static::$valueGenerator)) {
            static::$valueGenerator = new ValueGenerator();
        }
        static::$valueGenerator->setValue($config);
        static::$valueGenerator->setArrayDepth($indent);

        return static::$valueGenerator;
    }

    public function saveContentIntoFile($content, $path, $mode = 0777) {
        $this->mkdir(dirname($path), $mode);

        if (file_put_contents($path, $content) !== FALSE) {
            $this->setMessage('The file ' . $path . ' was created', 'info');
        } else {
            $this->setMessage('An error has occurred!', 'error');
        }
    }

    protected function mkdir($pathname, $mode = 0777, $recursive = true) {
        if (!file_exists($pathname)) {
            mkdir($pathname, $mode, $recursive);
        }
    }

    protected function underscoreToCamelCase($string) {
        return StaticFilter::execute($string, 'WordUnderscoreToCamelCase');
    }

    protected function camelCaseToUnderscore($string) {
        return strtolower(StaticFilter::execute($string, 'WordCamelCaseToUnderscore'));
    }

    protected function camelCaseToDash($string) {
        return strtolower(StaticFilter::execute($string, 'WordCamelCaseToDash'));
    }

    protected function dashToCamelCase($string) {
        return StaticFilter::execute($string, 'WordDashToCamelCase');
    }

    protected function normalizeNames($module = null, $controller = null) {
        if (empty($module) && empty($controller)) {
            return;
        }
        if (empty($this->controllerName)) {
            $this->controllerName = $this->underscoreToCamelCase($controller);
        }
        if (empty($this->moduleName)) {
            $this->moduleName = $this->underscoreToCamelCase($module);
        }
        if (empty($this->modulePath)) {
            $this->modulePath = $this->getAppRootDir() . '/module/' . $this->moduleName;
        }
        if (empty($this->controllerPath)) {
            $this->controllerPath = $this->modulePath . '/src/' . $this->moduleName . '/Controller/' . $this->controllerName . 'Controller.php';
        }
        if (empty($this->viewFolder)) {
            $this->viewFolder = $this->modulePath . '/view/' . $this->camelCaseToDash($this->moduleName) . '/' . $this->camelCaseToDash($this->controllerName);
        }
    }

    protected function normalizeActions(array $actions) {
        foreach ($actions as $value) {
            $key = $this->underscoreToCamelCase($value);
            $val = $this->camelCaseToDash($key);
            $this->actions[$key] = $val;
        }
    }

}
