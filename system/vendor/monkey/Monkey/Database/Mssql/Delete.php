<?php

namespace Monkey\Database\Mssql;

use Monkey\Database as Query;

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
