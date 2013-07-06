<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Administrator
 * Date: 13-5-9
 * Time: 下午4:01
 * To change this template use File | Settings | File Templates.
 */

namespace Monkey\Response;


class Status
{
    private
        $statusCode  = 200,//状态码
        $statusText  = 'OK',//状态说明
        $statusTexts//状态列表
    ;
    public function __construct()
    {
        $this->statusTexts = array(
            '100' => 'Continue',
            '101' => 'Switching Protocols',
            '200' => 'OK',
            '201' => 'Created',
            '202' => 'Accepted',
            '203' => 'Non-Authoritative Information',
            '204' => 'No Content',
            '205' => 'Reset Content',
            '206' => 'Partial Content',
            '207' => 'Multi-Status',
            '300' => 'Multiple Choices',
            '301' => 'Moved Permanently',
            '302' => 'Found',
            '303' => 'See Other',
            '304' => 'Not Modified',
            '305' => 'Use Proxy',
            '306' => '(Unused)',
            '307' => 'Temporary Redirect',
            '400' => 'Bad Request',
            '401' => 'Unauthorized',
            '402' => 'Payment Required',
            '403' => 'Forbidden',
            '404' => 'Not Found',
            '405' => 'Method Not Allowed',
            '406' => 'Not Acceptable',
            '407' => 'Proxy Authentication Required',
            '408' => 'Request Timeout',
            '409' => 'Conflict',
            '410' => 'Gone',
            '411' => 'Length Required',
            '412' => 'Precondition Failed',
            '413' => 'Request Entity Too Large',
            '414' => 'Request-URI Too Long',
            '415' => 'Unsupported Media Type',
            '416' => 'Requested Range Not Satisfiable',
            '417' => 'Expectation Failed',
            '422' => 'Unprocessable Entity',
            '423' => 'Locked',
            '424' => 'Failed Dependency',
            '500' => 'Internal Server Error',
            '501' => 'Not Implemented',
            '502' => 'Bad Gateway',
            '503' => 'Service Unavailable',
            '504' => 'Gateway Timeout',
            '505' => 'HTTP Version Not Supported',
            '507' => 'Insufficient Storage',
            '509' => 'Bandwidth Limit Exceeded'
        );
    }

    /**
     * 设置响应头状态码
     * @param $code
     * @param null $note 状态码说明
     */
    public function setCode($code, $note = null)
    {
        $this->statusCode = $code;
        $this->statusText = null !== $note ? $note : $this->statusTexts[$code];
    }

    /**
     * 获取响应状态
     * @return string
     */
    public function get()
    {
        return 'HTTP/1.1 '.$this->statusCode.' '.$this->statusText;
    }

    /**
     * 获取响应头状态代码
     * @return int|string
     */
    public function getCode()
    {
        return $this->statusCode;
    }

    /**
     * 获取响应状态说明
     * @return string
     */
    public function getText()
    {
        return $this->statusText;
    }

}