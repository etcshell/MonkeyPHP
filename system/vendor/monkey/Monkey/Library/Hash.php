<?php
namespace Library;

/**
 * hash一致性工具
 */
class Hash {
    private $_v_notes=array();//虚拟节点
    private $_v_notes_sorted=false;
    /**
     * hash一致性负载均衡算法
     */
    public function __construct() {}
    /**
     * 添加服务器节点
     * @param mixed $server_key 服务器key string或integer
     * @param string $eigenvalue 服务器特征值
     */
    public function addNote($server_key,$eigenvalue){
        for($i=0;$i<31;$i++){
            $this->_v_notes[$this->_getHashKey($i.$server_key.$eigenvalue)] =$server_key;
        }
    }
    /**
     * 导出虚拟节点
     * @return array
     */
    public function exportVnotes(){
        return $this->_v_notes;
    }
    /**
     * 导入虚拟节点
     * @param array $_v_notes
     */
    public function importVnotes(array $_v_notes){
        $this->_v_notes=$_v_notes;
    }
    /**
     * 删除服务器节点
     * @param mixed $server_key 服务器key string或integer
     */
    public function deleteNote($server_key){
        foreach ($this->_v_notes as $hash_key => $_server_key) {
            if($_server_key==$server_key) unset($this->_v_notes[$hash_key]);
        }
    }
    /**
     * 获取分配到的服务器key
     * @param string $search_eigenvalue 要搜索的特征值，一般是当前web服务器的http_host（含端口号）
     * @return mixed $server_key 服务器key string或integer
     */
    public function getServerKey($search_eigenvalue=null){
        if(empty($this->_v_notes)) return false;
        if(!$this->_v_notes_sorted){
            ksort($this->_v_notes,SORT_REGULAR);
            $this->_v_notes_sorted=TRUE;
        }
        if(!$search_eigenvalue){
            $search_eigenvalue=$_SERVER['HTTP_HOST'].config()->front_filename;
        }
        $search_eigenvalue=  $this->_getHashKey($search_eigenvalue);
        $i=-1;
        foreach ($this->_v_notes as $hash_key => $serverkey) {
            if($i==-1 ) $i=$hash_key;
            if($search_eigenvalue<$hash_key){
                $i=$hash_key;
                break;
            }
        }
        return $this->_v_notes[$i];
    }
    private function _getHashKey($key){
        return abs(crc32($key));
    }
}