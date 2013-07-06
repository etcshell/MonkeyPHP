<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Administrator
 * Date: 13-5-9
 * Time: 下午3:30
 * To change this template use File | Settings | File Templates.
 */

namespace Monkey\Logger\Type;


interface _Interface
{

    /**
     * 构造方法
     * @param \Monkey\Monkey|null $monkey 依赖类
     * @param mixed|null $config 配置
     */
    public function __construct($monkey,$config);

    /**
     * 添加一条日志信息
     * @param string|array $data 日志信息
     */
    public function put(array $data);

    /**
     * 批量添加日志信息
     * @param array $datas 日志信息
     */
    public function add(array $datas);

    /**
     * 写入日志文件
     * 不需要手动调用
     * @return bool
     */
    public function write();

}