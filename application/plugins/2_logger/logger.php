<?php

class Logger {

    protected $_file;
    protected $_entries;
    protected $_start;
    protected $_end;

    /**
     * 
     * @param type $values
     * @return type
     */
    protected function _sum($values) {
        $count = 0;

        foreach ($values as $value) {
            $count += $value;
        }

        return $count;
    }

    /**
     * 
     * @param type $values
     * @return type
     */
    protected function _average($values) {
        return $this->_sum($values) / count($values);
    }

    /**
     * 
     * @param type $options
     * @throws Exception
     */
    public function __construct($options) {
        if (!isset($options['file'])) {
            throw new Exception('Log file invalid.');
        }

        $this->_file = $options['file'];
        $this->_entries = array();
        $this->_start = microtime();
    }

    /**
     * 
     * @param type $message
     */
    public function log($message) {
        $this->_entries[] = array(
            'message' => '[' . date('Y-m-d H:i:s') . ']' . $message,
            'time' => microtime()
        );
    }

    /**
     * 
     */
    public function __destruct() {
        $messages = '';
        $last = $this->_start;
        $times = array();

        foreach ($this->_entries as $entry) {
            $messages .= $entry['message'] . PHP_EOL;
            $times[] = $entry['time'] - $last;
            $last = $entry['time'];
        }

        $messages .= 'Average: ' . $this->_average($times);
        $messages .= ', Longest: ' . max($times);
        $messages .= ', Shortest: ' . min($times);
        $messages .= ', Total: ' . (microtime() - $this->_start);
        $messages .= PHP_EOL;

        file_put_contents($this->_file, $messages, FILE_APPEND);
    }

}