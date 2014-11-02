<?php

namespace THCFrame\Template;

use THCFrame\Core\Base;
use THCFrame\Core\ArrayMethods;
use THCFrame\Core\StringMethods;
use THCFrame\Template\Exception as Exception;

/**
 * Template processing class
 */
class Template extends Base
{

    /**
     * @readwrite
     */
    protected $_implementation;

    /**
     * @readwrite
     */
    protected $_header = "if (is_array(\$_data) && count(\$_data)) extract(\$_data); \$_text = array();";

    /**
     * @readwrite
     */
    protected $_footer = "return implode(\$_text);";

    /**
     * @read
     */
    protected $_code;

    /**
     * @read
     */
    protected $_function;

    /**
     * 
     * @param string $method
     * @return \THCFrame\Template\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf("%s method not implemented", $method));
    }

    /**
     * The _arguments() method is used for any of the statements, 
     * if they have a specific argument format (such as for, foreach, or macro). 
     * It returns the bits between the {...} characters in a neat associative array
     * 
     * @param string $source
     * @param array $expression
     * @return array
     */
    protected function _arguments($source, $expression)
    {
        $args = $this->_array($expression, array(
            $expression => array(
                "opener" => "{",
                "closer" => "}"
            )
        ));

        $tags = $args["tags"];
        $arguments = array();
        $sanitized = StringMethods::sanitize($expression, "()[],.<>*$@");

        foreach ($tags as $i => $tag) {
            $sanitized = str_replace($tag, "(.*)", $sanitized);
            $tags[$i] = str_replace(array("{", "}"), "", $tag);
        }

        if (preg_match("#{$sanitized}#", $source, $matches)) {
            foreach ($tags as $i => $tag) {
                $arguments[$tag] = $matches[$i + 1];
            }
        }

        return $arguments;
    }

    /**
     * The _tag() method calls the implementation’s match() method.
     * The _tag() method will generate a $node array, 
     * which contains a bit of metadata about the tag
     * 
     * @param string $source
     * @return array
     */
    protected function _tag($source)
    {
        $tag = null;
        $arguments = array();

        $match = $this->_implementation->match($source);
        if ($match == null) {
            return false;
        }

        $delimiter = $match["delimiter"];
        $type = $match["type"];

        $start = strlen($type["opener"]);
        $end = strpos($source, $type["closer"]);
        $extract = substr($source, $start, $end - $start);

        if (isset($type["tags"])) {
            $tags = implode("|", array_keys($type["tags"]));
            $regex = "#^(/){0,1}({$tags})\s*(.*)$#";

            if (!preg_match($regex, $extract, $matches)) {
                return false;
            }

            $tag = $matches[2];
            $extract = $matches[3];
            $closer = !!$matches[1];
        }

        if ($tag && $closer) {
            return array(
                "tag" => $tag,
                "delimiter" => $delimiter,
                "closer" => true,
                "source" => false,
                "arguments" => false,
                "isolated" => $type["tags"][$tag]["isolated"]
            );
        }

        if (isset($type["arguments"])) {
            $arguments = $this->_arguments($extract, $type["arguments"]);
        } else if ($tag && isset($type["tags"][$tag]["arguments"])) {
            $arguments = $this->_arguments($extract, $type["tags"][$tag]["arguments"]);
        }

        return array(
            "tag" => $tag,
            "delimiter" => $delimiter,
            "closer" => false,
            "source" => $extract,
            "arguments" => $arguments,
            "isolated" => (!empty($type["tags"]) ? $type["tags"][$tag]["isolated"] : false)
        );
    }

    /**
     * The _array() method essentially deconstructs a template string into 
     * arrays of tags, text, and a combination of the two
     * 
     * @param string $source
     * @return array
     */
    protected function _array($source)
    {
        $parts = array();
        $tags = array();
        $all = array();

        $type = null;
        $delimiter = null;

        while ($source) {
            $match = $this->_implementation->match($source);

            $type = $match["type"];
            $delimiter = $match["delimiter"];

            $opener = strpos($source, $type["opener"]);
            $closer = strpos($source, $type["closer"]) + strlen($type["closer"]);

            if ($opener !== false) {
                $parts[] = substr($source, 0, $opener);
                $tags[] = substr($source, $opener, $closer - $opener);
                $source = substr($source, $closer);
            } else {
                $parts[] = $source;
                $source = "";
            }
        }

        foreach ($parts as $i => $part) {
            $all[] = $part;
            if (isset($tags[$i])) {
                $all[] = $tags[$i];
            }
        }

        return array(
            "text" => ArrayMethods::clean($parts),
            "tags" => ArrayMethods::clean($tags),
            "all" => ArrayMethods::clean($all)
        );
    }

    /**
     * The _tree() method loops through the array of template segments, 
     * generated by the _array() method, and organizes them into a hierarchical structure. 
     * Plain text nodes are simply assigned as-is to the tree, while additional metadata is 
     * generated and assigned with the tags. One important thing to note is that certain 
     * statements have an isolated property. This specifies whether text is allowed before the statement. 
     * When the loop gets to an isolated tag, it removes the preceding segment 
     * (as long as it is a plain text segment), so that the resultant function 
     * code is syntactically correct. The last bit of template processing we need to do is 
     * convert the template tree into a PHP function. This will allow us to reuse 
     * the template without having to recompile it.
     * 
     * @param array $array
     * @return array
     */
    protected function _tree($array)
    {
        $root = array(
            "children" => array()
        );
        $current = & $root;

        foreach ($array as $i => $node) {
            $result = $this->_tag($node);

            if ($result) {
                $tag = isset($result["tag"]) ? $result["tag"] : "";
                $arguments = isset($result["arguments"]) ? $result["arguments"] : "";

                if ($tag) {
                    if (!$result["closer"]) {
                        $last = ArrayMethods::last($current["children"]);

                        if ($result["isolated"] && is_string($last)) {
                            array_pop($current["children"]);
                        }

                        $current["children"][] = array(
                            "index" => $i,
                            "parent" => &$current,
                            "children" => array(),
                            "raw" => $result["source"],
                            "tag" => $tag,
                            "arguments" => $arguments,
                            "delimiter" => $result["delimiter"],
                            "number" => count($current["children"])
                        );
                        $current = & $current["children"][count($current["children"]) - 1];
                    } else if (isset($current["tag"]) && $result["tag"] == $current["tag"]) {
                        $start = $current["index"] + 1;
                        $length = $i - $start;
                        $current["source"] = implode(array_slice($array, $start, $length));
                        $current = & $current["parent"];
                    }
                } else {
                    $current["children"][] = array(
                        "index" => $i,
                        "parent" => &$current,
                        "children" => array(),
                        "raw" => $result["source"],
                        "tag" => $tag,
                        "arguments" => $arguments,
                        "delimiter" => $result["delimiter"],
                        "number" => count($current["children"])
                    );
                }
            } else {
                $current["children"][] = $node;
            }
        }

        return $root;
    }

    /**
     * The _script method walks the hierarchy (generated by the _tree() method), 
     * parses plain text nodes, and indirectly invokes the handler for each valid tag. 
     * The _script() method should generate a syntactically correct function body
     * 
     * @param array $tree
     * @return string
     */
    protected function _script($tree)
    {
        $content = array();

        if (is_string($tree)) {
            $tree = addslashes($tree);
            return "\$_text[] = \"{$tree}\";";
        }

        if (count($tree["children"]) > 0) {
            foreach ($tree["children"] as $child) {
                $content[] = $this->_script($child);
            }
        }

        if (isset($tree["parent"])) {
            return $this->_implementation->handle($tree, implode($content));
        }

        return implode($content);
    }

    /**
     * The parse() method utilizes all of the code we have written so far 
     * (for our template parser) to create a working function. 
     * It wraps the processed template within the $_header and $_footer 
     * variables we saw earlier. This is then evaluated with a call to 
     * PHP’s create_function method and assigned to the protected $_function property
     * 
     * @param mixed $template
     * @return \THCFrame\Template\Template
     * @throws Exception\Implementation
     */
    public function parse($template)
    {
        if (!is_a($this->_implementation, "THCFrame\Template\Implementation")) {
            throw new Exception\Implementation();
        }

        $array = $this->_array($template);
        $tree = $this->_tree($array["all"]);

        $this->_code = $this->header . $this->_script($tree) . $this->footer;
        $this->_function = create_function("\$_data", $this->code);

        return $this;
    }

    /**
     * The process() method check for the existence of the protected $_function 
     * property and throws a Exception\Parser exception if it is not present. 
     * It then tries to execute the generated function with the $data passed to it. 
     * If the function errors, another Exception\Parser exception is thrown
     * 
     * @param array $data
     * @return mixed
     * @throws Exception\Parser
     */
    public function process($data = array())
    {
        if ($this->_function == null) {
            throw new Exception\Parser();
        }

        try {
            $function = $this->_function;
            return $function($data);
        } catch (\Exception $e) {
            throw new Exception\Parser($e->getMessage());
        }
    }

}
