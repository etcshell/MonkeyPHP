<?php

namespace Monkey\Database\Query\Pgsql;

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

    protected function compile()
    {
        $query=array();
        $query['sql'] = 'DELETE FROM {:' . $this->table . ':} ';
        if (count($this->condition)) {
            $query['sql'] .= "\nWHERE " . $this->condition->getString($this->queryIdentifier);
            $query['arguments']= $this->condition->getArguments($this->queryIdentifier);
        }
        return $query;
    }
}
