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

use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\FileGenerator;
use ZOR\Db\Sql\Ddl\CreateNewTable;
use Zend\Db\Adapter\Adapter;

class GenerateService extends AbstractService {

    protected $viewContent = "<strong>Module:</strong>        %s &raquo;
                                <strong>Controller:</strong>    %s &raquo;
                                <strong>Action:</strong>        %s";
    private $class = null;

    public function generateController($name, $module, array $actions = array()) {
        $modulName = $this->getAppRootDir() . '/module/' . ucfirst($module);
        $ctrlPath = $modulName . '/src/' . ucfirst($module) . '/Controller/' . ucfirst($name) . 'Controller.php';

        if (file_exists($ctrlPath)) {
            $this->setMessage('Controller ' . ucfirst($name) . ' exists', 'error');
            return;
        }

        $ctrlName = ucfirst($name) . 'Controller';
        $namespaceName = ucfirst($module) . '\Controller';

        $this->class = $this->generateClass($ctrlName, $namespaceName);

        $this->class->addUse('Zend\Mvc\Controller\AbstractActionController')
                ->addUse('Zend\View\Model\ViewModel')
                ->setExtendedClass('AbstractActionController');

        $this->generateActions($actions, $module, $name);


        $file = new FileGenerator(
                array(
            'classes' => array($this->class),
                )
        );

        $this->saveContentIntoFile($file->generate(), $ctrlPath);



        if (file_exists($modulName . "/config/module.config.php")) {
            $module_config = require $modulName . "/config/module.config.php";
            $module_config['controllers']['invokables'][$namespaceName . '\\' . ucfirst($name)] = $namespaceName . '\\' . $ctrlName;
            copy($modulName . "/config/module.config.php", $modulName . "/config/module.config.php.old");
            file_put_contents($modulName . "/config/module.config.php", "<?php return " . $this->exportConfig($module_config) . ";");
        } else {
            $this->setMessage('Module config file not found', 'error');
        }
    }

    public function generateActions(array $actions, $module, $controller) {
        $fromFileReflection = false;
        $methodArray = array();
        $class = null;

        $ctrlPath = $this->getAppRootDir() . '/module/' . ucfirst($module) . '/src/' . ucfirst($module) . '/Controller/' . ucfirst($controller) . 'Controller.php';
        $viewPath = $this->getAppRootDir() . '/module/' . ucfirst($module) . '/view/' . $module . '/' . $controller . '/';

        if (is_null($this->class)) {
            if (!file_exists($ctrlPath)) {
                $this->setMessage('Controller ' . $controller . ' not exists', 'error');
                return false;
            } else {
                $class = sprintf('%s\\Controller\\%sController', ucfirst($module), ucfirst($controller));

                $fileReflection = new \Zend\Code\Reflection\FileReflection($ctrlPath, true);
                $classReflection = $fileReflection->getClass($class);
                $this->class = ClassGenerator::fromReflection($classReflection);
                $this->class->addUse('Zend\Mvc\Controller\AbstractActionController')
                        ->addUse('Zend\View\Model\ViewModel')
                        ->setExtendedClass('AbstractActionController');
                $fromFileReflection = true;
            }
        }


        foreach ($actions as $value) {
            if ($this->class->hasMethod($value . 'Action')) {
                $this->setMessage("The action $value already exists in controller $controller of module $module.", "error");
                continue;
            }

            $methodArray[] = $this->generateMethode(strtolower($value) . "Action", array(), MethodGenerator::FLAG_PUBLIC, 'return new ViewModel();');
            $content = $this->generateView($this->viewContent, $module, $controller, $value);
            $this->saveContentIntoFile($content, $viewPath . $value . '.phtml');
        }

        if (!empty($methodArray)) {
            $this->class->addMethods($methodArray);

            if ($fromFileReflection) {
                $file = new FileGenerator(
                        array(
                    'classes' => array($this->class),
                        )
                );

                $this->saveContentIntoFile($file->generate(), $ctrlPath);
            }
        }


        return true;
    }

    public function generateModel($name, $module, $columns, $dbadapter) {
        
        $modulPath = $this->getAppRootDir() . '/module/' . ucfirst($module);
        $modelPath = $modulPath . '/src/' . ucfirst($module) . '/Model/' . ucfirst($name) . '.php';

        if (file_exists($modelPath)) {
            $this->setMessage('Model ' . ucfirst($name) . ' exists', 'error');
            return;
        }

        $modelName = ucfirst($name);
        $namespaceName = ucfirst($module) . '\Model';
        
        $ct = $this->createTable($modelName, $columns, $dbadapter);

        $this->class = $this->generateClass($modelName, $namespaceName);

        $this->class->addUse('ZOR\ActiveRecord\ActiveRecord')
                ->setExtendedClass('ActiveRecord');

        $this->class->addProperty('primaryKeyColumn', $ct->getPrimaryKeyColumn())
                ->addProperty('table', $ct->getTableName());
        
        $file = new FileGenerator(
                array(
            'classes' => array($this->class),
                )
        );

        $this->saveContentIntoFile($file->generate(), $modelPath);


        if (file_exists($modulPath . "/config/module.config.php")) {
            $module_config = require $modulPath . "/config/module.config.php";
            $module_config['service_manager']['invokables'][$namespaceName . '\\' . $modelName] = $namespaceName . '\\' . $modelName;
            copy($modulPath . "/config/module.config.php", $modulPath . "/config/module.config.php.old");
            file_put_contents($modulPath . "/config/module.config.php", "<?php return " . $this->exportConfig($module_config) . ";");
        } else {
            $this->setMessage('Module config file not found', 'error');
        }
    }

    /*
      order,id:integer{11}:unsigned:notnull::auto_increment:primerykey,uniqid:varchar{255}::null:aaa::uniquekey,amount:float{10.4}::notnull:::,created_at:timestamp::null::on_update::foreignkey{name.referenceTable.referenceColumn.onDeleteRule.onUpdateRule}
     */

    protected function createTable($name, $columns,  $dbadapter) {
        try {
       
            $ct = new CreateNewTable($dbadapter);
            $ct->createTableFromString($name, $columns);
            $this->setMessage($ct->createTable());
            return $ct;
        } catch (Exception $exc) {
            $this->setMessage($exc->getTraceAsString());
        }
    }

    /**
     * Implement sprintf function
     * 
     * @param string $content
     * @param mixed $args
     * @return string
     */
    public function generateView($content, $args = null) {
        $args = func_get_args();
        $query = call_user_func_array('sprintf', $args);
        return $query;
    }

    /**
     * 
     * @param type $name
     * @param type $namespaceName
     * @param type $methods
     * @return ClassGenerator
     */
    public function generateClass($name = null, $namespaceName = null, $methods = array()) {

        return new ClassGenerator($name, $namespaceName, null, null, array(), array(), $methods);
    }

    /**
     * 
     * @param type $name
     * @param type $parameters
     * @param type $flags
     * @param type $body
     * @return MethodGenerator
     */
    public function generateMethode($name, $parameters = array(), $flags = null, $body = null) {
        return new MethodGenerator($name, $parameters, $flags, $body);
    }

}
