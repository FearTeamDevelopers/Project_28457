<?php

THCFrame\Core\Test::add(
    function()
    {
        $configuration = new THCFrame\Configuration\Configuration();
        return ($configuration instanceof THCFrame\Configuration\Configuration);
    },
    "Configuration instantiates in uninitialized state",
    "Configuration"
);

THCFrame\Core\Test::add(
    function()
    {
        $configuration = new THCFrame\Configuration\Configuration(array(
            "type" => "ini"
        ));
        
        $configuration = $configuration->initialize();
        return ($configuration instanceof THCFrame\Configuration\Driver\Ini);
    },
    "Configuration\Driver\Ini initializes",
    "Configuration\Driver\Ini"
);

THCFrame\Core\Test::add(
    function()
    {
        $configuration = new THCFrame\Configuration\Configuration(array(
            "type" => "ini"
        ));
        
        $configuration = $configuration->initialize();
        $parsed = $configuration->parse("_configuration");
        
        return ($parsed->config->first == "hello" && $parsed->config->second->second == "bar");
    },
    "Configuration\Driver\Ini parses configuration files",
    "Configuration\Driver\Ini"
);