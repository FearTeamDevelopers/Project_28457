<?php

namespace THCFrame\Database\Query;

use THCFrame\Database as Database;
use THCFrame\Database\Exception as Exception;
use THCFrame\Core\Core;

/**
 * Description of Mysql
 *
 * @author Tomy
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
            $error = $this->connector->lastError;

            if (ENV == 'dev') {
                Core::getLogger()->log($sql);
                throw new Exception\Sql(sprintf('There was an error with your SQL query: %s', $error));
            } else {
                Core::getLogger()->log($sql);
                throw new Exception\Sql('There was an error with your SQL query');
            }
        }

        $rows = array();

        for ($i = 0; $i < $result->num_rows; $i++) {
            $rows[] = $result->fetch_array(MYSQLI_ASSOC);
        }

        return $rows;
    }

}
