<?php

$database = new THCFrame\Database\Database(array(
    "type" => "mysql",
    "options" => array(
        "host" => "localhost",
        "username" => "root",
        "password" => "",
        "schema" => "frametest"
    )
));
$database = $database->initialize();
$database = $database->connect();

THCFrame\Registry\Registry::set("database", $database);

class Example extends THCFrame\Model\Model
{
    /**
    * @readwrite
    * @column
    * @type auto_increment
    * @primary
    */
    protected $_id;
    
    /**
    * @readwrite
    * @column
    * @type text
    * @length 32
    */
    protected $_name;
    
    /**
    * @readwrite
    * @column
    * @type datetime
    */
    protected $_created;
}

THCFrame\Core\Test::add(
    function() use ($database)
    {
        $example = new Example();
        return ($database->sync($example) instanceof THCFrame\Database\Connector\Mysql);
    },
    "Model syncs",
    "Model"
);

THCFrame\Core\Test::add(
    function() use ($database)
    {
        $example = new Example(array(
            "name" => "foo",
            "created" => date("Y-m-d H:i:s")
        ));
        
        return ($example->save() > 0);
    },
    "Model inserts rows",
    "Model"
);

THCFrame\Core\Test::add(
    function() use ($database)
    {
        return (Example::count() == 1);
    },
    "Model fetches number of rows",
    "Model"
);

THCFrame\Core\Test::add(
    function() use ($database)
    {
        $example = new Example(array(
            "name" => "foo",
            "created" => date("Y-m-d H:i:s")
        ));
        
        $example->save();
        $example->save();
        $example->save();
        
        return (Example::count() == 2);
    },
    "Model saves single row multiple times",
    "Model"
);

THCFrame\Core\Test::add(
    function() use ($database)
    {
        $example = new Example(array(
            "id" => 1,
            "name" => "hello",
            "created" => date("Y-m-d H:i:s")
        ));
        $example->save();
        
        return (Example::first()->name == "hello");
    },
    "Model updates rows",
    "Model"
);

THCFrame\Core\Test::add(
    function() use ($database)
    {
        $example = new Example(array(
            "id" => 2
        ));
        $example->delete();
        
        return (Example::count() == 1);
    },
    "Model deletes rows",
    "Model"
);

unset($database);