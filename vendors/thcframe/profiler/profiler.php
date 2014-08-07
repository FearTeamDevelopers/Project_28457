<?php

namespace THCFrame\Profiler;

use THCFrame\Profiler\Exception as Exception;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Events\Events as Event;

/**
 * 
 */
class Profiler
{

    private static $_instance = null;
    private $_enabled = false;
    private $_data = array();
    private $_dbData = array();
    private $_dbLastIdentifier;
    private $_logging;
    private $_winos;

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
     * 
     * @param type $size
     * @return type
     */
    private function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    /**
     * 
     */
    private function __construct()
    {
        Event::fire('framework.profiler.construct');

        $configuration = Registry::get('config');
        $this->_enabled = (bool) $configuration->profiler->active;
        $this->_logging = $configuration->profiler->logging;

        if ($this->_enabled) {
            if (strtolower(substr(php_uname('s'), 0, 7)) == 'windows') {
                $this->_winos = true;
            } elseif (strtolower(substr(php_uname('s'), 0, 5)) == 'linux') {
                $this->_winos = false;
            } else {
                $this->_winos = false;
            }
        } else {
            return;
        }
    }

    /**
     * 
     * @return type
     */
    public static function getProfiler()
    {
        if (self::$_instance === null) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
     * 
     * @param type $identifier
     */
    public function start($identifier = 'run')
    {
        if ($this->_enabled) {
            $this->_data[$identifier]['startTime'] = microtime(true);
            $this->_data[$identifier]['startMemoryPeakUsage'] = memory_get_peak_usage();
            $this->_data[$identifier]['startMomoryUsage'] = memory_get_usage();

            if (!$this->_winos) {
                $this->_data[$identifier]['startRusage'] = getrusage();
            }
        } else {
            return;
        }
    }

    /**
     * 
     * @param type $identifier
     */
    public function end($identifier = 'run')
    {
        if ($this->_enabled) {
            $startTime = $this->_data[$identifier]['startTime'];
            $startMemoryPeakUsage = $this->convert($this->_data[$identifier]['startMemoryPeakUsage']);
            $startMomoryUsage = $this->convert($this->_data[$identifier]['startMomoryUsage']);

            $endMemoryPeakUsage = $this->convert(memory_get_peak_usage());
            $endMemoryUsage = $this->convert(memory_get_usage());
            $time = round(microtime(true) - $startTime, 8);

            if (!$this->_winos) {
                $startRusage = $this->_data[$identifier]['startRusage'];
                $endRusage = getrusage();

                $usageStr = "<td>{$startRusage['ru_nswap']}</td><td>{$endRusage['ru_nswap']}</td>"
                . "<td>{$startRusage['ru_majflt']}</td><td>{$endRusage['ru_majflt']}</td>"
                . "<td>{$startRusage['ru_utime.tv_sec']}</td><td>{$endRusage['ru_utime.tv_sec']}</td>";
            } else {
                $usageStr = "<td></td><td></td><td></td><td></td><td></td><td></td>";
            }

            $str = '<table><tr style="font-weight:bold;">';
            $str .= '<td>Request URI</td><td>Execution time [s]</td><td>Memory peak usage - start</td><td>Memory peak usage - end</td>'
                    . '<td>Memory usage - start</td><td>Memory usage - end</td><td>Number of swaps - start</td><td>Number of swaps - end</td>'
                    . '<td>Number of page faults - start</td><td>Number of page faults - end</td><td>User time used (seconds) - start</td>'
                    . '<td>User time used (seconds) - end</td></tr>';
            $str .= '<tr>';
            $str .= "<td>{$_SERVER['REQUEST_URI']}</td><td>{$time}</td><td>{$startMemoryPeakUsage}</td><td>{$endMemoryPeakUsage}</td>"
            . "<td>{$startMomoryUsage}</td><td>{$endMemoryUsage}</td>";
            $str .= $usageStr;
            $str .= '</tr>';
            $str .= '<tr style="font-weight:bold; border-top:1px solid black;"><td colspan=4>Query</td><td>Execution time [s]</td><td>Returned rows</td><td colspan=6>Backtrace</td></tr>';
            
            foreach ($this->_dbData as $key => $value) {
                $str .= '<tr>';
                $str .= "<td colspan=4 width='40%'>{$value['query']}</td>";
                $str .= "<td>{$value['execTime']}</td>";
                $str .= "<td>{$value['totalRows']}</td>";
                $str .= "<td colspan=6>";
                foreach ($value['backTrace'] as $key => $trace){
                    $str .= $key.' '.$trace['file'].':'.$trace['line'].':'.$trace['class'].':'.$trace['function']."<br/>";
                }
                $str .= "</td>";
                $str .= '</tr>';
            }
            
            $str .= '</table>'.PHP_EOL;
            \THCFrame\Core\Core::log($str, 'profiler.log', true, false);

        } else {
            return;
        }
    }

    /**
     * 
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
     * 
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
     * 
     */
    public function printProfilerRecord()
    {
        if ($this->_enabled) {
            $fileContent = file_get_contents('./application/logs/profiler.log');
            return $fileContent;
        }else{
            return '';
        }
    }

    /**
     * 
     */
    public function __destruct()
    {
        Event::fire('framework.profiler.destruct');
    }

}
