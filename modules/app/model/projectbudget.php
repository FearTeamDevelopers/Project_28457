<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_ProjectBudget
 *
 * @author Tomy
 */
class App_Model_ProjectBudget extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'pb';

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
     * @type integer
     * 
     * @validate required, numeric, max(8)
     */
    protected $_projectId;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate required, numeric, max(8)
     */
    protected $_userId;

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
     * @length 150
     *
     * @validate required, alphanumeric, max(150)
     * @label title
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     *
     * @validate required, alphanumeric, max(4096)
     * @label description
     */
    protected $_description;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate required, numeric, max(4)
     * @label quantity
     */
    protected $_quantity;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 10
     *
     * @validate required, alphanumeric, max(10)
     * @label measure unit
     */
    protected $_mu;

    /**
     * @column
     * @readwrite
     * @type decimal
     *
     * @validate required, numeric
     * @label price per unit
     */
    protected $_ppu;

    /**
     * @column
     * @readwrite
     * @type decimal
     *
     * @validate required, numeric
     * @label total price
     */
    protected $_totalPrice;

    /**
     * @column
     * @readwrite
     * @type boolean
     * 
     * @validate max(3)
     */
    protected $_deleted;

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
            $this->setDeleted(false);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * 
     * @param type $id
     */
    public static function countProjectBudget($id)
    {
        $budget = self::all(
                        array('projectId = ?' => (int) $id, 'active = ?' => true, 'deleted = ?' => false), 
                        array('totalPrice')
        );
        
        $sum = 0;
        if (null !== $budget) {
            foreach ($budget as $rec) {
                $sum += $rec->getTotalPrice();
            }
        }

        return $sum;
    }

}
