<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ZOR\Service;

use Zend\Code\Generator\ValueGenerator;

abstract class AbstractService {

    protected $messages = array();
    protected $_app_root_dir = null;
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
        $dir = dirname($path);
        if (!file_exists($dir)) {
            mkdir($dir, $mode, true);
        }

        if (file_put_contents($path, $content) !== FALSE) {
            $this->setMessage('The file ' . $path . ' was created', 'info');
        } else {
            $this->setMessage('An error has occurred!', 'error');
        }
    }

}
