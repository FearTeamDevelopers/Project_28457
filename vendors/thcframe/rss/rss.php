<?php

namespace THCFrame\Rss;

use THCFrame\Core\Base;
use THCFrame\Rss\RssItem;
use THCFrame\Request\RequestMethods;
use THCFrame\Rss\Exception;

/**
 * 
 */
class Rss extends Base
{

    /**
     * @readwrite
     * @var string 
     */
    protected $_title;

    /**
     * @readwrite
     * @var string 
     */
    protected $_description;

    /**
     * @readwrite
     * @var string 
     */
    protected $_link;

    /**
     * @readwrite
     * @var string 
     */
    protected $_language;

    /**
     * @readwrite
     * @var string 
     */
    protected $_imageTitle;

    /**
     * @readwrite
     * @var string 
     */
    protected $_imageUrl;
    
    /**
     * @readwrite
     * @var string 
     */
    protected $_imageLink;

    /**
     * @readwrite
     * @var string 
     */
    protected $_imageWidth;

    /**
     * @readwrite
     * @var string 
     */
    protected $_imageHeight;
    
    /**
     * @readwrite
     * @var boolean 
     */
    protected $_status = true;

    /**
     * @readwrite
     * @var array
     */
    protected $_items = array();

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        if (($msg = $this->validate()) !== true) {
            $this->status = false;
            throw new Exception\InvalidDetail($msg);
        }
    }

    /**
     * 
     * @return type
     */
    public function createFeed()
    {
        if ($this->getStatus()) {
            $content = $this->getDetails() . $this->getFeedItems() . $this->getFeedEnd();
            file_put_contents('./temp/rss/rss.xml', $content);
        } else {
            throw new Exception('Some error occured while feed was generating');
        }
    }

    /**
     * 
     * @param type $title
     * @param type $link
     * @param type $description
     */
    public function addItem($title, $link, $description)
    {
        try {
            $item = new RssItem(array(
                'title' => $title,
                'link' => $link,
                'description' => $description
            ));
            
            $this->_items[] = $item;
        } catch (Exception\InvalidItem $e) {
            $this->status = false;
            throw new Exception\InvalidItem($e->getMessage());
        }

        
    }

    /**
     * 
     * @return string
     */
    private function getDetails()
    {
        $host = 'http://'.  RequestMethods::server('HTTP_HOST');

        $details = '<?xml version="1.0" encoding="UTF-8" ?>
            <rss version="2.0">
            <channel>'
                . '<title>' . $this->getTitle() . '</title>'
                . '<link>' . $this->getLink() . '</link>'
                . '<description>' . $this->getDescription() . '</description>'
                . '<language>' . $this->getLanguage() . '</language>'
                . '<image>'
                . '<title>' . $this->getImageTitle() . '</title>'
                . '<url>' .$host. $this->getImageUrl() . '</url>'
                . '<link>' .$host. $this->getImageLink() . '</link>'
                . '<width>' . $this->getImageWidth() . '</width>'
                . '<height>' . $this->getImageHeight() . '</height>
                </image>';

        return $details;
    }

    /**
     * 
     * @return type
     */
    private function getFeedItems()
    {
        $items = '';

        foreach ($this->_items as $item) {
            if ($item instanceof RssItem) {
                $items .= $item->toString();
            } else {
                continue;
            }
        }

        return $items;
    }

    /**
     * 
     * @return string
     */
    private function getFeedEnd()
    {
        return '</channel></rss>';
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
        } elseif ($this->getLanguage() === null || $this->getLanguage() == '') {
            return 'Language is empty';
        } elseif ($this->getImageTitle() === null || $this->getImageTitle() == '') {
            return 'Image title is empty';
        } elseif ($this->getImageUrl() === null || $this->getImageUrl() == '') {
            return 'Image url is empty';
        } elseif ($this->getImageLink() === null || $this->getImageLink() == '') {
            return 'Image link is empty';
        } elseif ($this->getImageWidth() === null || $this->getImageWidth() == '') {
            return 'Image width is empty';
        } elseif ($this->getImageHeight() === null || $this->getImageHeight() == '') {
            return 'Image height is empty';
        }

        return true;
    }

}
