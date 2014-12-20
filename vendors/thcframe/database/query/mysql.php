<?php

namespace THCFrame\Database\Query;

use THCFrame\Database as Database;
use THCFrame\Database\Exception as Exception;

/**
 * Extension for Query class specificly for Mysql
 */
class Mysql extends Database\Query
{

    /**
     * 
     * @return type
     * @throws Exception\Sql
     */
    public function all()
    {
        $sql = $this->_buildSelect();
        $result = $this->connector->execute($sql);
        
        if ($result === false) {
            $err = $this->connector->getLastError();
            $this->_logError($err, $sql);
            
            if (ENV == 'dev') {
                throw new Exception\Sql(sprintf('There was an error with your SQL query: %s', $err));
            } else {
                throw new Exception\Sql('There was an error');
            }
        }

        $rows = array();

        for ($i = 0; $i < $result->num_rows; $i++) {
            $rows[] = $result->fetch_array(MYSQLI_ASSOC);
        }

        return $rows;
    }

}
