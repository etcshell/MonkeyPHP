<?php
namespace Monkey\Request;

/**
 * RequestCli
 * Cli请求组件
 * @package Monkey\Request
 */
class RequestCli
{
    private
        /**
         * @var \Monkey\App\App
         */
        $app,
        $parameters,
        $body
    ;

    /**
     * @param \Monkey\App\App $app
     */
    public function __construct($app)
    {
        $this->app=$app;
        $this->parameters = $_SERVER["argv"];
    }

    /**
     * 获取命令行参数
     * @param int $index 参数序号
     * @param string $defaultValue 当获取变量失败的时候返回该值,默认该值为null
     * @return string
     */
    public function getParameter($index, $defaultValue = null)
    {
        return isset($this->parameters[$index]) ? $this->parameters[$index] : $defaultValue;
    }

    /**
     * 获取请求方法
     * @return string
     */
    public function getMethod()
    {
        return 'CLI';
    }

    /**
     * 获取请求文件根目录
     * @return string
     */
    public function getRoot()
    {
        return getcwd();
    }

    /**
     * 获取请求内容主体
     * @return string
     */
    public function getBody()
    {
        !$this->body and $this->body = @(string)file_get_contents('php://input');
        return $this->body;
    }

}