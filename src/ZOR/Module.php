<?php

namespace ZOR;

define('ROOTDIR', dirname(__DIR__));

use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Db\Adapter\AdapterAwareInterface;
use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\ModuleManager\Feature\ConsoleBannerProviderInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Filter\StaticFilter;
use Zend\Validator\StaticValidator;

class Module implements ConsoleUsageProviderInterface, AutoloaderProviderInterface, ConfigProviderInterface, ConsoleBannerProviderInterface, BootstrapListenerInterface {

    public function onBootstrap(EventInterface $e) {
        $sm = $e->getApplication()->getServiceManager();

        // get filter and validator manager 
        $filterManager = $sm->get('FilterManager');
        $validatorManager = $sm->get('ValidatorManager');

        // add custom filters and validators
        StaticFilter::setPluginManager($filterManager);
        StaticValidator::setPluginManager($validatorManager);
    }

    public function getConfig() {
        return include __DIR__ . '/../../config/module.config.php';
    }

    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    function getServiceConfig() {
        return array(
            'initializers' => array(
                'service' => function($service, ServiceLocatorInterface $serviceLocator) {
                    if ($service instanceof AdapterAwareInterface) {
                        $service->setDbAdapter($serviceLocator->get("Zend\Db\Adapter\Adapter"));
                    }
                }
            )
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
            'create project [--path=]' => 'Create an application',
            array('[--path]', 'Optional if workspace differently'),
            'create module --name= [--path=]' => 'Create a module',
            array('[--name]', 'Name of Module'),
            array('[--path]', 'Optional if workspace differently'),
            'create fmodule --require= [--path=]' => 'Create a foreign module from packagist.org',
            array('[--require]', 'Package name from packagist.org'),
            array('[--path]', 'Optional if workspace differently'),
             'create database [--name=] [--driver=] [--username=] [--password=]' => 'Create a database adapter',
            array('[--name]', 'Name of tabel'),
            array('[--driver]', 'Zend Farmework supports driver'),
            array('[--username]', 'Database username'),
            array('[--password]', 'Database password'),
            'generate ctrl --name= [--module=] [--actions=]' => 'Generate a controller',
            array('[--name]', 'Name of controller'),
            array('[--module]', 'Name of module. Default: "Application"'),
            array('[--actions]', 'Names of actions. Default: "index"'),
            'generate act [--cname=] [--module=] [--actions=]' => 'Generate the actions for a controller',
            array('[--cname]', 'Name of controller'),
            array('[--module]', 'Name of module. Default:"Application"'),
            array('[--actions]', 'Names of actions. Default: "index"'),
            'generate model [--name=] [--module=] [--columns=]' => 'Generate a model with ActiveRecord pattern',
            array('[--name]', 'Name of model'),
            array('[--module]', 'Name of module. Default:"Application"'),
            array('[--columns]', 'A string of attributs'),
            'generate migration [--name=] [--columns=]' => 'Generate a migration',
            array('[--name]', 'Name of migration'),
            array('[--columns]', 'A string of attributs'),
            
            'run server [--host=] [--port=] [--path=]' => 'Run buildin PHP server',
            array('[--host]', 'Name of migration. Default: "localhost"'),
            array('[--port]', 'Port nummber. Default: "8080"'),
            array('[--path]', 'Path to index.php. Default: "/public"'),
            
            'db migrate [--version]' => 'Run migration to database',
            'db rollback [--version]' => 'Run rollback to database',
            array('[--version]', 'Version of migration. Default: any'),

            );
    }

}
