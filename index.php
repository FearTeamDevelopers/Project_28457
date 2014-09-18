<?php

if(preg_match('#^.*\.dev$#i',$_SERVER['SERVER_NAME'])){
    defined('ENV')? null : define('ENV', 'dev');
}elseif(preg_match('#^.*\.fear-team\.cz$#i', $_SERVER['SERVER_NAME'])){
    defined('ENV')? null : define('ENV', 'qa');
}else{
    defined('ENV')? null : define('ENV', 'live');
}

defined('APP_PATH')? null : define('APP_PATH', __DIR__);

if (ENV == 'dev') {
    error_reporting(E_ALL || E_STRICT);
} else {
    error_reporting(0);
}

if (version_compare(phpversion(), '5.4', '<')) {
    header('Content-type: text/html');
    include(APP_PATH . '/phpversion.phtml');
    exit();
}

// core
require('./vendors/thcframe/core/core.php');
THCFrame\Core\Core::initialize();

// plugins
$path = APP_PATH . '/application/plugins';
$iterator = new \DirectoryIterator($path);

foreach ($iterator as $item) {
    if (!$item->isDot() && $item->isDir()) {
        include($path . '/' . $item->getFilename() . '/initialize.php');
    }
}

//module loading
$modules = array('App');
THCFrame\Core\Core::registerModules($modules);

$profiler = THCFrame\Profiler\Profiler::getProfiler();
$profiler->start();

// load services and run dispatcher
THCFrame\Core\Core::run();

$profiler->end();
