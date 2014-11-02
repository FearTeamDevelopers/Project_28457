<?php

namespace THCFrame\View;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Template;
use THCFrame\View\Exception as Exception;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;

/**
 * View class
 */
class View extends Base
{

    /**
     * View file
     * 
     * @readwrite
     */
    protected $_file;

    /**
     * Storage for view data
     * 
     * @readwrite
     */
    protected $_data;

    /**
     * Template instance
     * 
     * @read
     */
    protected $_template;
    
    /**
     * Session object
     * 
     * @var \THCFrame\Session\Session
     */
    private $_session;

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        Event::fire('framework.view.construct.before', array($this->file));

        $this->_session = Registry::get('session');

        $this->_template = new Template\Template(array(
            'implementation' => new Template\Implementation\Extended()
        ));

        $this->_checkMessage();

        Event::fire('framework.view.construct.after', array($this->file, $this->template));
    }

    /**
     * 
     * @param type $method
     * @return \THCFrame\View\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * Method check if there is any message set or not
     */
    private function _checkMessage()
    {
        if ($this->_session->get('infoMessage') !== null) {
            $this->set('infoMessage', $this->_session->get('infoMessage'));
            $this->_session->erase('infoMessage');
        } else {
            $this->set('infoMessage', '');
        }

        if ($this->_session->get('warningMessage') !== null) {
            $this->set('warningMessage', $this->_session->get('warningMessage'));
            $this->_session->erase('warningMessage');
        } else {
            $this->set('warningMessage', '');
        }

        if ($this->_session->get('successMessage') !== null) {
            $this->set('successMessage', $this->_session->get('successMessage'));
            $this->_session->erase('successMessage');
        } else {
            $this->set('successMessage', '');
        }

        if ($this->_session->get('errorMessage') !== null) {
            $this->set('errorMessage', $this->_session->get('errorMessage'));
            $this->_session->erase('errorMessage');
        } else {
            $this->set('errorMessage', '');
        }

        if ($this->_session->get('longFlashMessage') !== null) {
            $this->set('longFlashMessage', $this->_session->get('longFlashMessage'));
            $this->_session->erase('longFlashMessage');
        } else {
            $this->set('longFlashMessage', '');
        }
    }

    /**
     * 
     * @return string
     */
    public function render()
    {
        Event::fire('framework.view.render.before', array($this->file));

        if (!file_exists($this->file)) {
            return '';
        }

        return $this->template
                        ->parse(file_get_contents($this->file))
                        ->process($this->data);
    }

    /**
     * 
     * @return null
     */
    public function getHttpReferer()
    {
        if (RequestMethods::server('HTTP_REFERER') === false) {
            return null;
        } else {
            return RequestMethods::server('HTTP_REFERER');
        }
    }

    /**
     * 
     * @param type $key
     * @param type $default
     * @return type
     */
    public function get($key, $default = '')
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return $default;
    }

    /**
     * 
     * @param type $key
     * @param type $value
     * @throws Exception\Data
     */
    protected function _set($key, $value)
    {
        if (!is_string($key) && !is_numeric($key)) {
            throw new Exception\Data('Key must be a string or a number');
        }

        $data = $this->data;

        if (!$data) {
            $data = array();
        }

        $data[$key] = $value;
        $this->data = $data;
    }

    /**
     * 
     * @param type $key
     * @param type $value
     * @return \THCFrame\View\View
     */
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $_key => $value) {
                $this->_set($_key, $value);
            }
            return $this;
        }

        $this->_set($key, $value);
        return $this;
    }

    /**
     * 
     * @param type $key
     * @return \THCFrame\View\View
     */
    public function erase($key)
    {
        unset($this->data[$key]);
        return $this;
    }

    /**
     * 
     * @param text $msg
     * @return text
     */
    public function infoMessage($msg = '')
    {
        if (!empty($msg)) {
            $this->_session->set('infoMessage', $msg);
        } else {
            return $this->get('infoMessage');
        }
    }

    /**
     * 
     * @param text $msg
     * @return text
     */
    public function warningMessage($msg = '')
    {
        if (!empty($msg)) {
            $this->_session->set('warningMessage', $msg);
        } else {
            return $this->get('warningMessage');
        }
    }

    /**
     * 
     * @param text $msg
     * @return text
     */
    public function successMessage($msg = '')
    {
        if (!empty($msg)) {
            $this->_session->set('successMessage', $msg);
        } else {
            return $this->get('successMessage');
        }
    }

    /**
     * 
     * @param text $msg
     * @return text
     */
    public function errorMessage($msg = '')
    {
        if (!empty($msg)) {
            $this->_session->set('errorMessage', $msg);
        } else {
            return $this->get('errorMessage');
        }
    }

    /**
     * 
     * @param text $msg
     * @return text
     */
    public function longFlashMessage($msg = '')
    {
        if (!empty($msg)) {
            $this->_session->set('longFlashMessage', $msg);
        } else {
            return $this->get('longFlashMessage');
        }
    }

}
