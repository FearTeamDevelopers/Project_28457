<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Client
 *
 * @author Tomy
 */
class App_Model_Client extends Model
{
    /**
     * @column
     * @readwrite
     * @primary
     * @type auto_increment
     */
    protected $_id;
    
    /**
     * @column
     * @readwrite
     * @type boolean
     * 
     * @validate max(3)
     * @label active
     */
    protected $_active;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 80
     *
     * @validate required, alphanumeric, max(80)
     * @label contact person
     */
    protected $_contactPerson;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 80
     *
     * @validate required, email, max(80)
     * @label contact email
     */
    protected $_contactEmail;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 150
     *
     * @validate required, alphanumeric, max(150)
     * @label company name
     */
    protected $_companyName;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     *
     * @validate required, alphanumeric, max(1024)
     * @label company address
     */
    protected $_companyAddress;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 25
     *
     * @validate numeric, max(25)
     * @label contact phone
     */
    protected $_contactPhone;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 100
     *
     * @validate url, max(100)
     * @label web page
     */
    protected $_www;
    
    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_modified;

    /**
     * 
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setCreated(date('Y-m-d H:i:s'));
            $this->setActive(true);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }
}
