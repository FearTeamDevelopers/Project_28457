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
    private static $_profilerTableCreated = null;
    private $_enabled = true;
    private $_data = array();
    private $_database;
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
    private function _createProfilerTable()
    {
        if (self::$_profilerTableCreated === null) {
            $sql = "CREATE TABLE IF NOT EXISTS `tb_profilerlog` ("
                    . "`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,"
                    . "`identifier` varchar(50) NOT NULL DEFAULT '',"
                    . "`executionTime` varchar(50) NOT NULL DEFAULT '',"
                    . "`smpu` varchar(50) NOT NULL DEFAULT '',"             //start memory peak usage
                    . "`empu` varchar(50) NOT NULL DEFAULT '',"             //end memory peak usage
                    . "`smu` varchar(50) NOT NULL DEFAULT '',"              //start memory usage
                    . "`emu` varchar(50) NOT NULL DEFAULT '',"              //end memory usage
                    . "`sswapsnum` varchar(50) NOT NULL DEFAULT '',"        //start number of swaps
                    . "`eswapsnum` varchar(50) NOT NULL DEFAULT '',"        //end number of swaps
                    . "`spfnum` varchar(50) NOT NULL DEFAULT '',"           //start number of page faults
                    . "`epfnum` varchar(50) NOT NULL DEFAULT '',"           //end number of page faults
                    . "`sutu` varchar(50) NOT NULL DEFAULT '',"             //start user time used (seconds)
                    . "`eutu` varchar(50) NOT NULL DEFAULT '',"             //end user time used (seconds)
                    . "`created` datetime DEFAULT NULL,"
                    . "PRIMARY KEY (`id`),"
                    . "KEY `ix_profilerlog_identifier` (`identifier`)"
                    . ") ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;";

            $this->_database->execute($sql);
            self::$_profilerTableCreated = true;
        }
    }

    /**
     * 
     */
    private function __construct()
    {
        Event::fire('framework.profiler.construct');

        $this->_database = Registry::get('database')->connect();

        $configuration = Registry::get('config');
        $this->_enabled = (bool) $configuration->profiler->active;
        $this->_logging = $configuration->profiler->logging;

        if ($this->_enabled) {
            if (strtolower($this->_logging) === 'database') {
                $this->_createProfilerTable();
            }

            if (strtolower(substr(php_uname('s'), 0, 7)) == 'windows') {
                $this->_winos = true;
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
     */
    public function pause()
    {
        
    }

    /**
     * 
     */
    public function unpause()
    {
        
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
                $usageStr = 'Number of swaps - start: ' . $startRusage['ru_nswap'] . PHP_EOL;
                $usageStr = 'Number of swaps - end: ' . $endRusage['ru_nswap'] . PHP_EOL;
                $usageStr .= 'Number of page faults - start: ' . $startRusage['ru_majflt'] . PHP_EOL;
                $usageStr .= 'Number of page faults - end: ' . $endRusage['ru_majflt'] . PHP_EOL;
                $usageStr .= 'User time used (seconds) - start: ' . $startRusage['ru_utime.tv_sec'] . PHP_EOL;
                $usageStr .= 'User time used (seconds) - end: ' . $endRusage['ru_utime.tv_sec'] . PHP_EOL;

                $sql = 'INSERT INTO tb_profiler (identifier, executionTime, smpu, empu, smu, emu, sswapsnum, eswapsnum, spfnum, epfnum, sutu, eutu, created) '
                        . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now())';
            } else {
                $usageStr = '';
                $sql = 'INSERT INTO tb_profiler (identifier, executionTime, smpu, empu, smu, emu, created) '
                        . 'VALUES (?, ?, ?, ?, ?, ?, now())';
            }

            if ($this->_logging == 'database') {
                $this->_database->execute($sql, $identifier, $time, $startMemoryPeakUsage, $endMemoryPeakUsage, $startMomoryUsage, $endMemoryUsage);
            } else {
                $str = PHP_EOL . 'Identifier: ' . $identifier . PHP_EOL;
                $str .= 'Execution time: ' . $time . ' seconds' . PHP_EOL;
                $str .= 'Memory peak usage - start: ' . $startMemoryPeakUsage . PHP_EOL;
                $str .= 'Memory peak usage - end: ' . $endMemoryPeakUsage . PHP_EOL;
                $str .= 'Memory usage - start: ' . $startMomoryUsage . PHP_EOL;
                $str .= 'Memory usage - end: ' . $endMemoryUsage . PHP_EOL;
                $str .= $usageStr;
                $str .= '----------------------------------------------------------';

                \THCFrame\Core\Core::log($str, 'profiler.log', true);
            }
            unset($this->_data[$identifier]);
        } else {
            return;
        }
    }

    /**
     * 
     */
    public function __destruct()
    {
        Event::fire('framework.profiler.destruct');
        $this->_database->disconnect();
    }

}
