<?php

namespace THCFrame\View;

use THCFrame\Core\Base as Base;
use THCFrame\Events\Events as Event;
use THCFrame\Template as Template;
use THCFrame\View\Exception as Exception;

/**
 * Description of View
 *
 * @author Tomy
 */
class View extends Base
{

    /**
     * @readwrite
     */
    protected $_file;

    /**
     * @readwrite
     */
    protected $_data;

    /**
     * @readwrite
     */
    protected $_flashMessage;

    /**
     * @read
     */
    protected $_template;

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        Event::fire('framework.view.construct.before', array($this->file));

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
        if (isset($_SESSION['infoMessage'])) {
            $this->set('infoMessage', $_SESSION['infoMessage']);
            unset($_SESSION['infoMessage']);
        } else {
            $this->set('infoMessage', '');
        }

        if (isset($_SESSION['warningMessage'])) {
            $this->set('warningMessage', $_SESSION['warningMessage']);
            unset($_SESSION['warningMessage']);
        } else {
            $this->set('warningMessage', '');
        }

        if (isset($_SESSION['successMessage'])) {
            $this->set('successMessage', $_SESSION['successMessage']);
            unset($_SESSION['successMessage']);
        } else {
            $this->set('successMessage', '');
        }

        if (isset($_SESSION['errorMessage'])) {
            $this->set('errorMessage', $_SESSION['errorMessage']);
            unset($_SESSION['errorMessage']);
        } else {
            $this->set('errorMessage', '');
        }

        if (isset($_SESSION['longFlashMessage'])) {
            $this->set('longFlashMessage', $_SESSION['longFlashMessage']);
            unset($_SESSION['longFlashMessage']);
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
        if (empty($_SERVER['HTTP_REFERER'])) {
            return null;
        } else {
            return $_SERVER['HTTP_REFERER'];
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
            $_SESSION['infoMessage'] = $msg;
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
            $_SESSION['warningMessage'] = $msg;
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
            $_SESSION['successMessage'] = $msg;
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
            $_SESSION['errorMessage'] = $msg;
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
            $_SESSION['longFlashMessage'] = $msg;
        } else {
            return $this->get('longFlashMessage');
        }
    }
}
