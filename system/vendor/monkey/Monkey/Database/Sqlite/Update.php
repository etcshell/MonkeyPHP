<?php

namespace Monkey\Database\Sqlite;

use Monkey\Database as Query;

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
