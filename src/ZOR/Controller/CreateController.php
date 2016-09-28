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
 * @version 0.0.1 from 26.04.2016
 */

namespace ZOR\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\Exception\RuntimeException;
use Zend\Console\ColorInterface as Color;
use ZOR\Service\GenerateService;
use ZOR\Service\CreateService;

class CreateController extends AbstractActionController {

    protected $_service;
    protected $_console;

    public function createAction() {

        $service = new CreateService();
        $filepath = (empty($this->request->getParam('path'))) ? APP_ROOT_DIR : $this->request->getParam('path');

        switch ($this->request->getParam('what')) {
            case 'module':
                if (empty($this->request->getParam('name'))) {
                    $this->showAlert(array('error' => array('Module name is empty')));
                    return;
                }
                $service->createModule($this->request->getParam('name'), $filepath);
                break;
            case 'fmodule':
                if (empty($this->request->getParam('link'))) {
                    $this->showAlert(array('error' => array('Module Git link is empty')));
                    return;
                }
                $service->createForeignModule($this->request->getParam('link'), $filepath);
                break;
            default:
                $service->createApplication($filepath);
                break;
        }

        $this->showAlert($service->getMessages());
        $this->showAlert(array('info' => array("Create " . $this->request->getParam('what') . " is completed")));

        return;
    }

    public function generateAction() {
        $service = new GenerateService();
        $module = (!empty($this->request->getParam('mname'))) ? strtolower($this->request->getParam('mname')) : 'application';
        $controllerName = strtolower($this->request->getParam('cname'));
        $actionstring = strtolower($this->request->getParam('actions'));

        $actions = explode(",", $actionstring);

        switch ($this->request->getParam('what')) {
            case 'ctrl':
                if (empty($this->request->getParam('cname'))) {
                    $this->showAlert(array('error' => array('Controller name is empty')));
                    return;
                }
                if (empty($this->request->getParam('actions'))) {
                    $this->showAlert(array('error' => array('Actions is empty')));
                    return;
                }
                $service->generateController($controllerName, $module, $actions);
                break;
            case 'act':

                if (empty($this->request->getParam('cname'))) {
                    $this->showAlert(array('error' => array('Controller name is empty')));
                    return;
                }
                if (empty($this->request->getParam('actions'))) {
                    $this->showAlert(array('error' => array('Actions is empty')));
                    return;
                }
                $service->generateActions($actions, $module, $controllerName);
                break;
                case 'model':
                    
                    $service->generateModel(strtolower($this->request->getParam('name')), $module,$this->request->getParam('columns'));
                   // $service->createTable(strtolower($this->request->getParam('name')),$this->request->getParam('columns'));
            default:
                break;
        }

        $this->showAlert($service->getMessages());
    }

    public function showAlert(array $messages) {
        $this->_console = $this->getServiceLocator()->get('console');
        if (!$this->_console instanceof Console) {
            throw new RuntimeException('Cannot obtain console adapter. Are we running in a console?');
        }

        foreach ($messages as $key => $value) {

            switch ($key) {
                case 'error':
                    $color = Color::RED;
                    break;
                case 'info':
                    $color = Color::BLUE;
                    break;
                 case 'sucesse':
                    $color = Color::GREEN;
                    break;
                default:
                    $color = Color::WHITE;
                    break;
            }

            foreach ($value as $message) {
                $this->_console->writeLine($message, $color);
            }
        }
    }

}
