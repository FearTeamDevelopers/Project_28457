<?php

spl_autoload_register(function($class) {
    $file = strtolower(str_replace('\\', DIRECTORY_SEPARATOR, trim($class, '\\'))) . '.php';
    $combined = '.' . DIRECTORY_SEPARATOR . $file;

    if (file_exists($combined)) {
        require_once($combined);
        return;
    } else {
        $file = strtolower(str_replace('_', DIRECTORY_SEPARATOR, trim($class, '\\'))) . '.php';
        $combined = '.' . DIRECTORY_SEPARATOR . $file;

        if (file_exists($combined)) {
            require_once($combined);
            return;
        }
    }
});

use IDS\Init;
use IDS\Monitor;

try {

    $request = array(
        'GET' => $_GET,
        'POST' => $_POST
    );

    $init = Init::init(APP_PATH . '/vendors/ids/config/config.ini');

    $init->config['General']['base_path'] = APP_PATH . '/vendors/ids/';
    $init->config['General']['use_base_path'] = true;
    $init->config['Caching']['caching'] = 'none';

    $ids = new Monitor($init);

    $result = $ids->run($request);
    if (!$result->isEmpty()) {

        $compositeLog = new IDS_Log_Composite();
        $compositeLog->addLogger(IDS_Log_File::getInstance($init));
        /*
        $compositeLog->addLogger(
            IDS_Log_Email::getInstance($init)
        );
        */
        $compositeLog->execute($result);
        
        echo 'Data which you have sent contains dangerous chars. Please delete all cookies and try it again';
        die();
    }
} catch (\Exception $e) {
    echo 'An error occured';
    exit();
}