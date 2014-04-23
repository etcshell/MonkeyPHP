<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-3-2
 * Time: 下午9:45
 */

namespace Monkey\View;


abstract class Widget extends View {

    protected
        $cacheMe=false,//是否缓存挂件渲染结果
        $expire=0  //渲染结果保存时间
    ;

    /**
     * 获取挂件
     * @return string
     */
    abstract public function getHtml();

    /**
     * 渲染
     * @param string $tplFilename  模板文件名，相对于模板下挂件根目录。
     * @return string 
     */
    public function render($tplFilename)
    {
        return parent::render('/widget'.$tplFilename,false);
    }

} 