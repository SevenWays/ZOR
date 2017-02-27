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
use Zend\Db\Adapter\Adapter;


class GenerateService extends AbstractService {
    /**
     * Included Traits
     */
    use Traits\DbMigration;
    protected $viewContent = "<strong>Module:</strong>        %s &raquo;
                                <strong>Controller:</strong>    %s &raquo;
                                <strong>Action:</strong>        %s";
    private $class = null;

    public function generateController($name, $module, array $actions = array()) {

        $this->normalizeNames($module, $name);

        if (file_exists($this->controllerPath)) {
            $this->setMessage('Controller already ' . $this->controllerName . ' exists', 'error');
            return;
        }

        $ctrClassName = $this->controllerName . 'Controller';
        $ctrNamespace = $this->moduleName . '\Controller';

        $this->class = $this->generateClass($ctrClassName, $ctrNamespace, 'AbstractActionController');

        $this->class->addUse('Zend\Mvc\Controller\AbstractActionController')
                ->addUse('Zend\View\Model\ViewModel');

        $this->generateActions($actions, $module, $name);

        $this->executeFileGenerate($this->controllerPath);

        $module_config = $this->loadModuleConfig();
        if (!empty($module_config)) {
            $module_config['controllers']['invokables'][$ctrNamespace . '\\' . $this->controllerName] = $ctrNamespace . '\\' . $ctrClassName;
            $this->saveModuleConfig($module_config);
        }
    }

    public function generateActions(array $actions, $module, $controller) {

        $this->normalizeNames($module, $controller);
        $this->normalizeActions($actions);

        $fromFileReflection = false;
        $methodArray = array();

        if (is_null($this->class)) {
            $fromFileReflection = $this->getControllerClass();
        }

        foreach ($this->actions as $key => $value) {
            if ($this->class->hasMethod($key . 'Action')) {
                $this->setMessage("The action $key already exists in controller $this->controllerName of module $this->moduleName.", "error");
                continue;
            }

            $methodArray[] = $this->generateMethode($key . "Action", array(), MethodGenerator::FLAG_PUBLIC, 'return new ViewModel();');
            $content = $this->generateView($this->viewContent, $this->moduleName, $this->controllerName, $value);
            $this->saveContentIntoFile($content, $this->viewFolder . '/' . $value . '.phtml');
        }

        if (!empty($methodArray)) {
            $this->class->addMethods($methodArray);
            if ($fromFileReflection) {
                $this->executeFileGenerate($this->controllerPath);
            }
        }
        return true;
    }

    private function getControllerClass() {
        if (!file_exists($this->controllerPath)) {
            throw new Exception('Controller ' . $this->controllerName . ' not exists', 'error');
        } else {
            $className = sprintf('%s\\Controller\\%sController', $this->moduleName, $this->controllerName);

            $fileReflection = new \Zend\Code\Reflection\FileReflection($this->controllerPath, true);
            $classReflection = $fileReflection->getClass($className);
            $this->class = ClassGenerator::fromReflection($classReflection);
            $this->class->addUse('Zend\Mvc\Controller\AbstractActionController')
                    ->addUse('Zend\View\Model\ViewModel')
                    ->setExtendedClass('AbstractActionController');
            return true;
        }
    }

    public function generateModel($name, $module, $columns) {
        $this->normalizeNames($module);
        $modelName = $this->underscoreToCamelCase($name);
        $namespaceName = $this->moduleName . '\Model';
        $modelPath = $this->modulePath . '/src/' . $this->moduleName . '/Model/' . $modelName . '.php';

        if (file_exists($modelPath)) {
            $this->setMessage('Model already ' . $modelName . ' exists', 'error');
            return;
        }

        $this->generateMigration('Create' . $modelName, $columns);

        $this->class = $this->generateClass($modelName, $namespaceName, 'ActiveRecord');
        $this->class->addUse('ZOR\ActiveRecord\ActiveRecord');
        $this->class->addProperty('primaryKeyColumn', 'id')
                ->addProperty('table', $modelName);

        $this->executeFileGenerate($modelPath);

        $module_config = $this->loadModuleConfig();
        if (!empty($module_config)) {
            $module_config['service_manager']['invokables'][$namespaceName . '\\' . $modelName] = $namespaceName . '\\' . $modelName;
            $module_config['service_manager']['shared'][$namespaceName . '\\' . $modelName] = false;
            $this->saveModuleConfig($module_config);
        }
    }

    private function loadModuleConfig() {
        if (file_exists($this->modulePath . "/config/module.config.php")) {
            return require $this->modulePath . "/config/module.config.php";
        } else {
            $this->setMessage('Module config file not found', 'error');
            return null;
        }
    }

    private function saveModuleConfig($config) {
        if (file_exists($this->modulePath . "/config/module.config.php")) {
            copy($this->modulePath . "/config/module.config.php", $this->modulePath . "/config/module.config.php.old");
            file_put_contents($this->modulePath . "/config/module.config.php", "<?php return " . $this->exportConfig($config) . ";");
        } else {
            $this->setMessage('Module config file not found', 'error');
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
    public function generateClass($name = null, $namespaceName = null, $extends = null, $methods = array()) {
        return new ClassGenerator($name, $namespaceName, null, $extends, array(), array(), $methods);
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

    private function executeFileGenerate($path) {
        if (empty($this->class)) {
            throw new \Exception('Class is empty');
        }

        $file = new FileGenerator(
                array(
            'classes' => array($this->class),
                )
        );
        $this->saveContentIntoFile($file->generate(), $path);
    }

}
