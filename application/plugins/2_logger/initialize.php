<?php

// initialize logger
if (ENV == 'dev') {
    require_once 'logger.php';

    $logger = new Logger(array(
        'file' => APP_PATH . '/application/logs/' . date('Y-m-d') . '.log'
    ));

// log cache events
    THCFrame\Events\Events::add('framework.cache.initialize.before', function($type, $options) use ($logger) {
        $logger->log(sprintf('framework.cache.initialize.before: %s', $type));
    });

    THCFrame\Events\Events::add('framework.cache.initialize.after', function($type, $options) use ($logger) {
        $logger->log(sprintf('framework.cache.initialize.after: %s', $type));
    });

// log configuration events
    THCFrame\Events\Events::add('framework.configuration.initialize.before', function($type, $options) use ($logger) {
        $logger->log(sprintf('framework.configuration.initialize.before: %s', $type));
    });

    THCFrame\Events\Events::add('framework.configuration.initialize.after', function($type, $options) use ($logger) {
        $logger->log(sprintf('framework.configuration.initialize.after: %s', $type));
    });

// log controller events
    THCFrame\Events\Events::add('framework.controller.construct.before', function($name) use ($logger) {
        $logger->log(sprintf('framework.controller.construct.before: %s', $name));
    });

    THCFrame\Events\Events::add('framework.controller.construct.after', function($name) use ($logger) {
        $logger->log(sprintf('framework.controller.construct.after: %s', $name));
    });

    THCFrame\Events\Events::add('framework.controller.render.before', function($name) use ($logger) {
        $logger->log(sprintf('framework.controller.render.before: %s', $name));
    });

    THCFrame\Events\Events::add('framework.controller.render.after', function($name) use ($logger) {
        $logger->log(sprintf('framework.controller.render.after: %s', $name));
    });

    THCFrame\Events\Events::add('framework.controller.destruct.before', function($name) use ($logger) {
        $logger->log(sprintf('framework.controller.destruct.before: %s', $name));
    });

    THCFrame\Events\Events::add('framework.controller.destruct.after', function($name) use ($logger) {
        $logger->log(sprintf('framework.controller.destruct.after: %s', $name));
    });

// log database events
    THCFrame\Events\Events::add('framework.database.initialize.before', function($type, $options) use ($logger) {
        $logger->log(sprintf('framework.database.initialize.before: %s', $type));
    });

    THCFrame\Events\Events::add('framework.database.initialize.after', function($type, $options) use ($logger) {
        $logger->log(sprintf('framework.database.initialize.after: %s', $type));
    });

    THCFrame\Events\Events::add('framework.mysqldump.create.before', function($filename) use ($logger) {
        $logger->log(sprintf('framework.mysqldump.create.before: %s', $filename));
    });

    THCFrame\Events\Events::add('framework.mysqldump.create.after', function($filename) use ($logger) {
        $logger->log(sprintf('framework.mysqldump.create.after: %s', $filename));
    });

// log logger events
    THCFrame\Events\Events::add('framework.logger.initialize.before', function($type, $options) use ($logger) {
        $logger->log(sprintf('framework.logger.initialize.before: %s', $type));
    });

    THCFrame\Events\Events::add('framework.logger.initialize.after', function($type, $options) use ($logger) {
        $logger->log(sprintf('framework.logger.initialize.after: %s', $type));
    });

// log profiler events
    THCFrame\Events\Events::add('framework.profiler.start.before', function($identifier) use ($logger) {
        $logger->log(sprintf('framework.profiler.start.before: %s', $identifier));
    });

    THCFrame\Events\Events::add('framework.profiler.start.after', function($identifier) use ($logger) {
        $logger->log(sprintf('framework.profiler.start.after: %s', $identifier));
    });
    
    THCFrame\Events\Events::add('framework.profiler.stop.before', function($identifier) use ($logger) {
        $logger->log(sprintf('framework.profiler.stop.before: %s', $identifier));
    });

    THCFrame\Events\Events::add('framework.profiler.stop.after', function($identifier) use ($logger) {
        $logger->log(sprintf('framework.profiler.stop.after: %s', $identifier));
    });

// log request events
    THCFrame\Events\Events::add('framework.request.request.before', function($method, $url, $parameters) use ($logger) {
        $logger->log(sprintf('framework.request.request.before: %s, %s', $method, $url));
    });

    THCFrame\Events\Events::add('framework.request.request.after', function($method, $url, $parameters, $response) use ($logger) {
        $logger->log(sprintf('framework.request.request.after: %s, %s', $method, $url));
    });

// log router events
    THCFrame\Events\Events::add('framework.router.findroute.checkredirect.before', function($url) use ($logger) {
        $logger->log(sprintf('framework.router.findroute.checkredirect.before: %s', $url));
    });

    THCFrame\Events\Events::add('framework.router.findroute.checkredirect.after', function($url) use ($logger) {
        $logger->log(sprintf('framework.router.findroute.checkredirect.after: %s', $url));
    });

    THCFrame\Events\Events::add('framework.router.findroute.before', function($url) use ($logger) {
        $logger->log(sprintf('framework.router.findroute.before: %s', $url));
    });

    THCFrame\Events\Events::add('framework.router.findroute.after', function($url, $module, $controller, $action) use ($logger) {
        $logger->log(sprintf('framework.router.findroute.after: %s, %s, %s, %s', $url, $module, $controller, $action));
    });

// log session events
    THCFrame\Events\Events::add('framework.session.initialize.before', function($type, $options) use ($logger) {
        $logger->log(sprintf('framework.session.initialize.before: %s', $type));
    });

    THCFrame\Events\Events::add('framework.session.initialize.after', function($type, $options) use ($logger) {
        $logger->log(sprintf('framework.session.initialize.after: %s', $type));
    });

// log module loading
    THCFrame\Events\Events::add('framework.module.initialize.before', function($name) use ($logger) {
        $logger->log(sprintf('framework.module.initialize.before: %s', $name));
    });

//THCFrame\Events\Events::add('framework.module.initialize.after', function($name) use ($logger) {
//            $logger->log(sprintf('framework.module.initialize.after: %s', $name));
//        });
// log security loading
    THCFrame\Events\Events::add('framework.security.initialize.before', function($type) use ($logger) {
        $logger->log(sprintf('framework.security.initialize.before: %s', $type));
    });

    THCFrame\Events\Events::add('framework.security.initialize.user', function($user) use ($logger) {
        $logger->log(sprintf('framework.security.initialize.user: %s / %s / %s', $user->getId(), $user->getWholeName(), $user->getEmail()));
    });

    THCFrame\Events\Events::add('framework.security.initialize.after', function($type) use ($logger) {
        $logger->log(sprintf('framework.security.initialize.after: %s', $type));
    });

    THCFrame\Events\Events::add('framework.authentication.initialize.before', function($type) use ($logger) {
        $logger->log(sprintf('framework.authentication.initialize.before: %s', $type));
    });

    THCFrame\Events\Events::add('framework.authentication.initialize.after', function($type) use ($logger) {
        $logger->log(sprintf('framework.authentication.initialize.after: %s ', $type));
    });

    THCFrame\Events\Events::add('framework.authorization.initialize.before', function($type) use ($logger) {
        $logger->log(sprintf('framework.authorization.initialize.before: %s', $type));
    });

    THCFrame\Events\Events::add('framework.authorization.initialize.after', function($type) use ($logger) {
        $logger->log(sprintf('framework.authorization.initialize.after: %s ', $type));
    });

// log view events
    THCFrame\Events\Events::add('framework.view.construct.before', function($file) use ($logger) {
        $logger->log(sprintf('framework.view.construct.before: %s', $file));
    });

    THCFrame\Events\Events::add('framework.view.construct.after', function($file, $template) use ($logger) {
        $logger->log(sprintf('framework.view.construct.after: %s', $file));
    });

    THCFrame\Events\Events::add('framework.view.render.before', function($file) use ($logger) {
        $logger->log(sprintf('framework.view.render.before: %s', $file));
    });

    THCFrame\Events\Events::add('plugin.mobiledetect.devicetype', function($type) use ($logger) {
        $logger->log(sprintf('plugin.mobiledetect.devicetype: %s', $type));
    });
}