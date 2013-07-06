<?php

namespace Monkey\Database\Query\Mysql;

use Monkey\Database\Query;

class Delete extends Query\Delete
{
    /**
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(Connection $connection, $table)
    {
        $this->queryIdentifier=uniqid('', TRUE);
        $this->connection=$connection;
        $this->table = $table;
        $this->condition = new Condition($this->queryIdentifier,'AND');
    }
}
