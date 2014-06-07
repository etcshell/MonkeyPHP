<?php
namespace Monkey\Permission;


class Permission
{
    private
        $anonymous='anonymous',
        /**
         * @var callable
         */
        $authFinder
    ;

    /**
     * @param \Monkey\App\App $app
     */
    public function __construct($app)
    {
    }

    /**
     * 设置查找行为授权的方法
     * @param \Closure $finder
     * $finder方法将获得行为编码参数，见behaviourCoder方法，
     * $finder方法返回值应为allowed、denied、own、others之一，
     * $finder方法返回值分别代表具有该角色的：所有用户允许、所有用户拒绝、仅创建者允许、除开创建者允许
     */
    public function setAuthFinder(\Closure $finder)
    {
        $this->authFinder=$finder;
    }

    /**
     * 尽可能的获取指定角色的行为授权
     * @param string $resource
     * @param string $action
     * @param string $role
     * @return bool|string
     */
    public function getRoleAuth($resource, $action, $role='')
    {
        if(empty($role)) return $this->getAnonymousAuth($resource, $action);
        $auth=$this->findRoleAuth($resource, $action, $role);
        if(empty($auth)) return $this->getAnonymousAuth($resource, $action);
        return $auth;
    }

    /**
     * 获取匿名角色的行为授权
     * @param string $resource
     * @param string $action
     * @return bool|string
     */
    public function getAnonymousAuth($resource, $action)
    {
        return $this->findRoleAuth($resource, $action, $this->anonymous);
    }

    /**
     * 查找具体角色的行为授权
     * @param string $resource
     * @param string $action
     * @param string $role
     * @return bool|string
     */
    private function findRoleAuth($resource, $action, $role)
    {
        $auth=false;
        $behaviour=$this->behaviourCoder($resource, $action, $role);
        $auth=call_user_func($this->authFinder,$behaviour);
        return $auth;
    }

    /**
     * 对行为编码
     * @param string $resource
     * @param string $action
     * @param string $role
     * @return string
     */
    public function behaviourCoder($resource, $action, $role)
    {
        return md5($resource.'#'.$action.'#'.$role);
    }
}

/*

behaviour(资源， 动作， 角色): 访问限制
--------------------

资源1，添加，角色1:     allowed

资源1，删除，角色1:     others

资源1，修改，角色1:     own

资源1，查看，角色1:     allowed

资源1，添加，角色1的上级:  denied

资源1，删除，角色1的上级:  denied

资源1，修改，角色1的上级:  denied

资源1，查看，角色1的上级:  allowed

资源1，查看，角色1的下级:  denied

 */