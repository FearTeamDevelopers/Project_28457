<?php

namespace THCFrame\Logger\Driver;

use THCFrame\Logger;

/**
 * Description of Filelogger
 *
 * @author Tomy
 */
class File extends Logger\Driver
{

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
        $logsPath = '.' . DIRECTORY_SEPARATOR . trim($this->path, DIRECTORY_SEPARATOR);

        if (!is_dir($logsPath)) {
            mkdir($logsPath, 0755);
        }

        $date = date('Y-m-d', strtotime('-90 days'));
        $this->deleteOldLogs($date);
    }

    /**
     * 
     * @param type $olderThan   date yyyy-mm-dd
     */
    public function deleteOldLogs($olderThan)
    {
        $path = DIRECTORY_SEPARATOR . trim($this->path, DIRECTORY_SEPARATOR);

        if (is_dir($path)) {
            $logsPath = $path;
        } elseif (is_dir('.' . $path)) {
            $logsPath = '.' . $path;
        }

        $iterator = new \DirectoryIterator($logsPath);
        $arr = array();

        foreach ($iterator as $item) {
            if (!$item->isDot() && $item->isFile()) {
                $date = substr($item->getFilename(), 0, 10);

                if(!preg_match('#^[0-9]{4}-[0-9]{2}-[0-9]{2}$#', $date)){
                    continue;
                }
                
                if (time() - strtotime($date) > time() - strtotime($olderThan)) {
                    $arr[] = $logsPath . DIRECTORY_SEPARATOR . $item->getFilename();
                }
            }
        }

        if (!empty($arr)) {
            foreach ($arr as $path) {
                unlink($path);
            }
        }
    }

    /**
     * 
     * @param type $message
     * @param type $flag
     * @param type $file
     */
    public function log($message, $flag = FILE_APPEND, $prependTime = true, $file = null)
    {
        if ($prependTime) {
            $message = '[' . date('Y-m-d H:i:s', time()) . '] ' . $message;
        }

        $message = $message . PHP_EOL;
        $logsPath = '.' . DIRECTORY_SEPARATOR . trim($this->path, DIRECTORY_SEPARATOR);
        $sysLogPath = '.' . DIRECTORY_SEPARATOR .
                str_replace('{date}', date('Y-m-d', time()), 
                        trim($this->syslog, DIRECTORY_SEPARATOR));

        if (NULL !== $file) {
            if (strlen($file) > 50) {
                $file = trim(substr($file, 0, 50)) . '.log';
            }

            $path = $logsPath . DIRECTORY_SEPARATOR . $file;
            if (!file_exists($path)) {
                file_put_contents($path, $message, $flag);
            } elseif (file_exists($path) && filesize($path) < 10000000) {
                file_put_contents($path, $message, $flag);
            } elseif (file_exists($path) && filesize($path) > 10000000) {
                file_put_contents($path, $message);
            }
        } else {
            if (!file_exists($sysLogPath)) {
                file_put_contents($sysLogPath, $message, $flag);
            } elseif (file_exists($sysLogPath) && filesize($sysLogPath) < 10000000) {
                file_put_contents($sysLogPath, $message, $flag);
            } elseif (file_exists($sysLogPath) && filesize($sysLogPath) > 10000000) {
                file_put_contents($sysLogPath, $message);
            }
        }
    }

    /**
     * 
     * @param type $message
     * @param type $flag
     * @param type $prependTime
     */
    public function logError($message, $flag = FILE_APPEND, $prependTime = true)
    {
        if ($prependTime) {
            $message = '[' . date('Y-m-d H:i:s', time()) . '] ' . $message;
        }

        $message = $message . PHP_EOL;
        $errorLogPath = '.' . DIRECTORY_SEPARATOR .
                str_replace('{date}', date('Y-m-d', time()), 
                        trim($this->errorlog, DIRECTORY_SEPARATOR));

        if (!file_exists($errorLogPath)) {
            file_put_contents($errorLogPath, $message, $flag);
        } elseif (file_exists($errorLogPath) && filesize($errorLogPath) < 10000000) {
            file_put_contents($errorLogPath, $message, $flag);
        } elseif (file_exists($errorLogPath) && filesize($errorLogPath) > 10000000) {
            file_put_contents($errorLogPath, $message);
        }
    }

}
