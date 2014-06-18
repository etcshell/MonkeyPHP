<?php

namespace Monkey\Database\Mssql;

use Monkey\Database as Query;

class Insert extends Query\Insert
{

    /**
     * @param Connection $connection
     * @param $table
     */
    public function __construct(Connection $connection, $table)
    {
        $this->app=$connection->app;
        $this->connection=$connection;
        $this->table = $table;
    }

}
