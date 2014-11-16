<?php

namespace THCFrame\Logger\Driver;

use THCFrame\Logger;

/**
 * File logger class
 */
class File extends Logger\Driver
{

    const DIR_CHMOD = 0755;
    const FILE_CHMOD = 0644;
    const MAX_FILE_SIZE = 1000000;

    /**
     * Object constructor
     * 
     * @param array $options
     */
    public function __construct($options = null)
    {
        $options = array(
            'path' => 'application/logs',
            'syslog' => '{date}-system.log',
            'errorlog' => '{date}-error.log'
        );

        parent::__construct($options);

        $this->path = APP_PATH . DIRECTORY_SEPARATOR . trim($this->path, DIRECTORY_SEPARATOR);
        $this->syslog = $this->path . DIRECTORY_SEPARATOR
                . str_replace('{date}', date('Y-m-d', time()), trim($this->syslog, DIRECTORY_SEPARATOR));
        $this->errorlog = $this->path . DIRECTORY_SEPARATOR
                . str_replace('{date}', date('Y-m-d', time()), trim($this->errorlog, DIRECTORY_SEPARATOR));

        if (!is_dir($this->path)) {
            mkdir($this->path, self::DIR_CHMOD);
        }

        $date = date('Y-m-d', strtotime('-90 days'));
        $this->deleteOldLogs($date);
    }

    /**
     * Delete old log files
     * 
     * @param string $olderThan   date yyyy-mm-dd
     */
    private function deleteOldLogs($olderThan)
    {
        if (!is_dir($this->path)) {
            return;
        }

        $iterator = new \DirectoryIterator($this->path);
        $arr = array();

        foreach ($iterator as $item) {
            if (!$item->isDot() && $item->isFile()) {
                $date = substr($item->getFilename(), 0, 10);

                if (!preg_match('#^[0-9]{4}-[0-9]{2}-[0-9]{2}$#', $date)) {
                    continue;
                }

                if (time() - strtotime($date) > time() - strtotime($olderThan)) {
                    $arr[] = $this->path . DIRECTORY_SEPARATOR . $item->getFilename();
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
     * Save log message into file
     * 
     * @param string $message
     * @param mixed $flag
     * @param boolean $prependTime
     * @param string $file
     */
    public function log($message, $type = 'error', $flag = FILE_APPEND, $prependTime = true, $file = null)
    {
        if ($prependTime) {
            $message = '[' . date('Y-m-d H:i:s', time()) . '] ' . $message;
        }

        $message = $message . PHP_EOL;

        if ($file !== null) {
            if (strlen($file) > 50) {
                $file = trim(substr($file, 0, 50)) . '.log';
            }

            $path = $this->path . DIRECTORY_SEPARATOR . $file;
            if (!file_exists($path)) {
                file_put_contents($path, $message, $flag);
            } elseif (file_exists($path) && filesize($path) < self::MAX_FILE_SIZE) {
                file_put_contents($path, $message, $flag);
            } elseif (file_exists($path) && filesize($path) > self::MAX_FILE_SIZE) {
                file_put_contents($path, $message);
            }
        } elseif ($type == 'error') {
            if (!file_exists($this->errorlog)) {
                file_put_contents($this->errorlog, $message, $flag);
            } elseif (file_exists($this->errorlog) && filesize($this->errorlog) < self::MAX_FILE_SIZE) {
                file_put_contents($this->errorlog, $message, $flag);
            } elseif (file_exists($this->errorlog) && filesize($this->errorlog) > self::MAX_FILE_SIZE) {
                file_put_contents($this->errorlog, $message);
            }
        } else {
            if (!file_exists($this->syslog)) {
                file_put_contents($this->syslog, $message, $flag);
            } elseif (file_exists($this->syslog) && filesize($this->syslog) < self::MAX_FILE_SIZE) {
                file_put_contents($this->syslog, $message, $flag);
            } elseif (file_exists($this->syslog) && filesize($this->syslog) > self::MAX_FILE_SIZE) {
                file_put_contents($this->syslog, $message);
            }
        }
    }

}
