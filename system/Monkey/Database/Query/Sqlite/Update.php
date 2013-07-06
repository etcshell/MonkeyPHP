<?php

namespace Monkey\Database\Query\Sqlite;

use Monkey\Database\Query;

class Update extends Query\Update
{
    /**
     * @param Connection $connection
     * @param $table
     */
    public function __construct(Connection $connection, $table)
    {
        $this->connection=$connection;
        $this->queryIdentifier=uniqid('', TRUE);
        $this->table = $table;
        $this->condition = new Condition($this->queryIdentifier,'AND');
    }

}
