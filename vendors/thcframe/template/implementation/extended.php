<?php

namespace THCFrame\Template\Implementation;

use THCFrame\Request\Request;
use THCFrame\Registry\Registry;
use THCFrame\Template\Template;
use THCFrame\Core\StringMethods;
use THCFrame\Request\RequestMethods;

/**
 * Description of Extended
 *
 * @author Tomy
 */
class Extended extends Standard
{

    /**
     * @readwrite
     */
    protected $_defaultPath = "modules/%s/view";

    /**
     * @readwrite
     */
    protected $_defaultKey = "_data";

    /**
     * @readwrite
     */
    protected $_index = 0;

    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->_map = array(
            "partial" => array(
                "opener" => "{partial",
                "closer" => "}",
                "handler" => "_partial"
            ),
            "include" => array(
                "opener" => "{include",
                "closer" => "}",
                "handler" => "_include"
            ),
            "yield" => array(
                "opener" => "{yield",
                "closer" => "}",
                "handler" => "_yield"
            )
                ) + $this->_map;

        $this->_map["statement"]["tags"] = array(
            "set" => array(
                "isolated" => false,
                "arguments" => "{key}",
                "handler" => "set"
            ),
            "append" => array(
                "isolated" => false,
                "arguments" => "{key}",
                "handler" => "append"
            ),
            "prepend" => array(
                "isolated" => false,
                "arguments" => "{key}",
                "handler" => "prepend"
            )
                ) + $this->_map["statement"]["tags"];
    }

    /**
     * 
     * @param type $tree
     * @param type $content
     * @return type
     */
    protected function _include($tree, $content)
    {
        $template = new Template(array(
            "implementation" => new self()
        ));

        $router = Registry::get("router");
        $module = $router->getLastRoute()->module;
        $path = sprintf($this->defaultPath, $module);

        $file = trim($tree["raw"]);

        $content = file_get_contents(APP_PATH . "/{$path}/{$file}");

        $template->parse($content);
        $index = $this->_index++;

        return "\$_anon = function(\$_data){
                " . $template->code . "
            };\$_text[] = \$_anon(\$_data);";
    }

    /**
     * 
     * @param type $tree
     * @param type $content
     * @return type
     */
    protected function _partial($tree, $content)
    {
        $address = trim($tree["raw"], " /");

        if (StringMethods::indexOf($address, "http") != 0) {
            $host = RequestMethods::server("HTTP_HOST");
            $address = "http://{$host}/{$address}";
        }

        $request = new Request();
        $response = addslashes(trim($request->get($address)));

        return "\$_text[] = \"{$response}\";";
    }

    /**
     * 
     * @param type $tree
     * @return null
     */
    protected function _getKey($tree)
    {
        if (empty($tree["arguments"]["key"])) {
            return null;
        }

        return trim($tree["arguments"]["key"]);
    }

    /**
     * 
     * @param type $tree
     * @param type $content
     * @return type
     */
    protected function _yield($tree, $content)
    {
        $key = trim($tree["raw"]);
        $value = addslashes($this->_getValue($key));
        return "\$_text[] = \"{$value}\";";
    }

    /**
     * 
     * @param type $key
     * @param type $value
     */
    protected function _setValue($key, $value)
    {
        if (!empty($key)) {
            $data = Registry::get($this->defaultKey, array());
            $data[$key] = $value;

            Registry::set($this->defaultKey, $data);
        }
    }

    /**
     * 
     * @param type $key
     * @return string
     */
    protected function _getValue($key)
    {
        $data = Registry::get($this->defaultKey);

        if (isset($data[$key])) {
            return $data[$key];
        }

        return "";
    }

    /**
     * 
     * @param type $key
     * @param type $value
     */
    public function set($key, $value)
    {
        if (StringMethods::indexOf($value, "\$_text") > -1) {
            $first = StringMethods::indexOf($value, "\"");
            $last = StringMethods::lastIndexOf($value, "\"");
            $value = stripslashes(substr($value, $first + 1, ($last - $first) - 1));
        }

        if (is_array($key)) {
            $key = $this->_getKey($key);
        }

        $this->_setValue($key, $value);
    }

    /**
     * 
     * @param type $key
     * @param type $value
     */
    public function append($key, $value)
    {
        if (is_array($key)) {
            $key = $this->_getKey($key);
        }

        $previous = $this->_getValue($key);
        $this->set($key, $previous . $value);
    }

    /**
     * 
     * @param type $key
     * @param type $value
     */
    public function prepend($key, $value)
    {
        if (is_array($key)) {
            $key = $this->_getKey($key);
        }

        $previous = $this->_getValue($key);
        $this->set($key, $value . $previous);
    }

}
