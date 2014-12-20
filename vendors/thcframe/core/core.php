<?php

namespace THCFrame\Core;

use THCFrame\Core\Exception as Exception;
use THCFrame\Registry\Registry;
use THCFrame\Core\Autoloader;
use THCFrame\Logger\Logger;

/**
 * THCFrame core class
 */
class Core
{

    /**
     * Logger instance
     * 
     * @var THCFrame\Logger\Logger
     */
    private static $_logger;

    /**
     * Autoloader instance
     * 
     * @var THCFrame\Core\Autoloader 
     */
    private static $_autoloader;

    /**
     * Registered modules
     * 
     * @var array 
     */
    private static $_modules = array();

    /**
     * List of exceptions
     * 
     * @var array 
     */
    private static $_exceptions = array(
        '401' => array(
            'THCFrame\Security\Exception\Role',
            'THCFrame\Security\Exception\Unauthorized',
            'THCFrame\Security\Exception\UserExpired',
            'THCFrame\Security\Exception\UserInactive',
            'THCFrame\Security\Exception\UserPassExpired',
            'THCFrame\Security\Exception\CSRF',
            'THCFrame\Security\Exception\WrongPassword',
            'THCFrame\Security\Exception\UserNotExists',
            'THCFrame\Security\Exception\BruteForceAttack'
        ),
        '404' => array(
            'THCFrame\Router\Exception\Module',
            'THCFrame\Router\Exception\Action',
            'THCFrame\Router\Exception\Controller'
        ),
        '500' => array(
            'THCFrame\Cache\Exception',
            'THCFrame\Cache\Exception\Argument',
            'THCFrame\Cache\Exception\Implementation',
            'THCFrame\Configuration\Exception',
            'THCFrame\Configuration\Exception\Argument',
            'THCFrame\Configuration\Exception\Implementation',
            'THCFrame\Configuration\Exception\Syntax',
            'THCFrame\Controller\Exception',
            'THCFrame\Controller\Exception\Argument',
            'THCFrame\Controller\Exception\Implementation',
            'THCFrame\Core\Exception',
            'THCFrame\Core\Exception\Argument',
            'THCFrame\Core\Exception\Implementation',
            'THCFrame\Core\Exception\Property',
            'THCFrame\Core\Exception\ReadOnly',
            'THCFrame\Core\Exception\WriteOnly',
            'THCFrame\Database\Exception',
            'THCFrame\Database\Exception\Argument',
            'THCFrame\Database\Exception\Implementation',
            'THCFrame\Database\Exception\Sql',
            'THCFrame\Logger\Exception',
            'THCFrame\Logger\Exception\Argument',
            'THCFrame\Logger\Exception\Implementation',
            'THCFrame\Model\Exception',
            'THCFrame\Model\Exception\Argument',
            'THCFrame\Model\Exception\Connector',
            'THCFrame\Model\Exception\Implementation',
            'THCFrame\Model\Exception\Primary',
            'THCFrame\Model\Exception\Type',
            'THCFrame\Model\Exception\Validation',
            'THCFrame\Module\Exception\Multiload',
            'THCFrame\Module\Exception\Implementation',
            'THCFrame\Module\Exception',
            'THCFrame\Profiler\Exception',
            'THCFrame\Profiler\Exception\Disabled',
            'THCFrame\Request\Exception',
            'THCFrame\Request\Exception\Argument',
            'THCFrame\Request\Exception\Implementation',
            'THCFrame\Request\Exception\Response',
            'THCFrame\Router\Exception',
            'THCFrame\Router\Exception\Argument',
            'THCFrame\Router\Exception\Implementation',
            'THCFrame\Rss\Exception',
            'THCFrame\Rss\Exception\InvalidDetail',
            'THCFrame\Rss\Exception\InvalidItem',
            'THCFrame\Security\Exception',
            'THCFrame\Security\Exception\Implementation',
            'THCFrame\Security\Exception\HashAlgorithm',
            'THCFrame\Session\Exception',
            'THCFrame\Session\Exception\Argument',
            'THCFrame\Session\Exception\Implementation',
            'THCFrame\Template\Exception',
            'THCFrame\Template\Exception\Argument',
            'THCFrame\Template\Exception\Implementation',
            'THCFrame\Template\Exception\Parser',
            'THCFrame\View\Exception',
            'THCFrame\View\Exception\Argument',
            'THCFrame\View\Exception\Data',
            'THCFrame\View\Exception\Implementation',
            'THCFrame\View\Exception\Renderer',
            'THCFrame\View\Exception\Syntax'
        ),
        '503' => array(
            'THCFrame\Database\Exception\Service',
            'THCFrame\Cache\Exception\Service'
        ),
        '507' => array(
            'THCFrame\Router\Exception\Offline'
        )
    );

    private function __construct()
    {
        
    }

    private function __clone()
    {
        
    }

    /**
     * 
     * @param type $array
     * @return type
     */
    private static function _clean($array)
    {
        if (is_array($array)) {
            return array_map(__CLASS__ . '::_clean', $array);
        }
        return htmlentities(trim($array), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Error handler
     * 
     * @param type $number
     * @param type $text
     * @param type $file
     * @param type $row
     */
    public static function _errorHandler($number, $text, $file, $row)
    {
        switch ($number) {
            case E_WARNING: case E_USER_WARNING :
                $type = 'Warning';
                break;
            case E_NOTICE: case E_USER_NOTICE:
                $type = 'Notice';
                break;
            default:
                $type = 'Error';
                break;
        }

        $file = basename($file);
        $message = "{$type} ~ {$file} ~ {$row} ~ {$text}";

        if (self::$_logger instanceof \THCFrame\Logger\Driver) {
            self::$_logger->log($message);
        } else {
            file_put_contents(APP_PATH . '/application/logs/error.log', $message . PHP_EOL);
        }
    }

    /**
     * Exception handler
     * 
     * @param Exception $exception
     */
    public static function _exceptionHandler(\Exception $exception)
    {
        $type = get_class($exception);
        $file = $exception->getFile();
        $row = $exception->getLine();
        $text = $exception->getMessage();

        $message = "Uncaught exception: {$type} ~ {$file} ~ {$row} ~ {$text}" . PHP_EOL;
        $message .= $exception->getTraceAsString();

        if (self::$_logger instanceof \THCFrame\Logger\Driver) {
            self::$_logger->log($message);
        } else {
            file_put_contents(APP_PATH . '/application/logs/error.log', $message . PHP_EOL);
        }
    }

    /**
     * Generates new application secret which is used is hashing
     * functions. Can be used only in dev env
     * 
     * @return string
     */
    public static function generateSecret()
    {
        if (ENV == 'dev') {
            return substr(rtrim(base64_encode(md5(microtime())), "="), 5, 25);
        } else {
            return null;
        }
    }

    /**
     * Return logger instance
     * 
     * @return THCFrame\Logger\Logger
     */
    public static function getLogger()
    {
        return self::$_logger;
    }

    /**
     * Main framework initialization method
     * 
     * @return type
     * @throws Exception
     */
    public static function initialize()
    {
        if (!defined('APP_PATH')) {
            throw new Exception('APP_PATH not defined');
        }

        // fix extra backslashes in $_POST/$_GET
        if (get_magic_quotes_gpc()) {
            $globals = array('_POST', '_GET', '_COOKIE', '_REQUEST', '_SESSION');

            foreach ($globals as $global) {
                if (isset($GLOBALS[$global])) {
                    $GLOBALS[$global] = self::_clean($GLOBALS[$global]);
                }
            }
        }

        // Autoloader
        $prefixes = array(
            APP_PATH . DIRECTORY_SEPARATOR . 'vendors',
            APP_PATH . DIRECTORY_SEPARATOR . 'application',
            APP_PATH . DIRECTORY_SEPARATOR . 'modules',
            APP_PATH . DIRECTORY_SEPARATOR . 'public',
        );

        require_once APP_PATH . '/vendors/thcframe/core/autoloader.php';
        self::$_autoloader = new Autoloader();
        self::$_autoloader->addPrefixes($prefixes);
        self::$_autoloader->register();

        // Logger
        $logger = new Logger();
        self::$_logger = $logger->initialize();

        // error and exception handlers
        set_error_handler(__CLASS__ . '::_errorHandler');
        set_exception_handler(__CLASS__ . '::_exceptionHandler');

        try {
            // configuration
            $configuration = new \THCFrame\Configuration\Configuration(
                    array('type' => 'ini', 'options' => array('env' => ENV))
            );
            $confingInitialized = $configuration->initialize();
            Registry::set('config', $confingInitialized);

            // database
            if (Registry::get('configuration')->database->host != '') {
                $database = new \THCFrame\Database\Database();
                $initializedDb = $database->initialize();
                Registry::set('database', $initializedDb);
                $initializedDb->connect();
                
                //extend configuration for config loaded from db
                $confingInitialized->extendForDbConfig();
            }

            // cache
            $cache = new \THCFrame\Cache\Cache();
            Registry::set('cache', $cache->initialize());

            // session
            $session = new \THCFrame\Session\Session();
            Registry::set('session', $session->initialize());

            // security
            $security = new \THCFrame\Security\Security();
            Registry::set('security', $security->initialize());

            // unset globals
            unset($configuration);
            unset($database);
            unset($cache);
            unset($session);
            unset($security);
        } catch (\Exception $e) {
            $exception = get_class($e);

            // attempt to find the approapriate error template, and render
            foreach (self::$_exceptions as $template => $classes) {
                foreach ($classes as $class) {
                    if ($class == $exception) {
                        $defaultErrorFile = MODULES_PATH . "/app/view/errors/{$template}.phtml";

                        http_response_code($template);
                        header('Content-type: text/html');
                        include($defaultErrorFile);
                        exit();
                    }
                }
            }

            // render fallback template
            http_response_code(500);
            header('Content-type: text/html');
            echo 'An error occurred.';
            if (ENV == 'dev') {
                print_r($e);
            }
            exit();
        }
    }

    /**
     * Register new modules within application. 
     * As parameter is given an array with module names
     * 
     * @param array $moduleArray
     */
    public static function registerModules(array $moduleArray)
    {
        foreach ($moduleArray as $moduleName) {
            self::registerModule($moduleName);
        }
    }

    /**
     * Register single module based on provided module name.
     * Module instance is created and stored in _modules array
     * 
     * @throws \THCFrame\Module\Exception\Multiload
     */
    public static function registerModule($moduleName)
    {
        if (array_key_exists(ucfirst($moduleName), self::$_modules)) {
            throw new \THCFrame\Module\Exception\Multiload(sprintf('Module %s has been alerady loaded', ucfirst($moduleName)));
        } else {
            $moduleClass = ucfirst($moduleName) . '_Etc_Module';

            try {
                $moduleObject = new $moduleClass();
                $moduleObjectName = ucfirst($moduleObject->getModuleName());
                self::$_modules[$moduleObjectName] = $moduleObject;
            } catch (Exception $e) {
                
            }
        }
    }

    /**
     * Return instance of registered module based on provided module name
     * 
     * @param string $moduleName
     * @return null | THCFrame\Module\Module
     */
    public static function getModule($moduleName)
    {
        $moduleName = ucfirst($moduleName);

        if (array_key_exists($moduleName, self::$_modules)) {
            return self::$_modules[$moduleName];
        } else {
            return null;
        }
    }

    /**
     * Return array with registered modules
     * 
     * @return null | array
     */
    public static function getModules()
    {
        if (empty(self::$_modules)) {
            return null;
        } else {
            return self::$_modules;
        }
    }

    /**
     * Return registered module names
     * 
     * @return null | array
     */
    public static function getModuleNames()
    {
        if (empty(self::$_modules)) {
            return null;
        } else {
            $moduleNames = array();

            foreach (self::$_modules as $module) {
                $moduleNames[] = $module->getModuleName();
            }

            return $moduleNames;
        }
    }

    /**
     * Initialize router and dispatcher and dispatch request.
     * If there is some error method tries to find and render error template
     */
    public static function run()
    {
        try {
            //router
            $router = new \THCFrame\Router\Router(array(
                'url' => urldecode($_SERVER['REQUEST_URI'])
            ));
            Registry::set('router', $router);

            //dispatcher
            $dispatcher = new \THCFrame\Router\Dispatcher();
            Registry::set('dispatcher', $dispatcher->initialize());

            $dispatcher->dispatch($router->getLastRoute());

            unset($router);
            unset($dispatcher);
        } catch (\Exception $e) {
            $exception = get_class($e);

            // attempt to find the approapriate error template, and render
            foreach (self::$_exceptions as $template => $classes) {
                foreach ($classes as $class) {
                    if ($class == $exception) {
                        $defaultErrorFile = MODULES_PATH . "/app/view/errors/{$template}.phtml";

                        http_response_code($template);
                        header('Content-type: text/html');
                        include($defaultErrorFile);
                        exit();
                    }
                }
            }

            // render fallback template
            http_response_code(500);
            header('Content-type: text/html');
            echo 'An error occurred.';
            if (ENV == 'dev') {
                print_r($e);
            }
            exit();
        }
    }

    /**
     * Return framework version
     * 
     * @return string
     */
    public static function getFrameworkVersion()
    {
        return '1.1.3';
    }

}
