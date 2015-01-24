<?php

namespace THCFrame\Profiler;

use THCFrame\Registry\Registry;
use THCFrame\Events\Events as Event;
use THCFrame\Request\RequestMethods;

/**
 * Application and database profiler class
 */
class Profiler
{

    /**
     * Profiles
     * 
     * @var Profiler
     */
    private static $_instance = null;

    /**
     * Profiles
     * 
     * @var array
     */
    private $_profiles = array();

    /**
     * Database profiler informations
     * 
     * @var array
     */
    private $_dbProfiles = array();

    /**
     * Flag if profiler is active
     * 
     * @var boolean
     */
    private $_active = null;

    /**
     * Last database profiler indentifier
     * 
     * @var string
     */
    private $_dbLastIdentifier;

    /**
     * Last database profiler indentifier
     * 
     * @var string
     */
    private $_dbLastQueryIdentifier;

    /**
     * 
     */
    private function __clone()
    {
        
    }

    /**
     * 
     */
    private function __wakeup()
    {
        
    }

    /**
     * Object constructor
     */
    private function __construct()
    {
        
    }

    /**
     * Convert unit for better readyability
     * 
     * @param mixed $size
     * @return mixed
     */
    private function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    /**
     * 
     * @return type
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Check if profiler should be active or not
     * 
     * @return boolean
     */
    private function isActive()
    {
        if ($this->_active === null) {
            $configuration = Registry::get('configuration');
            $active = (bool) $configuration->profiler->active;
        } else {
            $active = $this->_active;
        }

        if ($active === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Start application profiling
     * 
     * @param string $identifier
     */
    public function start($identifier = 'CORE')
    {
        if ($this->isActive()) {
            Event::fire('framework.profiler.start.before', array($identifier));
            
            $this->dbStart($identifier);
            $this->_profiles[$identifier]['startTime'] = microtime(true);
            $this->_profiles[$identifier]['startMemoryPeakUsage'] = $this->convert(memory_get_peak_usage());
            $this->_profiles[$identifier]['startMomoryUsage'] = $this->convert(memory_get_usage());
            
            Event::fire('framework.profiler.start.after', array($identifier));
        }
    }

    /**
     * End of application profiling
     * 
     * @param string $identifier
     */
    public function stop($identifier = 'CORE')
    {
        if ($this->isActive()) {
            Event::fire('framework.profiler.stop.before', array($identifier));
            
            $this->_profiles[$identifier]['requestUri'] = RequestMethods::server('REQUEST_URI');
            $this->_profiles[$identifier]['totalTime'] = round(microtime(true) - $this->_profiles[$identifier]['startTime'], 8);
            $this->_profiles[$identifier]['endMemoryPeakUsage'] = $this->convert(memory_get_peak_usage());
            $this->_profiles[$identifier]['endMomoryUsage'] = $this->convert(memory_get_usage());
            $this->_profiles[$identifier]['dbProfiles'] = $this->_dbProfiles[$identifier];
            $this->_profiles[$identifier]['sessionArr'] = $_SESSION;
            $this->_profiles[$identifier]['postArr'] = $_POST;
            $this->_profiles[$identifier]['getArr'] = $_GET;

            $this->dbStop($identifier);
            $this->process();
            
            Event::fire('framework.profiler.stop.after', array($identifier));
        }
    }

    /**
     * Start of database profiling
     */
    public function dbStart($identifier = 'CORE')
    {
        if ($this->isActive()) {
            $this->_dbProfiles[$identifier] = array();
            $this->_dbLastIdentifier = $identifier;
        }
    }

    /**
     * Stop of database profiling
     */
    public function dbStop($identifier = 'CORE')
    {
        if ($this->isActive()) {
            unset($this->_dbProfiles[$identifier]);
            $this->_dbLastIdentifier = 'CORE';
        }
    }

    /**
     * Start of database query profiling
     * 
     * @param string $query
     * @return type
     */
    public function dbQueryStart($query)
    {
        if ($this->isActive()) {
            $this->_dbLastQueryIdentifier = microtime();

            $this->_dbProfiles[$this->_dbLastIdentifier][$this->_dbLastQueryIdentifier]['startTime'] = microtime(true);
            $this->_dbProfiles[$this->_dbLastIdentifier][$this->_dbLastQueryIdentifier]['query'] = $query;
        }
    }

    /**
     * End of database query profiling
     * 
     * @param mixed $totalRows
     * @return type
     */
    public function dbQueryStop($totalRows)
    {
        if ($this->isActive()) {
            $startTime = $this->_dbProfiles[$this->_dbLastIdentifier][$this->_dbLastQueryIdentifier]['startTime'];
            $this->_dbProfiles[$this->_dbLastIdentifier][$this->_dbLastQueryIdentifier]['execTime'] = round(microtime(true) - $startTime, 8);
            $this->_dbProfiles[$this->_dbLastIdentifier][$this->_dbLastQueryIdentifier]['totalRows'] = $totalRows;
            $this->_dbProfiles[$this->_dbLastIdentifier][$this->_dbLastQueryIdentifier]['backTrace'] = debug_backtrace();
        }
    }

    /**
     * Static wrapper for _display function
     * @return string
     */
    public static function display()
    {
        $profiler = self::getInstance();
        return $profiler->_display();
    }

    /**
     * Loads profiler result from file and return it
     * @return string
     */
    public function _display()
    {
        if ($this->isActive()) {
            if (file_exists(APP_PATH . '/application/logs/profiler.log')) {
                return file_get_contents(APP_PATH . '/application/logs/profiler.log');
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    /**
     * Save formated result into file
     */
    private function process()
    {
        if ($this->isActive()) {
            $str = '<link href="/public/css/plugins/profiler.min.css" media="screen" rel="stylesheet" type="text/css" /><div id="profiler">';

            foreach ($this->_profiles as $ident => $profile) {
                $str .= "<div class=\"profiler-basic\">"
                        . "<span title=\"Profile Identifier\">{$ident}</span>"
                        . "<span title=\"Request URI\">{$profile['requestUri']}</span>"
                        . "<span title=\"Execution time [s]\">{$profile['totalTime']}</span>"
                        . "<span title=\"Memory peak usage\">{$profile['endMemoryPeakUsage']}</span>"
                        . "<span title=\"Memory usage\">{$profile['endMomoryUsage']}</span>"
                        . "<span title=\"SQL Query\"><a href=\"#\" class=\"profiler-show-query\" value=\"{$ident}\">SQL Query:</a>" . count($profile['dbProfiles']) . "</span>"
                        . "<span><a href=\"#\" class=\"profiler-show-globalvar\" value=\"{$ident}\">Global variables</a></span></div>";
                $str .= "<div class=\"profiler-query\" id=\"{$ident}_db\">"
                        . "<table><tr style=\"font-weight:bold; border-top:1px solid black;\" class=\"query-header\">"
                        . "<td colspan=5>Query</td><td>Execution time [s]</td><td>Returned rows</td><td colspan=6>Backtrace</td></tr>";

                foreach ($profile['dbProfiles'] as $key => $value) {
                    $str .= "<tr>";
                    $str .= "<td colspan=5 width=\"40%\">{$value['query']}</td>";
                    $str .= "<td>{$value['execTime']}</td>";
                    $str .= "<td>{$value['totalRows']}</td>";
                    $str .= "<td colspan=6 class=\"backtrace\"><div>";

                    foreach ($value['backTrace'] as $key => $trace) {
                        isset($trace['file']) ? $file = $trace['file'] : $file = '';
                        isset($trace['line']) ? $line = $trace['line'] : $line = '';
                        isset($trace['class']) ? $class = $trace['class'] : $class = '';
                        $str .= $key . " " . $file . ":" . $line . ":" . $class . ":" . $trace['function'] . "<br/>";
                    }
                    $str .= "</div></td></tr>";
                }

                $str .= "</table></div>";
                $str .= "<div class=\"profiler-globalvar\" id=\"{$ident}_vars\"><table>";
                $str .= "<tr><td colspan=2>SESSION</td></tr>";

                foreach ($profile['sessionArr'] as $key => $value) {
                    if (is_array($value)) {
                        $arrKey = array_keys($value);
                        $str .= "<tr><td>{$key}</td><td>{$arrKey[0]}</td></tr>";
                    } else {
                        $str .= "<tr><td>{$key}</td><td>{$value}</td></tr>";
                    }
                }

                $str .= "</table><table>";
                $str .= "<tr><td colspan=2>POST</td></tr>";

                foreach ($profile['postArr'] as $key => $value) {
                    if (is_array($value)) {
                        $arrKey = array_keys($value);
                        $str .= "<tr><td>{$key}</td><td>{$arrKey[0]}</td></tr>";
                    } else {
                        $str .= "<tr><td>{$key}</td><td>{$value}</td></tr>";
                    }
                }

                $str .= "</table><table>";
                $str .= "<tr><td colspan=2>GET</td></tr>";

                foreach ($profile['getArr'] as $key => $value) {
                    if (is_array($value)) {
                        $arrKey = array_keys($value);
                        $str .= "<tr><td>{$key}</td><td>{$arrKey[0]}</td></tr>";
                    } else {
                        $str .= "<tr><td>{$key}</td><td>{$value}</td></tr>";
                    }
                }

                $str .= "</table></div>";
            }

            $str .= '</div><script type="text/javascript" src="/public/js/plugins/profiler.min.js"></script>';

            file_put_contents(APP_PATH . '/application/logs/profiler.log', $str);
        }
    }

}
