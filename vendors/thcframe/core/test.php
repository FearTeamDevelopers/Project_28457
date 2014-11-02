<?php

namespace THCFrame\Core;

/**
 * Test class
 */
class Test
{

    private static $_tests = array();

    /**
     * 
     * @param type $callback
     * @param type $title
     * @param type $set
     */
    public static function add($callback, $title = 'Unnamed Test', $set = 'General')
    {
        self::$_tests[] = array(
            'set' => $set,
            'title' => $title,
            'callback' => $callback
        );
    }

    /**
     * 
     * @param type $before
     * @param type $after
     * @return type
     */
    public static function run($before = null, $after = null)
    {
        if ($before) {
            $before(self::$_tests);
        }

        $passed = array();
        $failed = array();
        $exceptions = array();

        foreach (self::$_tests as $test) {
            try {
                $result = call_user_func($test['callback']);

                if ($result) {
                    $passed[] = array(
                        'set' => $test['set'],
                        'title' => $test['title']
                    );
                } else {
                    $failed[] = array(
                        'set' => $test['set'],
                        'title' => $test['title']
                    );
                }
            } catch (\Exception $e) {
                $exceptions[] = array(
                    'set' => $test['set'],
                    'title' => $test['title'],
                    'type' => get_class($e),
                    'message' => $e->getMessage()
                );
            }
        }

        if ($after) {
            $after(self::$_tests);
        }

        return array(
            'passed' => $passed,
            'failed' => $failed,
            'exceptions' => $exceptions
        );
    }

}
