<?php

namespace ZOR;

define('ROOTDIR', dirname(__DIR__));

use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\ModuleManager\Feature\ConsoleBannerProviderInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module implements ConsoleUsageProviderInterface,
    AutoloaderProviderInterface,
    ConfigProviderInterface,
    ConsoleBannerProviderInterface,
    BootstrapListenerInterface{
 /**
     * @var ServiceLocatorInterface
     */
    protected $sm;

    public function onBootstrap(EventInterface $e)
    {
        $this->sm = $e->getApplication()->getServiceManager();
    }

    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    /**
     * This method is defined in ConsoleBannerProviderInterface
     */
    public function getConsoleBanner(Console $console) {
        return 'Zend On Rail by Sergej Hoffmann 0.0.1';
    }

    public function getConsoleUsage(Console $console) {
        return array(
            'create application [--path=]' => 'Create an application',
            array('[--path]', 'if workspace differently'),
            'create module --name= [--path=]' => 'Create a Module',
            array('[--name]', 'Name of Module'),
            array('[--path]', 'if workspace differently'),
            'create foreignmodule --link= [--path=]' => 'Create a foreign module from GitHub zip link',
            array('[--link]', 'Zip file on GitHub'),
            array('[--path]', 'if workspace differently'),
            'generate ctrl --name= [--module=] [--actions=]' => 'Generate a Controller',
             array('[--name]', 'Name of Controller'),
            array('[--module]', 'Name of Module. Default:"Application"'),
             array('[--actions]', 'Names of Actions. Default: "index"'),
            'generate act [--ctrl=] [--module=] [--actions=]' => 'Generate the Actions for a Controller',
            array('[--cname]', 'Name of Controller'),
            array('[--module]', 'Name of Module. Default:"Application"'),
            'generate model [--name=] [--module=] [--columns=]' => 'Generate a Model with ActiveRecords',
            array('[--name]', 'Name of Model'),
            array('[--module]', 'Name of Module. Default:"Application"'),
             array('[--columns]', 'A string of attributs'),
            );
    }

}
