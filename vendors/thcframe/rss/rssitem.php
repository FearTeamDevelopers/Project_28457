<?php

namespace THCFrame\Rss;

use THCFrame\Core\Base;

/**
 * 
 */
class RssItem extends Base
{

    /**
     * @readwrite
     * @var type 
     */
    protected $_title;

    /**
     * @readwrite
     * @var type 
     */
    protected $_link;

    /**
     * @readwrite
     * @var type 
     */
    protected $_description;
    
    /**
     * @readwrite
     * @var type 
     */
    protected $_status = true;

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
        
        if (($msg = $this->validate()) !== true) {
            $this->status = false;
            throw new Exception\InvalidItem($msg);
        }
    }

    /**
     * 
     * @return string
     */
    public function __toString()
    {
        $string = '<item>'
                . '<title>' . $this->getTitle() . '</title>'
                . '<link>' . $this->getLink() . '</link>'
                . '<description><![CDATA[' . $this->getDescription() . ']]></description>'
                . '</item>';
        
        return $string;
    }

    /**
     * 
     * @return type
     */
    public function toString()
    {
        return $this->__toString();
    }

    /**
     * 
     */
    private function validate()
    {
        if ($this->getTitle() === null || $this->getTitle() == '') {
            return 'Title is empty';
        } elseif ($this->getLink() === null || $this->getLink() == '') {
            return 'Link is empty';
        } elseif ($this->getDescription() === null || $this->getDescription() == '') {
            return 'Description is empty';
        }

        return true;
    }
}
