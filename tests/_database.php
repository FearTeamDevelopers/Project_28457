<?php

$options = array(
    "type" => "mysql",
    "options" => array(
        "host" => "localhost",
        "username" => "root",
        "password" => "",
        "schema" => "frametest"
    )
);

THCFrame\Core\Test::add(
    function()
    {
        $database = new THCFrame\Database\Database();
        return ($database instanceof THCFrame\Database\Database);
    },
    "Database instantiates in uninitialized state",
    "Database"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        
        return ($database instanceof THCFrame\Database\Connector\Mysql);
    },
    "Database\Connector\Mysql initializes",
    "Database\Connector\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        
        return ($database instanceof THCFrame\Database\Connector\Mysql);
    },
    "Database\Connector\Mysql connects and returns self",
    "Database\Connector\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        $database = $database->disconnect();
        
        try
        {
            $database->execute("SELECT 1");
        }
        catch (THCFrame\Database\Exception\Service $e)
        {
            return ($database instanceof THCFrame\Database\Connector\Mysql);
        }
        
        return false;
    },
    "Database\Connector\Mysql disconnects and returns self",
    "Database\Connector\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
                
        return ($database->escape("foo'".'bar"') == "foo\\'bar\\\"");
    },
    "Database\Connector\Mysql escapes values",
    "Database\Connector\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        
        $database->execute("
            SOME INVALID SQL
        ");
        
        return (bool) $database->lastError;
    },
    "Database\Connector\Mysql returns last error",
    "Database\Connector\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        
        $database->execute("
            DROP TABLE IF EXISTS `tb_example`;
        ");
        $database->execute("
            CREATE TABLE `tb_example` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `number` int(11) NOT NULL,
                `text` varchar(255) NOT NULL,
                `boolean` tinyint(4) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
                
        return !$database->lastError;
    },
    "Database\Connector\Mysql executes queries",
    "Database\Connector\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        
        for ($i = 0; $i < 4; $i++)
        {
            $database->execute("
                INSERT INTO `tb_example` (`number`, `text`, `boolean`) VALUES ('1337', 'text', '0');
            ");
        }
                
        return $database->lastInsertId;
    },
    "Database\Connector\Mysql returns last inserted ID",
    "Database\Connector\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        
        $database->execute("
            UPDATE `tb_example` SET `number` = 1338;
        ");
        
        return $database->affectedRows;
    },
    "Database\Connector\Mysql returns affected rows",
    "Database\Connector\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        $query = $database->query();
        
        return ($query instanceof THCFrame\Database\Query\Mysql);
    },
    "Database\Connector\Mysql returns instance of Database\Query\Mysql",
    "Database\Query\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        $query = $database->query();
        
        return ($query->connector instanceof THCFrame\Database\Connector\Mysql);
    },
    "Database\Query\Mysql references connector",
    "Database\Query\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
                
        $row = $database->query()
            ->from("tb_example")
            ->first();
        
        return ($row["id"] == 1);
    },
    "Database\Query\Mysql fetches first row",
    "Database\Query\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        
        $rows = $database->query()
            ->from("tb_example")
            ->all();
        
        return (sizeof($rows) == 4);
    },
    "Database\Query\Mysql fetches multiple rows",
    "Database\Query\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        
        $count = $database
            ->query()
            ->from("tb_example")
            ->count();
        
        return ($count == 4);
    },
    "Database\Query\Mysql fetches number of rows",
    "Database\Query\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        
        $rows = $database->query()
            ->from("tb_example")
            ->limit(1, 2)
            ->order("id", "desc")
            ->all();
        
        return (sizeof($rows) == 1 && $rows[0]["id"] == 3);
    },
    "Database\Query\Mysql accepts LIMIT, OFFSET, ORDER and DIRECTION clauses",
    "Database\Query\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        
        $rows = $database->query()
            ->from("tb_example")
            ->where("id != ?", 1)
            ->where("id != ?", 3)
            ->where("id != ?", 4)
            ->all();
        
        return (sizeof($rows) == 1 && $rows[0]["id"] == 2);
    },
    "Database\Query\Mysql accepts WHERE clauses",
    "Database\Query\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        
        $rows = $database->query()
            ->from("tb_example", array(
                "id" => "foo"
            ))
            ->all();
        
        return (sizeof($rows) && isset($rows[0]["foo"]) && $rows[0]["foo"] == 1);
    },
    "Database\Query\Mysql can alias fields",
    "Database\Query\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        
        $rows = $database->query()
            ->from("tb_example", array(
                "tb_example.id" => "foo"
            ))
            ->join("tb_example AS baz", "tb_example.id = baz.id", array(
                "baz.id" => "bar"
            ))
            ->all();
        
        return (sizeof($rows) && $rows[0]["foo"] == $rows[0]["bar"]);
    },
    "Database\Query\Mysql can join tables and alias joined fields",
    "Database\Query\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        
        $result = $database->query()
            ->from("tb_example")
            ->save(array(
                "number" => 3,
                "text" => "foo",
                "boolean" => true
            ));
        
        return ($result == 5);
    },
    "Database\Query\Mysql can insert rows",
    "Database\Query\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        
        $result = $database->query()
            ->from("tb_example")
            ->where("id = ?", 5)
            ->save(array(
                "number" => 3,
                "text" => "foo",
                "boolean" => false
            ));
        
        return ($result == 0);
    },
    "Database\Query\Mysql can update rows",
    "Database\Query\Mysql"
);

THCFrame\Core\Test::add(
    function() use ($options)
    {
        $database = new THCFrame\Database\Database($options);
        $database = $database->initialize();
        $database = $database->connect();
        
        $database->query()
            ->from("tb_example")
            ->delete();
        
        return ($database->query()->from("tb_example")->count() == 0);
    },
    "Database\Query\Mysql can delete rows",
    "Database\Query\Mysql"
);

unset($options);