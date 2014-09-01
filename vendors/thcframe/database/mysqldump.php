<?php

namespace THCFrame\Database;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Database\Exception;

/**
 * Description of mysqldump
 *
 * @author Tomy
 */
class Mysqldump extends Base
{

    const MAXLINESIZE = 500;

    private $_fileHandler = null;
    private $_filename;
    private $_backupname;
    private $_settings = array();
    private $_tables = array();
    private $_database;
    private $_mime;
    private $_defaultSettings = array(
        'include-tables' => array(),
        'exclude-tables' => array(),
        'exclude-tables-reqex' => array(),
        'no-data' => false,
        'add-drop-table' => true,
        'single-transaction' => true,
        'lock-tables' => false,
        'add-locks' => true,
        'extended-insert' => true
    );

    /**
     * Object constructor
     * 
     * @param IDatabase $database
     * @param mixed $settings
     */
    public function __construct($settings = null)
    {
//        ini_set('default_charset', 'UTF-8');
        $this->_database = Registry::get('database');
        $this->_database->connect();

        $this->_settings = $this->_extend($this->_defaultSettings, $settings);
        $this->_filename = APP_PATH . '/temp/db/' . $this->_database->getSchema() . '_' . date('Y-m-d') . '.sql';
        $this->_backupname = $this->_database->getSchema() . '_' . date('Y-m-d') . '.sql';
    }

    public function __destruct()
    {
        $this->_database->disconnect();
    }

    /**
     * Returns header for dump file
     *
     * @return string
     */
    private function _getHeader()
    {
        $header = '-- mysqldump-php SQL Dump' . PHP_EOL .
                '--' . PHP_EOL .
                "-- Host: {$this->_database->getHost()}" . PHP_EOL .
                '-- Generation Time: ' . date('r') . PHP_EOL .
                '--' . PHP_EOL .
                "-- Database: `{$this->_database->getSchema()}`" . PHP_EOL .
                '--' . PHP_EOL;
        return $header;
    }

    /**
     * Table structure extractor
     * 
     * @param string $tablename
     * @return boolean
     */
    private function _getTableStructure($tablename)
    {
        $sqlResult = $this->_database->execute("SHOW CREATE TABLE `$tablename`");

        while ($row = $sqlResult->fetch_array(MYSQLI_ASSOC)) {
            if (isset($row['Create Table'])) {
                $this->_write(
                        '-- -----------------------------------------------------' . PHP_EOL .
                        "-- Table structure for table `$tablename` --" . PHP_EOL);

                if ($this->_settings['add-drop-table']) {
                    $this->_write("DROP TABLE IF EXISTS `$tablename`;" . PHP_EOL);
                }

                $this->_write($row['Create Table'] . ';' . PHP_EOL);
                return true;
            }
        }
    }

    /**
     * Table rows extractor
     * 
     * @param string $tablename
     * @return type
     */
    private function _listValues($tablename)
    {
        $this->_write('--' . PHP_EOL .
                "-- Dumping data for table `$tablename` --" . PHP_EOL);

        if ($this->_settings['single-transaction']) {
            //$this->_database->query('SET GLOBAL TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $this->_database->beginTransaction();
        }
        if ($this->_settings['lock-tables']) {
            $this->_database->execute("LOCK TABLES `$tablename` READ LOCAL");
        }
        if ($this->_settings['add-locks']) {
            $this->_write("LOCK TABLES `$tablename` WRITE;" . PHP_EOL);
        }

        $onlyOnce = true;
        $lineSize = 0;
        $sqlResult = $this->_database->execute("SELECT * FROM `$tablename`");

        while ($row = $sqlResult->fetch_array(MYSQLI_ASSOC)) {
            $vals = array();
            foreach ($row as $val) {
                $vals[] = is_null($val) ? 'NULL' : "{$val}";
            }

            if ($onlyOnce || !$this->_settings['extended-insert']) {
                $lineSize += $this->_write(html_entity_decode(
                                "INSERT INTO `$tablename` VALUES ('" . implode("', '", $vals) . "')", ENT_QUOTES, 'UTF-8'));
                $onlyOnce = false;
            } else {
                $lineSize += $this->_write(html_entity_decode(",('" . implode("', '", $vals) . "')", ENT_QUOTES, 'UTF-8'));
            }

            if (($lineSize > Mysqldump::MAXLINESIZE) || !$this->_settings['extended-insert']) {
                $onlyOnce = true;
                $lineSize = $this->_write(';' . PHP_EOL);
            }
        }

        if (!$onlyOnce) {
            $this->_write(';' . PHP_EOL);
        }
        if ($this->_settings['add-locks']) {
            $this->_write('UNLOCK TABLES;' . PHP_EOL);
        }
        if ($this->_settings['single-transaction']) {
            $this->_database->commitTransaction();
        }
        if ($this->_settings['lock-tables']) {
            $this->_database->execute('UNLOCK TABLES');
        }

        return;
    }

    /**
     * merges arrays
     *
     * @param array $args
     * @param array $extended
     *
     * @return array $extended
     */
    private function _extend()
    {
        $args = func_get_args();
        $extended = array();
        if (is_array($args) && count($args) > 0) {
            foreach ($args as $array) {
                if (is_array($array)) {
                    $extended = array_merge($extended, $array);
                }
            }
        }
        return $extended;
    }

    /**
     * Open file
     * 
     * @param string $filename
     * @return boolean
     */
    private function _open($filename)
    {
        $this->_fileHandler = fopen($filename, 'wb');

        if (false === $this->_fileHandler) {
            return false;
        }
        return true;
    }

    /**
     * Write data into file
     * 
     * @param string $str
     * @return type
     * @throws \Exception
     */
    private function _write($str)
    {
        $bytesWritten = 0;
        if (false === ($bytesWritten = fwrite($this->_fileHandler, $str))) {
            throw new Exception\Backup('Writting to file failed!', 4);
        }
        return $bytesWritten;
    }

    /**
     * Close file
     * 
     * @return type
     */
    private function _close()
    {
        return fclose($this->_fileHandler);
    }

    /**
     * Main call
     * 
     * @param string $filename
     * @throws \Exception
     */
    public function create($filename = '')
    {
        if (!empty($filename)) {
            $this->_filename = $filename;
        }

        if (empty($this->_filename)) {
            throw new Exception\Backup('Output file name is not set', 1);
        }

        if (!$this->_open($this->_filename)) {
            throw new Exception\Backup(sprintf('Output file %s is not writable', $this->_filename), 2);
        }

        Event::fire('framework.mysqldump.create.before', array($this->_filename));

        $this->_write($this->_getHeader());
        $this->_tables = array();

        $sqlResult = $this->_database->execute('SHOW TABLES');

        while ($row = $sqlResult->fetch_array(MYSQLI_ASSOC)) {
            if (empty($this->_settings['include-tables']) ||
                    (!empty($this->_settings['include-tables']) &&
                    in_array($row['Tables_in_' . $this->_database->getSchema()], $this->_settings['include-tables'], true))) {
                array_push($this->_tables, $row['Tables_in_' . $this->_database->getSchema()]);
            }
        }

        // Exporting tables one by one
        foreach ($this->_tables as $table) {
            if (in_array($table, $this->_settings['exclude-tables'], true)) {
                continue;
            }
            
            foreach ($this->_settings['exclude-tables-reqex'] as $regex) {
                if(mb_ereg_match($regex, $table)){
                    continue 2;
                }
            }
            
            $is_table = $this->_getTableStructure($table);
            if (true === $is_table && false === $this->_settings['no-data']) {
                $this->_listValues($table);
            }
        }

        Event::fire('framework.mysqldump.create.after', array($this->_filename));

        $this->_close();

        return $this;
    }
    
    /**
     * 
     * @return type
     */
    public function getBackupName()
    {
        return $this->_backupname;
    }

    /**
     * Extension for view:
     * <p><span class="labeled-checkbox block">
      <input type="checkbox" name="downloadDump" value="1">
      Download database dump</span></p>
     */
    public function downloadDump()
    {
        $this->_mime = 'text/x-sql';
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-Disposition: attachment; filename=\"" . basename($this->_filename) . "\"");
        header('Content-Length: ' . filesize($this->_filename));
        ob_clean();
        readfile($this->_filename);
        exit;
    }

}
