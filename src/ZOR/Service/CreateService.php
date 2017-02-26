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

    const APPLICATION_SKELETON_URL = 'https://github.com/zendframework/ZendSkeletonApplication/archive/release-2.5.0.zip';
    const MODULE_SKELETON_URL = 'https://github.com/zendframework/ZendSkeletonModule/archive/2.0.0.zip';

    protected $_filename = "application.zip";
    protected $_extractedSkeleton = null;

    public function __construct() {
        if (!extension_loaded('zip')) {
            throw new Exception('You need to install the ZIP extension of PHP');
        }
    }

    public function createApplication($filepath) {

        $this->setAppRootDir($filepath);

        if ($this->moduleExist("Application")) {
            $this->setMessage('Application is already installed', 'error');
            return FALSE;
        }

        $this->installPhar($this->getAppRootDir());
        exec("php " . $this->getAppRootDir() . "/composer.phar create-project -sdev zendframework/skeleton-application:2.5.0 ");
        exec("cp -r " . $this->getAppRootDir() . "/skeleton-application/* " . $this->getAppRootDir());
        exec("rm -r " . $this->getAppRootDir() . "/skeleton-application");
        exec("php " . $this->getAppRootDir() . "/composer.phar --working-dir=" . $this->getAppRootDir() . " require sevenways/zor:dev-master");
        $this->includeModule('ZOR');
        $this->createDBConnect();
        $this->setMessage("Create Application is completed");
    }

    public function createModule($name, $filepath) {
        $this->normalizeNames($name);
        $this->setAppRootDir($filepath);

        if ($this->moduleExist($this->moduleName)) {
            $this->setMessage('Module is already ' . $this->moduleName . 'exist', 'error');
            return FALSE;
        }

        $this->_filename = "ZendSkeletonModule.zip";

        $this->create(self::MODULE_SKELETON_URL, $this->getAppRootDir() . "/module/" . $this->moduleName);
        $this->includeModule($this->moduleName);
        $this->setMessage("Create module " . $this->moduleName . " is completed");
    }

    public function createForeignModule($require, $filepath = null) {

        $this->setAppRootDir($filepath);
        $array = explode('/', $require);
        $modulename = $this->dashToCamelCase(array_pop($array));

        if ($this->moduleExist($modulename)) {
            $this->setMessage('Module is already ' . $modulename . 'exist', 'error');
            return FALSE;
        }

        if ($this->installPhar($this->getAppRootDir())) {
            exec("php " . $this->getAppRootDir() . "/composer.phar --working-dir=" . $this->getAppRootDir() . " require " . $require);
        }
        $this->includeModule($modulename);
        $this->setMessage("Create module " . $modulename . " is completed");
    }

    protected function create($link, $install_dir) {

        if ($this->downloadFromGit($link)) {
            if ($this->extractFile()) {
                $this->recurse_copy($this->_extractedSkeleton, $install_dir);
            }
        }
    }

    protected function moduleExist($name) {
        if (file_exists($this->getAppRootDir() . "/module/" . $name)) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * From ZFTool
     * @param string $path
     */
    protected function installPhar($path) {
        $tmpDir = $this->getTempDir();
        $this->setMessage($path, 'info');
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
        return true;
    }

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
            rename($dst . '/' . $file, $dst . '/' . $this->moduleName);
        } elseif ($file == "zend-skeleton-module") {
            rename($dst . '/' . $file, $dst . '/' . $this->camelCaseToDash($this->moduleName));
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
        $content = str_replace("ZendSkeletonModule", $this->moduleName, $content);
        $content = str_replace('module-name-here', $this->camelCaseToDash($this->moduleName), $content);
        $content = str_replace('module-specific-root', $this->camelCaseToDash($this->moduleName), $content);
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
        if (file_exists($this->getAppRootDir() . "/config/application.config.php")) {
            $application_config = require $this->getAppRootDir() . "/config/application.config.php";
            if (!in_array($name, $application_config['modules'])) {
                $application_config['modules'][] = $name;
                copy($this->getAppRootDir() . "/config/application.config.php", $this->getAppRootDir() . "/config/application.config.old");
                file_put_contents($this->getAppRootDir() . "/config/application.config.php", "<?php return " . $this->exportConfig($application_config) . ";");
            }
        } else {
            $this->setMessage('Application config file not found', 'error');
        }
    }

    public function createDBConnect($driver = null, $database = null, $username = null, $password = null) {
        $this->mkdir($this->dbDir . "/migrations");
        if ($driver == null || strtolower($driver) == 'sqlite') {
            $this->createSQLiteDbConnect($database);
        } else {
            $config = array(
                'driver' => $driver,
                'database' => $database,
                'username' => $username,
                'password' => $password
            );
            $this->setDBAdapterConfig($config);
        }
    }

    protected function createSQLiteDbConnect($database) {
        $dataname = (!is_null($database)) ? $database : 'sqlite';
        $dbFile = $this->dbDir ."/". $dataname . ".db";
        $this->saveContentIntoFile("", $dbFile);
        chmod($dbFile, 0666);
        chgrp($dbFile, "www-data");
        chgrp(dirname($dbFile), "www-data");

        $config = array(
            'driver' => 'Pdo_Sqlite',
            'database' => $dbFile
        );

        $this->setDBAdapterConfig($config);
    }

    protected function setDBAdapterConfig($config) {
        $globalConfigFile = $this->getAppRootDir() . "/config/autoload/global.php";

        if (file_exists($globalConfigFile)) {

            $global_config = require $globalConfigFile;

            $global_config['db'] = $config;

            $global_config['service_manager'] = array(
                'factories' => array(
                    'Zend\Db\Adapter\Adapter'
                    => 'Zend\Db\Adapter\AdapterServiceFactory',
                ),
            );

            copy($globalConfigFile, $globalConfigFile . ".old");
            file_put_contents($globalConfigFile, "<?php return " . $this->exportConfig($global_config) . ";");
        } else {
            $this->setMessage('Global config file not found', 'error');
        }
    }

}
