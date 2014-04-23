<?php
namespace Monkey\View;

use Monkey;
/**
 * html伪静态缓存
 */
class Cache{

    /**
     * @var \Monkey\App\App
     */
    public $app;

    private
        $cache_file,//缓存真实的物理绝对路径
        $expire_file
    ;

    /**
     * @param \Monkey\App\App $app
     * @param string|null $cache_file 默认使用路由作为缓存文件名（不含后缀名，路径相对于应用程序目录）
     */
    public function __construct($app,$cache_file=null)
    {
        $this->app=$app;
        $this->setFile($cache_file ? $cache_file : $app->router()->getPath());
    }

    /**
     * 设置缓存文件
     * @param string $file 相对于临时文件夹
     * @return $this
     */
    public function setFile($file)
    {
        $this->cache_file=$this->app->DIR.'/temp/html'.$file.'.php';
        $this->expire_file=$this->cache_file.'_expire.php';
        $path= dirname($this->cache_file);
        dir_check($path);
        return $this;
    }

    /**
     * @static
     * 设置html缓存
     * @param string $html //html内容
     * @param int $expire 缓存时间，默认保存时间为0（永久保存），24小时为86400*1
     * @return bool
     */
    public function store($html,$expire=0){
        file_put_contents($this->cache_file, $html, LOCK_EX);
        if($expire!=0){
            $expire='<?php'.PHP_EOL.'return '.($expire+ $this->app->TIME).' ;';
            file_put_contents($this->expire_file, $expire, LOCK_EX);
        }
        return true;
    }

    /**
     * 读取html缓存
     * @return string
     */
    public function fetch(){
        if($this->_exists()) return require $this->cache_file;
        else return '';
    }

    /**
     * 载入html缓存并直接显示
     * @return bool             成功返回true，失败返回false
     */
    public function load(){
        if(!$this->_exists())return FALSE;
        require $this->cache_file;
        return TRUE;
    }

    /**
     * @static
     * 删除某个html缓存单元
     * @return bool             成功返回true，失败返回false
     */
    public function delete(){
        if(file_exists($this->cache_file)) unlink ($this->cache_file);
        if(file_exists($this->expire_file)) unlink ($this->expire_file);
    }

    /**
     * 清除所有html缓存
     * @return boolean
     */
    public function clear(){
        return dir_delete($this->app->DIR.'/temp/html');
    }

    /**
     * 是否存在某个或者某些html缓存
     * @return bool|string
     */
    private function _exists(){
        if(!file_exists($this->cache_file)){
            $this->delete();
            return FALSE;
        }
        if(!file_exists($this->expire_file)) return TRUE;
        $expireTime=include $this->expire_file;
        if($expireTime >= $this->app->TIME) return TRUE;
        $this->delete();
        return FALSE;
    }
}