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

// log request events

    THCFrame\Events\Events::add('framework.request.request.before', function($method, $url, $parameters) use ($logger) {
        $logger->log(sprintf('framework.request.request.before: %s, %s', $method, $url));
    });

    THCFrame\Events\Events::add('framework.request.request.after', function($method, $url, $parameters, $response) use ($logger) {
        $logger->log(sprintf('framework.request.request.after: %s, %s', $method, $url));
    });

// log router events

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

    THCFrame\Events\Events::add('framework.security.authenticate.success', function($user) use ($logger) {
        $logger->log(sprintf('framework.security.authenticate.success: %s / %s / %s', $user->getId(), $user->getWholeName(), $user->getEmail()));
    });

    THCFrame\Events\Events::add('framework.security.authenticate.failure', function($user, $message) use ($logger) {
        $logger->log(sprintf('framework.security.authenticate.failure: %s / %s / %s', $user->getId(), $user->getEmail(), $message));
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