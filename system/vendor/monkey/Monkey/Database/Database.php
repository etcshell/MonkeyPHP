<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey\Database
 * @author    黄易 <582836313@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\Database;

use Monkey;

/**
 * Class Database
 *
 * 数据库组件
 *
 * @package Monkey\Database
 */
class Database
{
    /**
     * 应用对象
     *
     * @var Monkey\App
     */
    private $app;

    /**
     * 数据组件配置
     *
     * @var array
     */
    private $config = array();

    /**
     * 连接配置池
     *
     * @var array
     */
    private $pool = array();

    /**
     * 默认连接名
     *
     * @var string
     */
    private $default;

    /**
     * 连接对象池
     *
     * @var array
     */
    private $oConnections = array();

    /**
     * @param Monkey\App $app
     *
     * @throws \Exception
     */
    public function __construct($app)
    {
        //效验pdo组件是否存在
        if (!extension_loaded('pdo')) {
            throw new \Exception('没有安装pdo驱动扩展,请先在php.ini中配置安装pdo！', 1024);
        }

        //载入数据组件配置
        $this->app = $app;
        $config = $app->config()->getComponentConfig('database', 'default');
        $this->config = $config;
        isset($config['pool']) and $this->pool = $config['pool'];
        isset($config['default_connection']) and $this->default = $config['default_connection'];

        //设置默认连接
        if (!$this->default || !isset($this->pool[$this->default])) {
            reset($this->pool);
            $this->default = key($this->pool);
        }
    }

    /**
     * 获取指定名称的查询连接
     *
     * @param string|null $name 连接名称。留空时，使用默认连接
     *
     * @return \Monkey\Database\Connection|bool|null
     *
     * @throws \Exception
     *
     * 1.正确返回\Monkey\Database\Connection；
     * 2.不存在指定的连接（包括默认连接）返回null；
     * 3.连接失败返回false。
     */
    public function getConnection($name = null)
    {
        //验证连接名
        if (empty($name)) {
            $name = $this->default;
        } else {
            $name = strtolower($name);
            $name = isset($this->pool[$name]) ? $name : null;
        }
        if (!$name) {
            return null;
        }

        //提取连接对象
        if (!isset($this->oConnections[$name])) {
            $this->oConnections[$name] = $this->tryConnecting($this->config[$name], $name);
        }

        if (empty($this->oConnections[$name])) {
            throw new \Exception('数据库连接失败');
        }

        return $this->oConnections[$name];
    }

    /**
     * 尝试具体配置的连接
     *
     * @param array $config 连接配置
     * @param string $name 连接名称，留空表示测试连接
     *
     * @throws \PDOException
     *
     * @return \Monkey\Database\Connection|false
     */
    public function tryConnecting($config, $name = 'test')
    {
        //设置连接类名
        $class = ucfirst(strtolower($config['protocol']));
        $class = ($class == 'Mysql' ? '' : '\\' . $class); //如果是Mysql驱动，直接使用父类，目的是获得更高的效率
        $class = __NAMESPACE__ . $class . '\\Connection';

        try {
            //创建连接对象
            $connect = new $class($this->app, $name, $config);

        } catch (\PDOException $e) {
            //处理连接错误，记录错误日志
            $error = array(
                'error_title' => '连接到PDO时出错。',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            );
            $this->app->logger()->sql($error);

            throw $e;
        }

        return $connect;
    }

    /**
     * 尝试连接池中的连接
     *
     * @param string $name 连接名称，留空表示测试连接
     *
     * @return \Monkey\Database\Connection|false
     */
    public function tryPool($name)
    {
        if (isset($this->pool[$name])) {
            return $this->tryConnecting($this->pool[$name], $name);
        }

        return false;
    }

    /**
     * 注销方法
     */
    public function __destruct()
    {
        //销毁配置
        $this->config = null;
        $this->pool = null;
        $this->default = null;

        //逐一销毁连接对象
        if ($this->oConnections) {
            foreach ($this->oConnections as $key => $connection) {
                $connection = null;
                $this->oConnections[$key] = null;
            }
            $this->oConnections = null;
        }
    }

}