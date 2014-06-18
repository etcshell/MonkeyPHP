<?php

namespace Monkey\Database\Mysql;

use Monkey\Database as Query;

class Select extends Query\Select
{

    /**
     * @param Connection $connection
     * @param string $table
     * @param null $alias
     * @param array $options
     */
    public function __construct(Connection $connection, $table, $alias = NULL, $options = array())
    {
        $this->app=$connection->app;
        $this->connection=$connection;
        $this->queryIdentifier=uniqid('', TRUE);
        $conjunction  = isset($options['conjunction']) ? $options['conjunction'] : 'AND';
        $this->where  = new Condition($this->app,$conjunction);
        $this->having = new Condition($this->app,$conjunction);
        $this->addJoin(NULL, $table, $alias);
    }
}