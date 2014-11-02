<?php

namespace THCFrame\Profiler;

use THCFrame\Registry\Registry;
use THCFrame\Events\Events as Event;

/**
 * Application and database profiler class
 */
class Profiler
{

    /**
     * Profiler instance
     * 
     * @var Profiler
     */
    private static $_instance = null;
    
    /**
     * Profiler activity status
     * 
     * @var boolean
     */
    private $_enabled = false;
    
    /**
     * Application profiler informations
     * 
     * @var array
     */
    private $_data = array();
    
    /**
     * Database profiler informations
     * 
     * @var array
     */
    private $_dbData = array();
    
    /**
     * Last database profiler indentifier
     * 
     * @var string
     */
    private $_dbLastIdentifier;
    
    /**
     * Type of logging
     * 
     * @var string
     */
    private $_logging;

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
     * Object constructor
     */
    private function __construct()
    {
        Event::fire('framework.profiler.construct');

        $configuration = Registry::get('configuration');
        $this->_enabled = (bool) $configuration->profiler->active;
        $this->_logging = $configuration->profiler->logging;

        if (!$this->_enabled) {
            return;
        }
    }

    /**
     * Get profiler instance. Create new if needed
     * 
     * @return Profiler
     */
    public static function getProfiler()
    {
        if (self::$_instance === null) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
     * Start application profiling
     * 
     * @param string $identifier
     */
    public function start($identifier = 'run')
    {
        if ($this->_enabled) {
            $this->_data[$identifier]['startTime'] = microtime(true);
            $this->_data[$identifier]['startMemoryPeakUsage'] = memory_get_peak_usage();
            $this->_data[$identifier]['startMomoryUsage'] = memory_get_usage();
        } else {
            return;
        }
    }

    /**
     * End of application profiling
     * 
     * @param string $identifier
     */
    public function end($identifier = 'run')
    {
        if ($this->_enabled) {
            $startTime = $this->_data[$identifier]['startTime'];
            $endMemoryPeakUsage = $this->convert(memory_get_peak_usage());
            $endMemoryUsage = $this->convert(memory_get_usage());
            $time = round(microtime(true) - $startTime, 8);


            $str = '<link href="/public/css/plugins/profiler.min.css" media="screen" rel="stylesheet" type="text/css" /><div id="profiler">';
            $str .= "<div id='profiler-basic'><span title='Request URI'>{$_SERVER['REQUEST_URI']}</span><span title='Execution time [s]'>{$time}</span>"
                    . "<span title='Memory peak usage'>{$endMemoryPeakUsage}</span><span title='Memory usage'>{$endMemoryUsage}</span>"
                    . '<span title="SQL Query"><a href="#" class="profiler-show-query">SQL Query:</a> ' . count($this->_dbData) . '</span>'
                    . '<span><a href="#" class="profiler-show-globalvar">Global variables</a></span></div>';
            $str .= '<div id="profiler-query"><table><tr style="font-weight:bold; border-top:1px solid black;">'
                    . '<td colspan=5>Query</td><td>Execution time [s]</td><td>Returned rows</td><td colspan=6>Backtrace</td></tr>';

            foreach ($this->_dbData as $key => $value) {
                $str .= '<tr>';
                $str .= "<td colspan=5 width='40%'>{$value['query']}</td>";
                $str .= "<td>{$value['execTime']}</td>";
                $str .= "<td>{$value['totalRows']}</td>";
                $str .= "<td colspan=6 class=\"backtrace\"><div>";
                foreach ($value['backTrace'] as $key => $trace) {
                    isset($trace['file']) ? $file = $trace['file'] : $file = '';
                    isset($trace['line']) ? $line = $trace['line'] : $line = '';
                    isset($trace['class']) ? $class = $trace['class'] : $class = '';
                    $str .= $key . ' ' . $file . ':' . $line . ':' . $class . ':' . $trace['function'] . "<br/>";
                }
                $str .= "</div></td></tr>";
            }
            $str .= '</table></div>';

            $str .= '<div id="profiler-globalvar"><table>';
            $str .= '<tr><td colspan=2>SESSION</td></tr>';
            foreach ($_SESSION as $key => $value) {
                if (is_array($value)) {
                    $str .= '<tr><td>' . $key . '</td><td>' . $value[0] . '</td></tr>';
                } else {
                    $str .= '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
                }
            }
            $str .= '</table><table>';
            $str .= '<tr><td colspan=2>POST</td></tr>';
            foreach ($_POST as $key => $value) {
                if (is_array($value)) {
                    $str .= '<tr><td>' . $key . '</td><td>' . $value[0] . '</td></tr>';
                } else {
                    $str .= '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
                }
            }
            $str .= '</table><table>';
            $str .= '<tr><td colspan=2>GET</td></tr>';
            foreach ($_GET as $key => $value) {
                if (is_array($value)) {
                    $str .= '<tr><td>' . $key . '</td><td>' . $value[0] . '</td></tr>';
                } else {
                    $str .= '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
                }
            }
            $str .= '</table></div>';
            $str .= '</div><script type="text/javascript" src="/public/js/plugins/profiler.min.js"></script>';

            file_put_contents('./application/logs/profiler.log', $str);
        } else {
            return;
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
        if ($this->_enabled) {
            $this->_dbLastIdentifier = substr(rtrim(base64_encode(md5(microtime())), "="), 2, 40);

            for ($i = 0; $i < 100; $i++) {
                $this->_dbLastIdentifier = substr(rtrim(base64_encode(md5(microtime())), "="), 2, 40);

                if (array_key_exists($this->_dbLastIdentifier, $this->_dbData)) {
                    continue;
                } else {
                    break;
                }
            }

            $this->_dbData[$this->_dbLastIdentifier]['startTime'] = microtime(true);
            $this->_dbData[$this->_dbLastIdentifier]['query'] = $query;
        } else {
            return;
        }
    }

    /**
     * End of database query profiling
     * 
     * @param mixed $totalRows
     * @return type
     */
    public function dbQueryEnd($totalRows)
    {
        if ($this->_enabled) {
            $startTime = $this->_dbData[$this->_dbLastIdentifier]['startTime'];
            $this->_dbData[$this->_dbLastIdentifier]['execTime'] = round(microtime(true) - $startTime, 8);
            $this->_dbData[$this->_dbLastIdentifier]['totalRows'] = $totalRows;
            $this->_dbData[$this->_dbLastIdentifier]['backTrace'] = debug_backtrace();
        } else {
            return;
        }
    }

    /**
     * Save informations into file and return it
     */
    public function printProfilerRecord()
    {
        if ($this->_enabled) {
            $fileContent = file_get_contents('./application/logs/profiler.log');
            return $fileContent;
        } else {
            return '';
        }
    }

}
