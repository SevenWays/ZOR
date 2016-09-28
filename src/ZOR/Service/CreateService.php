<?php

/**
 * Copyright (c) 2014 SevenWays IT Solutions.
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
 * @version 0.0.1 from 26.04.2016
 */

namespace ZOR\Service;

class CreateService extends AbstractService {

    const APPLICATION_SKELETON_URL = 'https://github.com/zendframework/ZendSkeletonApplication/archive/master.zip';
    const MODULE_SKELETON_URL = 'https://github.com/zendframework/ZendSkeletonModule/archive/master.zip';

    protected $_filename = "application.zip";
    protected $_extractedSkeleton = null;
    protected $_modulename = null;

    public function __construct() {
        if (!extension_loaded('zip')) {
            throw new Exception('You need to install the ZIP extension of PHP');
        }
    }

    public function createApplication($filepath) {
        $this->_app_root_dir = $filepath;
        $this->_filename = $this->getModuleName(self::APPLICATION_SKELETON_URL) . ".zip";
        $this->create(self::APPLICATION_SKELETON_URL, $filepath);
        $this->includeModule('ZOR');
    }

    public function createModule($name, $filepath) {
        $this->_app_root_dir = APP_ROOT_DIR;
        $this->_filename = $this->getModuleName(self::MODULE_SKELETON_URL) . ".zip";
        $this->_modulename = strtolower($name);
        if (!file_exists($filepath . "/module")) {
            $filepath = $filepath . "/" . ucfirst($this->_modulename);
        } else {
            $filepath = $filepath . "/module/" . ucfirst($this->_modulename);
        }
        $this->create(self::MODULE_SKELETON_URL, $filepath);
        $this->includeModule(ucfirst($this->_modulename));
    }

    public function createForeignModule($gitlink, $filepath) {
        $this->_app_root_dir = $filepath;
        $this->_modulename = $this->getModuleName($gitlink);
        $this->_filename = $this->_modulename . ".zip";
        $filepath = $filepath . "/vendor/" . $this->_modulename;
        $this->create($gitlink, $filepath);
        $this->includeModule($this->_modulename);
    }

    protected function getModuleName($param) {
        $array = explode('/', $param);
        $c = count($array) - 3;
        return $array[$c];
    }

    protected function create($link, $install_dir) {

        if ($this->downloadFromGit($link)) {
            if ($this->extractFile()) {
                $this->recurse_copy($this->_extractedSkeleton, $install_dir);
                //   $this->installPhar($install_dir);
            }
        }
    }

    /**
     * From ZFTools
     * @param string $path
     */
    /* protected function installPhar($path) {
      $tmpDir = $this->getTempDir();
      $this->setMessage($path,'info');
      if (file_exists("$path/composer.phar")) {
      exec("php $path/composer.phar self-update");
      } else {
      if (!file_exists("$tmpDir/composer.phar")) {
      if (!file_exists("$tmpDir/composer_installer.php")) {
      file_put_contents(
      "$tmpDir/composer_installer.php", '?>' . file_get_contents('https://getcomposer.org/installer')
      );
      }
      exec("php $tmpDir/composer_installer.php --install-dir $tmpDir");
      }
      copy("$tmpDir/composer.phar", "$path/composer.phar");
      }
      chmod("$path/composer.phar", 0755);
      exec("php $path/composer.phar install");
      } */

    protected function extractFile() {
        $zip = new \ZipArchive();
        if ($zip->open($this->getTempDir() . "/" . $this->_filename)) {
            $stateIndex0 = $zip->statIndex(0);
            $this->_extractedSkeleton = $this->getTempDir() . '/' . rtrim($stateIndex0['name'], "/");
            if ($zip->extractTo($this->getTempDir())) {
                return TRUE;
            } else {
                throw new \Exception('Wrong Zip File');
            }
        } else {
            throw new \Exception('File not found');
        }
    }

    protected function recurse_copy($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ( $file = readdir($dir))) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if (is_dir($src . '/' . $file)) {
                    $this->recurse_copy($src . '/' . $file, $dst . '/' . $file);
                    if ($this->_filename == 'ZendSkeletonModule.zip') {
                        $this->renameModuleOrder($file, $dst);
                    }
                } else {
                    $src_n = $src . '/' . $file;
                    $dst_n = $dst . '/' . $file;

                    copy($src_n, $dst_n);
                    if ($this->_filename == 'ZendSkeletonModule.zip') {
                        $this->renameNamespaceIntoFile($dst_n);
                        $this->renameSkeletonController($file, $dst);
                    }
                }
            }
        }
        closedir($dir);
    }

    protected function renameModuleOrder($file, $dst) {
        if ($file == "ZendSkeletonModule") {
            rename($dst . '/' . $file, $dst . '/' . ucfirst($this->_modulename));
        } elseif ($file == "zend-skeleton-module") {
            rename($dst . '/' . $file, $dst . '/' . $this->_modulename);
        } elseif ($file == "skeleton") {
            rename($dst . '/' . $file, $dst . '/' . 'index');
        }
    }

    protected function renameSkeletonController($file, $dst) {
        if ($file == "SkeletonController.php") {
            rename($dst . '/' . $file, $dst . '/' . 'IndexController.php');
        }
    }

    protected function renameNamespaceIntoFile($dst) {

        $content = file_get_contents($dst);
        $content = str_replace("ZendSkeletonModule", ucfirst($this->_modulename), $content);
        $content = str_replace('module-name-here', $this->_modulename, $content);
        $content = str_replace('module-specific-root', $this->_modulename, $content);
        $content = str_replace('Skeleton', 'Index', $content);
        $content = str_replace('SkeletonController', 'IndexController', $content);
        file_put_contents($dst, $content);
    }

    protected function downloadFromGit($link) {

        $content = file_get_contents($link);
        if (!empty($content)) {
            file_put_contents($this->getTempDir() . "/" . $this->_filename, $content);
            return TRUE;
        }
        return FALSE;
    }

    protected function getTempDir() {
        return sys_get_temp_dir();
    }

    public function includeModule($name) {
        if (file_exists($this->_app_root_dir . "/config/application.config.php")) {
            $application_config = require $this->_app_root_dir . "/config/application.config.php";
            if (!in_array($name, $application_config['modules'])) {
                $application_config['modules'][] = $name;
                copy($this->_app_root_dir . "/config/application.config.php", $this->_app_root_dir . "/config/application.config.old");
                file_put_contents($this->_app_root_dir . "/config/application.config.php", "<?php return " . $this->exportConfig($application_config) . ";");
            }
        } else {
            $this->setMessage('Application config file not found', 'error');
        }
    }

}
