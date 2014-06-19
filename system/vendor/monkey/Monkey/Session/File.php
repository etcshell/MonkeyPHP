<?php
namespace Monkey\Session;
use Monkey\Session;

/**
 * File
 * Session的FileCache实现
 * @package Monkey\Session
 */
class File extends Session
{
    private $_idx_node_size = 40;
    private $_idx_node_base;
    private $_data_base_pos = 262588;    //40+20+24*16+16*16*16*16*4;
    private $_schema_item_size = 24;
    private $_header_padding = 20;       //保留空间 放置php标记防止下载
    private $_info_size = 20;            //保留空间 4+16 maxsize|ver
    //40起 添加20字节保留区域
    private $_idx_seq_pos = 40;          //id 计数器节点地址
    private $_dfile_cur_pos = 44;        //id 计数器节点地址
    private $_idx_free_pos = 48;         //id 空闲链表入口地址
    private $_idx_base_pos = 444;        //40+20+24*16
    private $_schema_struct = array('size','free','lru_head','lru_tail','hits','miss');
    private $_ver = '$Rev: 3 $';
    private $_cache_size='15M';          //预设的缓存大小
    private $_exists_file_lock=false;    //是否存在文件锁，设置为false，将模拟文件锁
    private $_cache_path='';             //存储缓存文件的目录
    private $_cache_file='';             //缓存的数据文件名
    private $_data_onCheck=false;        //是否验证数据
    private $_max_size;
    private $_rs;
    private $_bsize_list;
    private $_block_size_list;
    private $_node_struct;
    private $TIME ;

    /**
     * @param \Monkey\App\App $app
     */
    public function __construct($app)
    {
        $this->app=$app;
        $this->config= $config= $app->config()->getComponentConfig('session','file');
        $this->TIME=$app->TIME;
        $this->_cache_path=dir_format($app->TEMP.($config['dir']?$config['dir']:'/sessionCache'));
        $this->_cache_file=$config['filename']?'/'.$config['filename']:'/data';
        $this->_cache_size=$config['filesize']?$config['filesize']:'15M';
        $this->_data_onCheck=$config['check']?$config['check']:false;//是否验证数据
        if(!dir_check($this->_cache_path)) $this->_error('缓存目录校验失败。');
        $this->_workat($this->_cache_path.$this->_cache_file);

        $this->start();
    }

    /**
     * 打开Session文件
     * @param string $path
     * @param string $name
     * @return boolean
     */
    public function open($path, $name){
        //因为没有用文件存储Session，所以用不着
        return true;
    }
    /**
     * 关闭Session文件
     * @return boolean
     */
    public function close(){
        //因为没有用文件存储Session，所以用不着
        return true;
    }
    /**
     * 从session中读取数据
     * @param   string  $sessionid  session的ID
     * @return  mixed   返回session中对应的数据
     */
    public function read($sessionid){
        $out='';
        if($this->fetch($this->_storageKey($sessionid), $out)){
            return $out;
        }  else {
            return '';
        }
    }
    /**
     * 向session中添加数据
     * @param string $sessionid session的ID
     * @param string $data 序列化的Session变量
     * @return boolean
     */
    public function write($sessionid, $data){
        return $this->store($this->_storageKey($sessionid), $data, $this->_expire);
    }
    /**
     * 销毁Session
     * @param string $sessionid session的ID
     * @return boolean
     */
    public function destroy($sessionid){
        return $this->delete($this->_storageKey($sessionid));
    }
    /**
     * 回收过期的session
     * @param integer $lifetime
     * @return boolean
     */
    public function gc($lifetime){
        //无需额外回收,缓存有自己的过期回收机制
        return true;
    }

    /**
     * 读取缓存
     * @param string $key       要读取的缓存项目名称
     * @param mixed &$result   要保存的结果地址
     * @return bool             成功返回true，失败返回false
     */
    public function fetch($key, &$result){
        $result=null;
        if(!$this->_fetch(md5($key),$content))return false;
        $time  =  (int)substr($content,0, 12);
        if($time != -1 && $this->TIME >= $time) return false;
        if($this->_data_onCheck){
            //开启数据校验
            $check  =  substr($content,12, 32);
            $content   =  substr($content,44);
            if($check != md5($content)) return false;//校验错误
        } else {
            $content   =  substr($content,12);
        }
        $result=unserialize($content);//解序列化数据
        return TRUE;
    }
    /**
     * 设置缓存
     * @param string $key 要设置的缓存项目名称
     * @param mixed $value 要设置的缓存项目内容
     * @param int $time 要设置的缓存项目的过期时长，默认保存时间为 -1，永久保存为 0
     * @return bool 保存是成功为true ，失败为false
     */
    public function store($key,$value,$time=-1){
        //数据为空，缓存时间为0,则不缓存
        if(empty($key)||empty($value))return false;
        $data = serialize($value);//将数据序列化
        if($time==-1) $time=$this->_expire;
        if($time!=0) $time=$this->TIME+$time;//过期时间
        //是否开启数据校验
        $check=$this->_data_onCheck?md5($data):'';
        $data = sprintf('%012d',$time).$check.$data;
        return $this->_store(md5($key),$data);//存储数据
    }
    /**
     * 清空缓存
     * @return bool
     */
    public function clear(){
        return $this->_format(true);
    }
    /**
     * 删除缓存文件中指定的内容
     * @param string $key 要删除的项目的key
     * @return bool
     */
    public function delete($key) {
        return $this->_delete(md5($key));
    }
    //以下是辅助函数
    private function _all_schemas(){
        $schema = array();
        for($i=0;$i<16;$i++){
            $this->_seek(60+$i*$this->_schema_item_size);
            $info = unpack(
                'V1'.implode('/V1',$this->_schema_struct),
                fread($this->_rs,$this->_schema_item_size)
            );
            if(!$info['size'])return $schema;
            $info['id'] = $i;
            $schema[$i] = $info;
        }
        return $schema;
    }
    private function _alloc_idx($data){
        $this->_seek($this->_idx_free_pos);
        list(,$list_pos) = unpack('V',fread($this->_rs,4));
        if($list_pos){
            $this->_seek($list_pos*$this->_idx_node_size+$this->_idx_node_base);
            list(,$prev_free_node) = unpack('V',fread($this->_rs,4));
            $this->_puts($this->_idx_free_pos,pack('V',$prev_free_node));
        }else{
            $this->_seek($this->_idx_seq_pos);
            list(,$list_pos) = unpack('V',fread($this->_rs,4));
            $this->_puts($this->_idx_seq_pos,pack('V',$list_pos+1));
        }
        return $this->_create_node($list_pos,$data);
    }
    /**
     * 建立缓存文件
     * @return bool
     */
    private function _create(){
        try{
            $this->_rs = @fopen($this->_cache_file,'wb+');
        }catch(exception $e){
            $this->_error(__METHOD__.':创建缓存文件失败[ '.$this->_cache_file.' ]');
        }
        fseek($this->_rs,0);
        fputs($this->_rs,'<'.'?php exit()?'.'>');
        return $this->_format();
    }
    private function _create_node($pos,$data){
        $this->_puts($pos*$this->_idx_node_size + $this->_idx_node_base
            ,pack('V1V1V1V1V1V1H*',
                $data['next'],
                $data['prev'],
                $data['data'],
                $data['size'],
                $data['lru_right'],
                $data['lru_left'],
                $data['key']
            )
        );
        return $pos;
    }
    private function _dalloc($schema_id,$lru_freed=false){
        $free = $this->_get_schema($schema_id,'free');
        if($free){ //如果lru里有链表
            $this->_seek($free);
            list(,$next) = unpack('V',fread($this->_rs,4));
            $this->_set_schema($schema_id,'free',$next);
            return $free;
        }elseif($lru_freed){
            $this->_error(__METHOD__.':弹出lru区（最少使用区），空间被释放了');
        }else{
            $ds_offset = $this->_get_dcur_pos();
            $size = $this->_get_schema($schema_id,'size');
            if($size+$ds_offset > $this->_max_size){
                $info = $this->_lru_pop($schema_id);
                if($info)return $this->_dalloc($schema_id,$info);
                $this->_error(__METHOD__.':不能分配存储空间');
            }else{
                $this->_set_dcur_pos($ds_offset+$size);
                return $ds_offset;
            }
        }
        return $free;
    }
    /**
     * 删除缓存文件中指定的内容
     * @param string $md5_key 要删除的项目的md5_key
     * @param int|bool $pos 要删除的项目的定位指针偏移量
     * @return bool
     */
    private function _delete($md5_key,$pos=false){
        if(!$pos && !$this->_search($md5_key,$pos)) return false;
        $info = $this->_get_node($pos);
        if(!$info) return false;
        //删除data区域
        if($info['prev']){
            $this->_set_node($info['prev'],'next',$info['next']);
            $this->_set_node($info['next'],'prev',$info['prev']);
        }else{ //改入口位置
            $this->_set_node($info['next'],'prev',0);
            $this->_set_node_root($md5_key,$info['next']);
        }
        $this->_free_dspace($info['size'],$info['data']);
        $this->_lru_delete($info);
        $this->_free_node($pos);
        return $info['prev'];
    }
    private function _dfollow($pos,&$c){
        $c++;
        $this->_seek($pos);
        list(,$next) = unpack('V1',fread($this->_rs,4));
        if($next)return $this->_dfollow($next,$c);
        return $pos;
    }
    /**
     * 提取缓存内容
     * @param string $md5_key 提取缓存项目的键值，为md5数字串
     * @param mixed $return 存放提取缓存项目内容的指针，变量前加&，成功后结果为序列化数据，要反序列化后才能用
     * @return bool
     */
    private function _fetch($md5_key,&$return){
        $locked=$this->_lock_cache_file(false)?true:false;
        if(!$this->_search($md5_key,$offset)){
            $locked && $this->_unlock_cache_file();
            return false;
        }
        $info = $this->_get_node($offset);
        $schema_id = $this->_get_size_schema_id($info['size']);
        if($schema_id===false){
            if($locked) $this->_unlock_cache_file();
            return false;
        }
        $this->_seek($info['data']);
        $return  = fread($this->_rs,$info['size']);
        if($return===false){
            if($locked) $this->_unlock_cache_file();
            return false;
        }
        if(!$locked)return true;
        $this->_lru_push($schema_id,$info['offset']);
        $this->_set_schema($schema_id,
            'hits',
            $this->_get_schema($schema_id,'hits')+1);
        return $this->_unlock_cache_file();
    }
    /**
     * 格式化缓存文件
     * @param bool $truncate
     * @return bool
     */
    private function _format($truncate=false){
        !$this->_lock_cache_file(true,true) && $this->_error(__METHOD__.':不能锁定缓存文件');
        if($truncate){
            $this->_seek(0);
            ftruncate($this->_rs,$this->_idx_node_base);
        }
        $this->_max_size = $this->_parse_str_size($this->_cache_size,15728640); //default:15m
        $this->_puts($this->_header_padding,pack('V1a*',$this->_max_size,$this->_ver));
        ksort($this->_bsize_list);
        $ds_offset = $this->_data_base_pos;
        $i=0;
        foreach($this->_bsize_list as $size=>$count){
            //将预分配的空间注册到free链表里
            $count *= min(3,floor($this->_max_size/10485760));
            $next_free_node = 0;
            for($j=0;$j<$count;$j++){
                $this->_puts($ds_offset,pack('V',$next_free_node));
                $next_free_node = $ds_offset;
                $ds_offset+=intval($size);
            }
            $code = pack(str_repeat('V1',count($this->_schema_struct)),$size,$next_free_node,0,0,0,0);
            $this->_puts(60+$i*$this->_schema_item_size,$code);
            $i++;
        }
        $this->_set_dcur_pos($ds_offset);
        $this->_puts($this->_idx_base_pos,str_repeat("\0",262144));
        $this->_puts($this->_idx_seq_pos,pack('V',1));
        $this->_unlock_cache_file();
        return true;
    }
    private function _free_dspace($size,$pos){
        ($pos>$this->_max_size) && $this->_error (__METHOD__.':释放缓存空间时溢出['.$pos.']');
        $schema_id = $this->_get_size_schema_id($size);
        $free = $this->_get_schema($schema_id,'free');
        if($free){
            $this->_puts($free,pack('V1',$pos));
        }else{
            $this->_set_schema($schema_id,'free',$pos);
        }
        $this->_puts($pos,pack('V1',0));
    }
    private function _free_node($pos){
        $this->_seek($this->_idx_free_pos);
        list(,$prev_free_node) = unpack('V',fread($this->_rs,4));
        $this->_puts($pos*$this->_idx_node_size+$this->_idx_node_base,
            pack('V',$prev_free_node).str_repeat("\0",$this->_idx_node_size-4));
        return $this->_puts($this->_idx_free_pos,pack('V',$pos));
    }
    private function _get_dcur_pos(){
        $this->_seek($this->_dfile_cur_pos);
        list(,$ds_offset) = unpack('V', fread($this->_rs,4) );
        return $ds_offset;
    }
    /**
     * 取得当前偏移量的存储内容
     * @param integer $offset 当前偏移量
     * @return array
     */
    private function _get_node($offset){
        $this->_seek($offset*$this->_idx_node_size + $this->_idx_node_base);
        $info = unpack('V1next/V1prev/V1data/V1size/V1lru_right/V1lru_left/H*key',
            fread($this->_rs,$this->_idx_node_size));
        $info['offset'] = $offset;
        return $info;
    }
    /**
     * 取得键的索引值
     * @param string $md5_key
     * @return mixed
     */
    private function _get_node_root($md5_key){
        $this->_seek(hexdec(substr($md5_key,0,4))*4+$this->_idx_base_pos);
        $a= fread($this->_rs,4);
        list(,$offset) = unpack('V',$a);
        return $offset;
    }
    /**
     * 取得内容的索引值
     * @param integer $offset 键的索引值
     * @param string  $md5_key
     * @param integer $pos 存储内容索引值的指针
     * @return bool
     */
    private function _get_pos_by_key($offset,$md5_key,&$pos){
        if(!$offset){
            $pos = 0;
            return false;
        }
        for(;1;){
            $info = $this->_get_node($offset);
            if($info['key']==$md5_key){
                $pos = $info['offset'];
                return true;
            }elseif($info['next'] && $info['next']!=$offset){
                $offset=$info['next'];
            }else{
                $pos = $offset;
                return false;
            }
        }
        return true;
    }
    private function _get_schema($id,$md5_key){
        $info = array_flip($this->_schema_struct);
        $this->_seek(60+$id*$this->_schema_item_size);
        unpack('V1'.implode('/V1',$this->_schema_struct),
            fread($this->_rs,$this->_schema_item_size));
        $this->_seek(60+$id*$this->_schema_item_size + $info[$md5_key]*4);
        list(,$value) =unpack('V',fread($this->_rs,4));
        return $value;
    }
    /**
     * 获取存储大小对应的索引
     * @param integer $size
     * @return mixed
     */
    private function _get_size_schema_id($size){
        foreach($this->_block_size_list as $k=>$block_size){
            if($size <= $block_size)return $k;
        }
        return false;
    }
    /**
     * 锁定缓存文件
     * @param bool $is_block 如果文件已经锁定是否等待解锁并重新锁定
     * @param bool $whatever 是否坚持等待直到解锁，然后锁定
     * @return bool
     */
    private function _lock_cache_file($is_block,$whatever=false){
        if($this->_exists_file_lock) return flock($this->_rs, $is_block?LOCK_EX:LOCK_EX+LOCK_NB);
        ignore_user_abort(true);
        $support_usleep = version_compare(PHP_VERSION,5,'>=')?20:1;
        $lockfile = $this->_cache_file . '.lck';
        if (file_exists($lockfile)) {
            if (TIME - filemtime($lockfile) > 0){
                unlink($lockfile);
            }elseif(!$is_block){
                return false;
            }
        }
        $lock_ex = @fopen($lockfile, 'x');
        for ($i=0; ($lock_ex === false) && ($whatever || $i < 10); $i++){
            clearstatcache();
            if($support_usleep ==1){
                usleep(rand(9, 999));
            }else{
                sleep(1);
            }
            $lock_ex = @fopen($lockfile, 'x');
        }
        return ($lock_ex !== false);
    }
    private function _lru_delete($info){
        if($info['lru_right']){
            $this->_set_node($info['lru_right'],'lru_left',$info['lru_left']);
        }else{
            $this->_set_schema($this->_get_size_schema_id($info['size']),
                'lru_tail',
                $info['lru_left']);
        }
        if($info['lru_left']){
            $this->_set_node($info['lru_left'],'lru_right',$info['lru_right']);
        }else{
            $this->_set_schema($this->_get_size_schema_id($info['size']),
                'lru_head',
                $info['lru_right']);
        }
        return true;
    }
    private function _lru_pop($schema_id){
        $node = $this->_get_schema($schema_id,'lru_tail');
        if(!$node)return false;
        $info = $this->_get_node($node);
        if(!$info['data']) return false;
        $this->_delete($info['key'],$info['offset']);
        if(!$this->_get_schema($schema_id,'free')){
            $this->_error(__METHOD__.':弹出lru区（最少使用区），但是空间没有被释放');
        }
        return $info;
    }
    private function _lru_push($schema_id,$offset){
        $lru_head = $this->_get_schema($schema_id,'lru_head');
        $lru_tail = $this->_get_schema($schema_id,'lru_tail');
        if((!$offset) || ($lru_head==$offset))return true;
        $info = $this->_get_node($offset);
        $this->_set_node($info['lru_right'],'lru_left',$info['lru_left']);
        $this->_set_node($info['lru_left'],'lru_right',$info['lru_right']);
        $this->_set_node($offset,'lru_right',$lru_head);
        $this->_set_node($offset,'lru_left',0);
        $this->_set_node($lru_head,'lru_left',$offset);
        $this->_set_schema($schema_id,'lru_head',$offset);
        if($lru_tail==0){
            $this->_set_schema($schema_id,'lru_tail',$offset);
        }elseif($lru_tail==$offset && $info['lru_left']){
            $this->_set_schema($schema_id,'lru_tail',$info['lru_left']);
        }
        return true;
    }
    /**
     * 设置缓存大小
     * @param string $str_size 客户设置大小
     * @param integer $default 默认大小
     * @return integer
     */
    private function _parse_str_size($str_size,$default){
        if(!preg_match('/^([0-9]+)\s*([gmk]|)$/i',$str_size,$match)){
            return $default;
        }
        switch(strtolower($match[2])){
            case 'g':
                if($match[1]>1) $this->_error(__METHOD__.':设置缓存大小时越界，最大只能支持【1G】');
                $size = $match[1]<<30;
                break;
            case 'm':
                $size = $match[1]<<20;
                break;
            case 'k':
                $size = $match[1]<<10;
                break;
            default:
                $size = $match[1];
        }
        if($size<=0) $this->_error(__METHOD__.':设置缓存大小时越界，缓存文件大小为0则无意义！');
        if($size<10485760) return 10485760;
        return $size;
    }
    /**
     * 向缓存文件中写入数据
     * @param int $offset 偏移量（写入位置）
     * @param string $data 要写入的数据（序列化后的字符串）
     * @return bool
     */
    private function _puts($offset,$data){
        if($offset >= $this->_max_size*1.5){
            $this->_error(__METHOD__.':向缓存文件中写入数据时，缓存数据的偏移量$offset【'.$offset.'】超出配额！');
        }
        $this->_seek($offset);
        return fwrite($this->_rs,$data);
    }
    protected function _schemaStatus(){
        $return = array();
        foreach($this->_all_schemas() as $schemaItem){
            if($schemaItem['free']){
                $this->_dfollow($schemaItem['free'],$schemaItem['freecount']);
            }
            $return[] = $schemaItem;
        }
        return $return;
    }
    /**
     * 查找项目
     * @param string $md5_key 要查找的项目的key （key为 md5 字符串）
     * @param int $pos 要查找的项目的定位指针偏移量 如果找到节点则$pos=节点本身 返回true 否则 $pos=树的末端 返回false
     * @return mixed
     */
    private function _search($md5_key,&$pos){
        return $this->_get_pos_by_key($this->_get_node_root($md5_key), $md5_key,$pos);
    }
    /**
     * 在缓存文件中定位
     * @param int $offset 在缓存文件中定位的指针偏移量
     * @return int 定位成功为0 ，失败为-1
     */
    private function _seek($offset){
        return fseek($this->_rs,$offset);
    }
    /**
     * 设置存储标签
     * @param integer $pos
     * @return bool
     */
    private function _set_dcur_pos($pos){
        return $this->_puts($this->_dfile_cur_pos,pack('V',$pos));
    }
    private function _set_node($pos,$md5_key,$value){
        if(!$pos){ return false; }
        if(isset($this->_node_struct[$md5_key])){
            return $this->_puts(
                $pos*$this->_idx_node_size
                +$this->_idx_node_base
                +$this->_node_struct[$md5_key][0],
                pack($this->_node_struct[$md5_key][1],$value)
            );
        }else{
            return false;
        }
    }
    private function _set_node_root($md5_key,$value){
        return $this->_puts(
            hexdec(substr($md5_key,0,4))*4+$this->_idx_base_pos,
            pack('V',$value)
        );
    }
    private function _set_schema($schema_id,$md5_key,$value){
        $info = array_flip($this->_schema_struct);
        return $this->_puts(60+$schema_id*$this->_schema_item_size + $info[$md5_key]*4,
            pack('V',$value));
    }
    protected function _status(&$curBytes,&$totalBytes){
        $totalBytes = $curBytes = 0;
        $hits = $miss = 0;
        $schemaStatus = $this->_schemaStatus();
        $totalBytes = $this->_max_size;
        $freeBytes = $this->_max_size - $this->_get_dcur_pos();
        foreach($schemaStatus as $schema){
            $freeBytes+=$schema['freecount']*$schema['size'];
            $miss += $schema['miss'];
            $hits += $schema['hits'];
        }
        $curBytes = $totalBytes-$freeBytes;
        $return[] = array('name'=>'缓存命中','value'=>$hits);
        $return[] = array('name'=>'缓存未命中','value'=>$miss);
        return $return;
    }
    /**
     * 存储缓存内容
     * @param string $md5_key 存储缓存项目的键值，为md5数字串
     * @param mixed $data 存储缓存项目的内容，为已序列化的数据
     * @return bool
     */
    private function _store($md5_key,$data){
        if(!$this->_lock_cache_file(true)) $this->_error(__METHOD__.':不能锁定文件!');
        $size = strlen($data);
        //get list_idx
        $has_key = $this->_search($md5_key,$list_idx_offset);
        $schema_id = $this->_get_size_schema_id($size);
        if($schema_id===false){
            $this->_unlock_cache_file();
            return false;
        }
        if($has_key){
            $hdseq = $list_idx_offset;
            $info = $this->_get_node($hdseq);
            if($schema_id == $this->_get_size_schema_id($info['size'])){
                $dataoffset = $info['data'];
            }else{
                //破掉原有lru
                $this->_lru_delete($info);
                if(!($dataoffset = $this->_dalloc($schema_id))){
                    $this->_unlock_cache_file();
                    return false;
                }
                $this->_free_dspace($info['size'],$info['data']);
                $this->_set_node($hdseq,'lru_left',0);
                $this->_set_node($hdseq,'lru_right',0);
            }
            $this->_set_node($hdseq,'size',$size);
            $this->_set_node($hdseq,'data',$dataoffset);
        }else{
            if(!($dataoffset = $this->_dalloc($schema_id))){
                $this->_unlock_cache_file();
                return false;
            }
            $hdseq = $this->_alloc_idx(array(
                'next'=>0,
                'prev'=>$list_idx_offset,
                'data'=>$dataoffset,
                'size'=>$size,
                'lru_right'=>0,
                'lru_left'=>0,
                'key'=>$md5_key,
            ));
            if($list_idx_offset>0){
                $this->_set_node($list_idx_offset,'next',$hdseq);
            }else{
                $this->_set_node_root($md5_key,$hdseq);
            }
        }
        if($dataoffset>$this->_max_size) $this->_error(__METHOD__.':分配缓存空间时出错！');
        $this->_puts($dataoffset,$data);
        $this->_set_schema($schema_id,'miss',$this->_get_schema($schema_id,'miss')+1);
        $this->_lru_push($schema_id,$hdseq);
        $this->_unlock_cache_file();
        return true;
    }
    /**
     * 解除文件锁定
     * @return bool
     */
    private function _unlock_cache_file(){
        if($this->_exists_file_lock) return flock($this->_rs, LOCK_UN);
        ignore_user_abort(false);
        return @unlink($this->_cache_file.'.lck');
    }
    /**
     * 设定缓存文件
     * @param string $file 缓存文件名（含路径）
     * @return bool
     */
    private function _workat($file){
        $this->_cache_file = $file.'.php';
        $this->_bsize_list = array(
            512=>10,
            3<<10=>10,
            8<<10=>10,
            20<<10=>4,
            30<<10=>2,
            50<<10=>2,
            80<<10=>2,
            96<<10=>2,
            128<<10=>2,
            224<<10=>2,
            256<<10=>2,
            512<<10=>1,
            1024<<10=>1,
        );
        $this->_node_struct = array(
            'next'=>array(0,'V'),
            'prev'=>array(4,'V'),
            'data'=>array(8,'V'),
            'size'=>array(12,'V'),
            'lru_right'=>array(16,'V'),
            'lru_left'=>array(20,'V'),
            'key'=>array(24,'H*'),
        );
        if(!file_exists($this->_cache_file)){
            $this->_create();
        }else{
            $this->_rs = fopen($this->_cache_file,'rb+') or $this->_error(__METHOD__.':不能打开缓存文件[ '.realpath($this->_cache_file).' ]');
            $this->_seek($this->_header_padding);
            $info = unpack('V1max_size/a*ver',fread($this->_rs,$this->_info_size));
            if($info['ver']!=$this->_ver){
                $this->_format(true);
            }else{
                $this->_max_size = $info['max_size'];
            }
        }
        $this->_idx_node_base = $this->_data_base_pos+$this->_max_size;
        $this->_block_size_list = array_keys($this->_bsize_list);
        sort($this->_block_size_list);
        return true;
    }
    private function _error($message){
        throw new \Exception('文件缓存错误：'.$message);
    }
}