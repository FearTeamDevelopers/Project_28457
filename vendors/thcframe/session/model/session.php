<?php

namespace THCFrame\Session\Model;

use THCFrame\Model\Model;

/**
 * ORM class for session
 */
class Session extends Model
{

    /**
     * @column
     * @readwrite
     * @primary
     * @type text
     * @length 255
     */
    protected $_id;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate required, numeric, max(10)
     * @label expires
     */
    protected $_expires;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate alphanumeric
     * @label data
     */
    protected $_data;

}
