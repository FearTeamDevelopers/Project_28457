<?php

namespace THCFrame\Template\Implementation;

use THCFrame\Template;

/**
 * Description of Standard
 *
 * @author Tomy
 */
class Standard extends Template\Implementation
{

    /**
     * Grammar/language map
     * It is a list of language tags for our template dialect, so that the 
     * parser can know what the different types of template tags are, 
     * and how to parse them
     * 
     * @var type 
     */
    protected $_map = array(
        "echo" => array(
            "opener" => "{echo",
            "closer" => "}",
            "handler" => "_echo"
        ),
        "script" => array(
            "opener" => "{script",
            "closer" => "}",
            "handler" => "_script"
        ),
        "statement" => array(
            "opener" => "{",
            "closer" => "}",
            "tags" => array(
                "foreach" => array(
                    "isolated" => false,
                    "arguments" => "{element} in {object}",
                    "handler" => "_each"
                ),
                "for" => array(
                    "isolated" => false,
                    "arguments" => "{initialization} {condition} {incrementation}",
                    "handler" => "_for"
                ),
                "if" => array(
                    "isolated" => false,
                    "arguments" => null,
                    "handler" => "_if"
                ),
                "elseif" => array(
                    "isolated" => true,
                    "arguments" => null,
                    "handler" => "_elif"
                ),
                "else" => array(
                    "isolated" => true,
                    "arguments" => null,
                    "handler" => "_else"
                ),
                "macro" => array(
                    "isolated" => false,
                    "arguments" => "{name}({args})",
                    "handler" => "_macro"
                ),
                "literal" => array(
                    "isolated" => false,
                    "arguments" => null,
                    "handler" => "_literal"
                )
            )
        )
    );

    /**
     * The _echo() method converts the string “{echo $hello}” to “$_text[] = $hello”, 
     * so that it is already optimized for our final evaluated function
     * 
     * @param array $tree
     * @param type $content
     * @return array
     */
    protected function _echo($tree, $content)
    {
        $raw = $this->_script($tree, $content);
        return "\$_text[] = {$raw}";
    }

    /**
     * The _script() method converts the string “{:$foo + = 1}” to “$foo + = 1”
     * 
     * @param array $tree
     * @param type $content
     * @return mixed
     */
    protected function _script($tree, $content)
    {
        $raw = !empty($tree["raw"]) ? $tree["raw"] : "";
        return "{$raw};";
    }

    /**
     * The _each() method returns the code to perform a foreach loop through an array
     * 
     * @param array $tree
     * @param type $content
     * @return mixed
     */
    protected function _each($tree, $content)
    {
        $object = $tree["arguments"]["object"];
        $element = $tree["arguments"]["element"];

        return $this->_loop(
                        $tree, "foreach ({$object} as {$element}_i => {$element}) {{$content}}"
        );
    }

    /**
     * The _for() method produces the code to perform a for loop through an array
     * 
     * @param array $tree
     * @param type $content
     * @return mixed
     */
    protected function _for($tree, $content)
    {
        $initialization = $tree["arguments"]["initialization"];
        $condition = $tree["arguments"]["condition"];
        $incrementation = $tree["arguments"]["incrementation"];

        return $this->_loop(
                        $tree, "for ({$initialization}; {$condition}; {$incrementation}) {{$content}}"
        );
    }

    /**
     * 
     * @param array $tree
     * @param type $content
     * @return mixed
     */
    protected function _if($tree, $content)
    {
        $raw = $tree["raw"];
        return "if ({$raw}) {{$content}}";
    }

    /**
     * 
     * @param array $tree
     * @param type $content
     * @return mixed
     */
    protected function _elif($tree, $content)
    {
        $raw = $tree["raw"];
        return "elseif ({$raw}) {{$content}}";
    }

    /**
     * 
     * @param array $tree
     * @param type $content
     * @return mixed
     */
    protected function _else($tree, $content)
    {
        return "else {{$content}}";
    }

    /**
     * The _macro() method creates the string representation of a function, 
     * based on the contents of a {macro...}...{/macro} tag set. 
     * It is possible, using the {macro} tag, to define functions, 
     * which we then use within our templates
     * 
     * @param array $tree
     * @param type $content
     * @return mixed
     */
    protected function _macro($tree, $content)
    {
        $arguments = $tree["arguments"];
        $name = $arguments["name"];
        $args = $arguments["args"];

        return "function {$name}({$args}) {
                \$_text = array();
                {$content}
                return implode(\$_text);
            }";
    }

    /**
     * The _literal() method directly quotes any content within it
     * 
     * @param array $tree
     * @param type $content
     * @return mixed
     */
    protected function _literal($tree, $content)
    {
        $source = addslashes($tree["source"]);
        return "\$_text[] = \"{$source}\";";
    }

    /**
     * 
     * @param array $tree
     * @param type $inner
     * @return mixed
     */
    protected function _loop($tree, $inner)
    {
        $number = $tree["number"];
        $object = isset($tree["arguments"]["object"])? $tree["arguments"]["object"] : null;
        $children = $tree["parent"]["children"];

        if ($object && !empty($children[$number + 1]["tag"]) && $children[$number + 1]["tag"] == "else") {
            $objectCount = count($object);
            return "if (is_array({$object}) && {$objectCount} > 0) {{$inner}}";
        }

        return $inner;
    }

}
