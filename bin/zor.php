#!/usr/bin/env php
<?php
/**
 * ZendOnRail - Zend Framework 2 Modul
 */

$basePath = getcwd();
<<<<<<< HEAD
define('APP_ROOT_DIR', $basePath);
=======

>>>>>>> dbe5507dcbdd149b7791de73c37ad4978498dad4
ini_set('user_agent', 'ZendOnRail - Zend Framework 2 Modul');

// load autoloader
if (file_exists("$basePath/vendor/autoload.php")) {
    require_once "$basePath/vendor/autoload.php";
} elseif (file_exists("$basePath/init_autoloader.php")) {
    require_once "$basePath/init_autoloader.php";
} elseif (\Phar::running()) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    echo 'Error: I cannot find the autoloader of the application.' . PHP_EOL;
    echo "Check if $basePath contains a valid ZF2 application." . PHP_EOL;
    exit(2);
}

if (file_exists("$basePath/config/application.config.php")) {
    $appConfig = require "$basePath/config/application.config.php";
    if (!isset($appConfig['modules']['ZOR'])) {
        $appConfig['modules'][] = 'ZOR';
        $appConfig['module_listener_options']['module_paths']['ZOR'] = __DIR__;
    }
} else {
    $appConfig = array(
        'modules' => array(
            'ZOR',
        ),
        'module_listener_options' => array(
            'config_glob_paths'    => array(
                'config/autoload/{,*.}{global,local}.php',
            ),
            'module_paths' => array(
                '.',
                './vendor',
            ),
        ),
    );
}

Zend\Mvc\Application::init($appConfig)->run();
