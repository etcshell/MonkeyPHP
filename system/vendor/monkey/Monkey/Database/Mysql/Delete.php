<?php

namespace Monkey\Database\Mysql;

use Monkey\Database as Query;

class Delete extends Query\Delete
{
    /**
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(Connection $connection, $table)
    {
        $this->app= $connection->app;
        $this->queryIdentifier=uniqid('', TRUE);
        $this->connection=$connection;
        $this->table = $table;
        $this->condition = new Condition($this->app,'AND');
    }
}
