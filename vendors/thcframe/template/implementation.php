<?php

namespace THCFrame\Template;

use THCFrame\Core\Base;
use THCFrame\Core\StringMethods;
use THCFrame\Template\Exception as Exception;

/**
 * Description of Implementation
 * In order for our template parser to remain flexible, the structure of our 
 * template dialect needs to be in a separate class to the parser. 
 * This allows us to swap out different implementation classes for the same parser. 
 * All of our implementation classes should also inherit from a base implementation class.
 *
 * @author Tomy
 */
class Implementation extends Base
{

    /**
     * The _handler() method takes a $node array and 
     * determines the correct handler method to execute
     * 
     * @param array $node
     * @return mixed
     */
    protected function _handler($node)
    {
        if (empty($node["delimiter"])) {
            return null;
        }

        if (!empty($node["tag"])) {
            return $this->_map[$node["delimiter"]]["tags"][$node["tag"]]["handler"];
        }

        return $this->_map[$node["delimiter"]]["handler"];
    }

    /**
     * The handle() method uses the _handler() method to get the correct handler 
     * method, and executes it, throwing a Exception\Implementation exception if 
     * there was a problem executing the statement’s handler
     * 
     * @param string $node
     * @param mixed $content
     * @return mixed
     * @throws Exception\Implementation
     */
    public function handle($node, $content)
    {
        try {
            $handler = $this->_handler($node);
            return call_user_func_array(array($this, $handler), array($node, $content));
        } catch (\Exception $e) {
            throw new Exception\Implementation($e->getMessage());
        }
    }

    /**
     * The match() method evaluates a $source string to 
     * determine if it matches a tag or statement
     * 
     * @param mixed $source
     * @return mixed
     */
    public function match($source)
    {
        $type = null;
        $delimiter = null;

        foreach ($this->_map as $_delimiter => $_type) {
            if (!$delimiter || StringMethods::indexOf($source, $type["opener"]) == -1) {
                $delimiter = $_delimiter;
                $type = $_type;
            }

            $indexOf = StringMethods::indexOf($source, $_type["opener"]);

            if ($indexOf > -1) {
                if (StringMethods::indexOf($source, $type["opener"]) > $indexOf) {
                    $delimiter = $_delimiter;
                    $type = $_type;
                }
            }
        }

        if ($type == null) {
            return null;
        }

        return array(
            "type" => $type,
            "delimiter" => $delimiter
        );
    }

}
