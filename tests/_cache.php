<?php

THCFrame\Core\Test::add(
    function()
    {
        $cache = new THCFrame\Cache\Cache();
        return ($cache instanceof THCFrame\Cache\Cache);
    },
    "Cache instantiates in uninitialized state",
    "Cache"
);

THCFrame\Core\Test::add(
    function()
    {
        $cache = new THCFrame\Cache\Cache(array(
            "type" => "memcached"
        ));
        
        $cache = $cache->initialize();
        return ($cache instanceof THCFrame\Cache\Driver\Memcached);
    },
    "Cache\Driver\Memcached initializes",
    "Cache\Driver\Memcached"
);

THCFrame\Core\Test::add(
    function()
    {
        $cache = new THCFrame\Cache\Cache(array(
            "type" => "memcached"
        ));
        
        $cache = $cache->initialize();
        return ($cache->connect() instanceof THCFrame\Cache\Driver\Memcached);
    },
    "Cache\Driver\Memcached connects and returns self",
    "Cache\Driver\Memcached"
);

THCFrame\Core\Test::add(
    function()
    {
        $cache = new THCFrame\Cache\Cache(array(
            "type" => "memcached"
        ));
        
        $cache = $cache->initialize();
        $cache = $cache->connect();
        $cache = $cache->disconnect();
        
        try
        {
            $cache->get("anything");
        }
        catch (THCFrame\Cache\Exception\Service $e)
        {
            return ($cache instanceof THCFrame\Cache\Driver\Memcached);
        }
        
        return false;
    },
    "Cache\Driver\Memcached disconnects and returns self",
    "Cache\Driver\Memcached"
);

THCFrame\Core\Test::add(
    function()
    {
        $cache = new THCFrame\Cache\Cache(array(
            "type" => "memcached"
        ));
        
        $cache = $cache->initialize();
        $cache = $cache->connect();
        
        return ($cache->set("foo", "bar", 5) instanceof THCFrame\Cache\Driver\Memcached);
    },
    "Cache\Driver\Memcached sets values and returns self",
    "Cache\Driver\Memcached"
);

THCFrame\Core\Test::add(
    function()
    {
        $cache = new THCFrame\Cache\Cache(array(
            "type" => "memcached"
        ));
        
        $cache = $cache->initialize();
        $cache = $cache->connect();
        $value = $cache->get("foo");
        return ($cache->get("foo") == "bar");
    },
    "Cache\Driver\Memcached retrieves values: {$value}",
    "Cache\Driver\Memcached"
);

THCFrame\Core\Test::add(
    function()
    {
        $cache = new THCFrame\Cache\Cache(array(
            "type" => "memcached"
        ));
        
        $cache = $cache->initialize();
        $cache = $cache->connect();
        
        return ($cache->get("404", "baz") == "baz");
    },
    "Cache\Driver\Memcached returns default values",
    "Cache\Driver\Memcached"
);

THCFrame\Core\Test::add(
    function()
    {
        $cache = new THCFrame\Cache\Cache(array(
            "type" => "memcached"
        ));
        
        $cache = $cache->initialize();
        $cache = $cache->connect();
        
        // we sleep to void the 5 second cache key/value above
        sleep(5);
        
        return ($cache->get("foo") == null);
    },
    "Cache\Driver\Memcached expires values",
    "Cache\Driver\Memcached"
);

THCFrame\Core\Test::add(
    function()
    {
        $cache = new THCFrame\Cache\Cache(array(
            "type" => "memcached"
        ));
        
        $cache = $cache->initialize();
        $cache = $cache->connect();
        
        $cache = $cache->set("hello", "world");
        $cache = $cache->erase("hello");
        
        return ($cache->get("hello") == null && $cache instanceof THCFrame\Cache\Driver\Memcached);
    },
    "Cache\Driver\Memcached erases values and returns self",
    "Cache\Driver\Memcached"
);