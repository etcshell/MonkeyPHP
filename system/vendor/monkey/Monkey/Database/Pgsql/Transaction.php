<?php
namespace Monkey\Database\Pgsql;

use Monkey\Database as Query;

class Transaction extends Query\Transaction
{
    protected static $transactionsTotal;
    protected
        $conn,//连接驱动
        $pdoTrans,//当前驱动的事务清单
        $name,//当前事务名称
        $rolledBack //当前事务是否已经回滚了
    ;

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

    /**
     * 提交事务
     */
    public function commit()
    {
        if (!$this->pdoTrans[$this->name]) {
            return;
        }
        $this->pdoTrans[$this->name]=false;
        $this->_pop();
    }

    /**
     * 事务回滚
     */
    public function rollback()
    {
        if (!$this->getDepth() or !$this->conn->inTransaction()) {
            throw new \Exception('不在事务处理中.');
        }
        if (!$this->pdoTrans[$this->name]) {
            throw new \Exception('当前事务 '.$this->name.' 已经不存在了.');
        }
        $rolled_back_other_active_savepoint = FALSE;
        while ($savepoint = array_pop($this->pdoTrans)) {
            if ($savepoint == $this->name) {
                if (empty($this->pdoTrans)) {
                    break;
                }
                $this->conn->query('ROLLBACK TO SAVEPOINT ' . $savepoint);
                $this->_pop();
                if ($rolled_back_other_active_savepoint) {
                    throw new \Exception(' 存在未处理的子事务');
                }
                return;
            }
            else {
                $rolled_back_other_active_savepoint = TRUE;
            }
        }
        $this->conn->rollBack();
        if ($rolled_back_other_active_savepoint) {
            throw new \Exception(' 存在未处理的子事务');
        }
        $this->rolledBack = TRUE;
    }

    /**
     * 获取事务深度
     * @return int 返回事务嵌套层数
     */
    public function getDepth()
    {
        return count($this->pdoTrans) ;
    }

    /**
     * 获取事务名称
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 析构方法
     */
    public function __destruct()
    {
        if (!$this->rolledBack) {
            $this->_pop($this->name);
        }
    }

    /**
     * 执行事务query
     */
    protected function _pop() {
        foreach (array_reverse($this->pdoTrans) as $name => $active) {
            if ($active) {
                break;
            }
            unset($this->pdoTrans[$name]);
            if (empty($this->pdoTrans)) {
                if (!$this->conn->commit()) {
                    throw new \Exception('commit failed.');
                }
            }
            else {
                $this->conn->query('RELEASE SAVEPOINT ' . $name);
            }
        }
    }
}