<?php

namespace Monkey\Database\Sqlite;

use Monkey\Database as Query;

class Transaction extends Query\Transaction
{

    public function __construct(Connection $connection, $connectionName, $transName=null)
    {
        $this->conn=$connection;
        self::$transactionsTotal += array($connectionName=>array());
        $this->pdoTrans= &self::$transactionsTotal[$connectionName];

        $depth = $this->getDepth()+1;
        if($depth==1 ) {
            $transName = 'default_transaction';
        }
        if(!$transName) {
            $transName = 'savepoint_' . $depth;
        }
        $this->name=$transName;
        if ($this->pdoTrans[$this->name]) {
            throw new \Exception($transName . ' 这个事务名称已经被占用了，不能重复开启。');
        }
        if ($this->conn->inTransaction()) {
            $this->conn->query('SAVEPOINT ' . $transName);
        }
        else {
            $this->conn->beginTransaction();
        }
        $this->pdoTrans[$this->name]=$transName;
    }

}