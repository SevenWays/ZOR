#!/usr/bin/env php
<?php
/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
/* chdir(dirname(__DIR__));

  // Decline static file requests back to the PHP built-in webserver
  if (php_sapi_name() === 'cli-server') {
  $path = realpath(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
  if (__FILE__ !== $path && is_file($path)) {
  return false;
  }
  unset($path);
  }

  // Setup autoloading
  require 'init_autoloader.php';

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

  // Run the application!
  Zend\Mvc\Application::init($appConfig)->run(); */

//#!/usr/bin/env php
?>
<?php
/**
 * ZF2 command line tool
 *
 * @link      http://github.com/zendframework/ZFTool for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
chdir('../..');
define('APP_ROOT_DIR', getcwd());

ini_set('user_agent', 'ZOR - ZendOnRails command line tool');
echo APP_ROOT_DIR;
// load autoloader
if (file_exists(APP_ROOT_DIR . "/vendor/autoload.php")) {
    require_once APP_ROOT_DIR . "/vendor/autoload.php";
} elseif (file_exists(APP_ROOT_DIR . "/init_autoloader.php")) {
    require_once APP_ROOT_DIR . "/init_autoloader.php";
} elseif (\Phar::running()) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    echo 'Error: I cannot find the autoloader of the application.' . PHP_EOL;
    echo "Check if " . APP_ROOT_DIR . " contains a valid ZF2 application." . PHP_EOL;
    exit(2);
}

/* if (file_exists("$basePath/config/application.config.php")) {
  $appConfig = require "$basePath/config/application.config.php";
  if (!isset($appConfig['modules']['ZFTool'])) {
  $appConfig['modules'][] = 'ZFTool';
  $appConfig['module_listener_options']['module_paths']['ZFTool'] = __DIR__;
  }
  } else { */
$appConfig = array(
    'modules' => array(
        'ZOR'
    ),
    'module_listener_options' => array(
        'config_glob_paths' => array(
            'config/autoload/{,*.}{global,local}.php',
        ),
        'module_paths' => array(
            '.',
            './vendor',
        ),
    ),
);
//}

Zend\Mvc\Application::init($appConfig)->run();
