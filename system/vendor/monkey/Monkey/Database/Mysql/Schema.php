<?php

namespace Monkey\Database\Mysql;

use Monkey\Database as Query;

class Schema extends Query\Schema
{

    /**
     * @param Connection $connection
     * @param string|null $databaseName 数据库名，默认使用连接中的数据库
     */
    public function __construct(Connection $connection, $databaseName=null)
    {
        $this->app= $connection->app;
        $this->connection=$connection;
        $this->dbConfig=$connection->getConfig();
        if(!$databaseName){
            $databaseName=$this->dbConfig['dbname'];
        }
        $this->explainSchema($databaseName);
    }
}

