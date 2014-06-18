<?php

namespace Monkey\Database\Mysql;

use Monkey\Database as Query;

class Update extends Query\Update
{

    /**
     * @param Connection $connection
     * @param $table
     */
    public function __construct(Connection $connection, $table)
    {
        $this->app=$connection->app;
        $this->connection=$connection;
        $this->queryIdentifier=uniqid('', TRUE);
        $this->table = $table;
        $this->condition = new Condition($this->app,'AND');
    }
}
