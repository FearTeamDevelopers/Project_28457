<?php

namespace THCFrame\Core;

/**
 * Autoloade class
 */
class Autoloader
{

    /**
     * Path prefixes
     * 
     * @var array 
     */
    private $_prefixes = array();

    /**
     * Fallback dirs
     * 
     * @var array 
     */
    private $_fallbackDirs = array();

    /**
     * 
     * @var boolean 
     */
    private $_useIncludePath = false;

    /**
     * Already loaded classes
     * 
     * @var array 
     */
    private $_loadedClass = array();

    /**
     * Adds prefixes
     *
     * @param array $prefixes Prefixes to add
     */
    public function addPrefixes(array $prefixes)
    {
        foreach ($prefixes as $prefix => $path) {
            $this->addPrefix($prefix, $path);
        }
    }

    /**
     * Registers a set of classes
     *
     * @param string       $prefix The classes prefix
     * @param array|string $paths  The location(s) of the classes
     */
    public function addPrefix($prefix, $paths)
    {
        if (!$prefix) {
            foreach ((array) $paths as $path) {
                $this->_fallbackDirs[] = $path;
            }

            return;
        }
        if (isset($this->_prefixes[$prefix])) {
            $this->_prefixes[$prefix] = array_merge(
                    $this->_prefixes[$prefix], (array) $paths
            );
        } else {
            $this->_prefixes[$prefix] = (array) $paths;
        }
    }

    /**
     * Registers this instance as an autoloader
     *
     * @param Boolean $prepend Whether to prepend the autoloader or not
     */
    public function register($prepend = false)
    {
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);
    }

    /**
     * Unregisters this instance as an autoloader
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * Loads the given class or interface
     *
     * @param string $class The name of the class
     * @return Boolean|null True, if loaded
     */
    public function loadClass($class)
    {
        if (strpos($class, 'Swift_') !== false) {
            return;
        }

        $file = $this->findFile($class);
        if ($file !== false) {
            require $file;

            return true;
        } else {
            throw new \Exception(sprintf('%s not found', $class));
        }
    }

    /**
     * Finds the path to the file where the class is defined
     *
     * @param string $class The name of the class
     * @return string|null The path, if found
     */
    public function findFile($class)
    {
        if (false !== $pos = strrpos($class, '\\')) {
            // namespaced class name
            $classPath = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 0, $pos)) . DIRECTORY_SEPARATOR;
            $className = substr($class, $pos + 1);
        } else {
            // PEAR-like class name
            $classPath = null;
            $className = $class;
        }

        $classPath .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        if (array_key_exists($class, $this->_loadedClass)) {
            return $this->_loadedClass[$class];
        }

        foreach ($this->_prefixes as $prefix => $dirs) {
            foreach ($dirs as $dir) {
                if (file_exists(strtolower($dir . DIRECTORY_SEPARATOR . $classPath))) {
                    $file = $this->_loadedClass[$class] = strtolower($dir . DIRECTORY_SEPARATOR . $classPath);
                    return $file;
                }
            }
        }

        foreach ($this->_fallbackDirs as $dir) {
            if (file_exists(strtolower($dir . DIRECTORY_SEPARATOR . $classPath))) {
                $file = $this->_loadedClass[$class] = strtolower($dir . DIRECTORY_SEPARATOR . $classPath);
                return $file;
            }
        }

        if ($this->_useIncludePath && $file = stream_resolve_include_path($classPath)) {
            return $file;
        }

        return false;
    }

}
