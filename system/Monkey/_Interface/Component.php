<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Administrator
 * Date: 13-5-15
 * Time: 下午9:33
 * To change this template use File | Settings | File Templates.
 */

namespace Monkey\_Interface;


interface Component {
    /**
     * 组件初始化注入
     * @param \Monkey\Monkey|null $monkey 依赖类
     * @param mixed|null $config 配置
     * @return mixed
     */
    public function _initialize($monkey=null,$config=null);

    /**
     * 获取组件实例
     * @return object
     */
    public static function _instance();
}