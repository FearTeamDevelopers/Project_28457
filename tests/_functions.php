<?php

$post = function($url, $data)
{
    $request = new THCFrame\Request\Request();
    return $request->post("http://".$_SERVER["HTTP_HOST"]."/{$url}", $data);
};

THCFrame\Core\Test::add(
    function() use ($post)
    {
        $html = $post(
            "register",
            array(
                "first" => "Hello",
                "last" => "World",
                "email" => "info@example.com",
                "password" => "password",
                "register" => "register"
            )
        );
        
        return (stristr($html, "Your account has been created!"));
    },
    "Register form creates user",
    "Functions/Users"
);

THCFrame\Core\Test::add(
    function() use ($post)
    {
        $html = $post(
            "login",
            array(
                "email" => "info@example.com",
                "password" => "password",
                "login" => "login"
            )
        );
        
        return (stristr($html, "Location: /"));
    },
    "Login form creates user session + redirect to profile",
    "Functions/Users"
);