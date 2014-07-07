<?php

$get = function($url)
{
    $request = new THCFrame\Request\Request();
    return $request->get("http://".$_SERVER["HTTP_HOST"]."/{$url}");
};

$has = function($html, $fields)
{
    foreach ($fields as $field)
    {
        if (!stristr($html, "name=\"{$field}\""))
        {
            return false;
        }
    }
    
    return true;
};

THCFrame\Core\Test::add(
    function() use ($get, $has)
    {
        $html = $get("register");
        $status = $has($html, array(
            "first",
            "last",
            "email",
            "password",
            "photo",
            "register"
        ));
        
        return $status;
    },
    "Register form has required fields",
    "Forms/Users"
);

THCFrame\Core\Test::add(
    function() use ($get, $has)
    {
        $html = $get("login");
        $status = $has($html, array(
            "email",
            "password",
            "login"
        ));
        
        return $status;
    },
    "Login form has required fields",
    "Forms/Users"
);