<?php
namespace Library;

use Monkey;
/**
 * db_ghost
 * @category   数据库备份恢复工具
 * @package    扩展库
 * @author     HuangYi
 * @copyright  Copyright (c) 2012-4-1——至今
 * @license    New BSD License
 * @version    $Id: db_ghost.class.php 版本号 2013-1-1  $
 *
 */
class DatabaseGhost {
    /**
     * @var \PDO
     */
    private $_db;
    private $_data_dir;
    private $_structure_file='_tables.structure_sql.php';
    private $_data_fix='.sql.php';
    /**
     * 数据库备份恢复工具
     * @param string $data_dir 存放数据库备份文件的目录
     * @param \PDO $pdo 数据库层管理器
     */
    public function __construct($data_dir, \PDO $pdo) {
        $this->_data_dir=$data_dir;
        dir_check($data_dir);
        dir_check_writable($data_dir);
        $this->_db=$pdo;
    }
    /**
     * 获取表名列表
     * @param string $dbname 数据库名，为空时返回当前连接的数据库
     * @return array
     */
    public function get_tables($dbname=null){
        $sql='SHOW TABLES FROM '.$dbname;
        $tmpArry= $this->query($sql)->fetchAll();
        $tables=array();
        foreach ($tmpArry as $table) {
            $tables[]=  array_shift($table);
        }
        return $tables;
    }
    /**
     * 备份数据库
     * @param string|array $table  表名或表名数组，为空时备份所有表
     * @return bool
     */
    public function backup($table=null){
        $tables=empty($table)?$this->get_tables():(is_array($table)?$table:array($table));
        $file_pre=$this->_data_dir.'/'.date('Ymd',TIME);
        $tables_structure='';$i=0;$data_temp=array();$data_head='';$backup_data='';$file='';
        foreach ($tables as $table) {
            $tables_structure.='------------------------------------------'.PHP_EOL
                                   .'-- 表名：'.$table.PHP_EOL
                                   .'--------'.PHP_EOL
                                   .'DROP TABLE IF EXISTS '.$table.';'.PHP_EOL;
            $createtable=$this->query('SHOW CREATE TABLE '.$table)->fetchAll();
            $createtable=end($createtable[0]);
            $createtable=  preg_replace('/AUTO_INCREMENT=\d*/i', 'AUTO_INCREMENT=0', $createtable);
            $tables_structure.= $createtable.';'.PHP_EOL;
            $data=$this->query('SELECT * FROM '.$table)->fetchAll();
            $data_head='INSERT INTO '.$table.' VALUES'.PHP_EOL;
            foreach ($data as $row_index=>$row) {
                foreach ($row as $col_key=>$col_value) {
                    $row[$col_key]=$this->_db->quote($col_value);
                }
                $data_temp[]='('. implode(', ', $row).')';
                $data[$row_index]='';
                if($i<200){
                    $i++;
                }  else {
                    $backup_data.=$data_head.implode(','.PHP_EOL, $data_temp).';'.PHP_EOL;
                    $data_temp=array();
                    $i=0;
                }
            }
            if(!empty($data_temp))$backup_data.=$data_head.implode(','.PHP_EOL, $data_temp).';'.PHP_EOL;
            $file=$file_pre.'/'.$table.$this->_data_fix;
            dir_check(dirname($file));
            file_put_contents($file,$backup_data,LOCK_EX);
            $i=0; $data_temp=array();$data_head='';$backup_data='';
        }
        $file=$file_pre.'/'.$this->_structure_file;
        dir_check(dirname($file));
        file_put_contents($file,$tables_structure,LOCK_EX);
        return true;
    }
    /**
     * 恢复数据库
     * （只能恢复由本类备份的数据）
     * @param string $date_Ymd  类似 20120722 的字符串
     * @return boolean          成功返回TRUE，失败返回FALSE
     */
    public function restore($date_Ymd){
        if(empty($date_Ymd))return false;
        $targetDir=$this->_data_dir.'/'.$date_Ymd;
        $structure_file=$targetDir.'/'.$this->_structure_file;
        if(!file_exists($structure_file))return FALSE;
        if(!$this->import_sql_file($structure_file))return FALSE;
        $handle = opendir($targetDir);
        $target='';$ext_len=strlen($this->_data_fix);
        $successful=TRUE;
        if($handle){
            while (($item = readdir($handle)) !== false) {
                if ($item == '.' || $item == '..') continue;
                $target=$targetDir . '/' . $item;
                if (is_dir($target)) continue;
                if (substr($item, 0 - $ext_len) !== $this->_data_fix) continue;
                if(!$this->import_sql_file($target)) {$successful=FALSE;}
            }
            closedir($handle);
        }
        return $successful;
    }
    /**
     * 导入单个sql文件
     * @param string $sql_file      sql文件路径
     * @param string $old_prefix    原来的表名前缀，如果前缀无变化请留空
     * @param string $new_prefix    新的表名前缀，新表名前缀使用数据库连接配置指定时请留空
     * @param string $separator     sql文件中，sql语句分割符号，可为";\n"或";\r\n"或";\r"
     * @return boolean              成功返回TRUE，失败返回FALSE
     */
    public function import_sql_file($sql_file,$old_prefix='',$new_prefix='',$separator=';\n'){
    	$sqlarray= $this->parse_file($sql_file,$old_prefix,$new_prefix,$separator);
        if(empty($sqlarray)){return TRUE;}
        $successful=TRUE;
        $this->_db->beginTransaction();
        try{
            foreach ($sqlarray as $sql) {
                if(!$this->_db->exec($sql)){
                    $successful=FALSE;
                }
            }
        } catch (\Exception $e){
            $successful=FALSE;
        }
        if($successful){
            $this->_db->commit();
        }  else {
            $this->_db->rollBack();
        }
        return $successful;
    }
    /**
     * 解析mysql导出的数据库文件
     * 返回由mysql语句组成的数组，可用于导入mysql
     * @param string $sql_file sql文件路径
     * @param string $new_prefix 新表前缀
     * @param string $old_prefix 原表前缀
     * @param string $separator 分隔符 参数可为";\n"或";\r\n"或";\r"
     * @return array 返回由mysql语句组成的数组，可用于导入mysql
     */
    public function parse_file($sql_file,$new_prefix,$old_prefix='',$separator=';\n') {
        $result=array();
        $commenter = array('#','--');
          //判断文件是否存在
        if(!file_exists($sql_file)) return false;
        $content = file_get_contents($sql_file);   //读取sql文件
        if($content[0]=='<') $content=preg_replace('/[^\n]*\n/', '', $content, 1);
        if(!empty($old_prefix)) $content=str_replace($old_prefix,$new_prefix, $content);//替换前缀
        //通过sql语法的语句分割符进行分割
        $segment = explode($separator,trim($content));
        //去掉注释和多余的空行
        $data=array();
        foreach($segment as  $statement){
            $sentence = explode("\n",$statement);
            $newStatement = array();
            foreach($sentence as $subSentence){
                if('' != trim($subSentence)){
                    //判断是会否是注释
                    $isComment = false;
                    foreach($commenter as $comer){
                        if(preg_match("/^(".$comer.")/is",trim($subSentence))){
                            $isComment = true;
                            break;
                        }
                    }
                    //如果不是注释，则认为是sql语句
                    if(!$isComment) $newStatement[] = $subSentence;
                }
            }
            $data[] = $newStatement;
        }
        //组合sql语句
        foreach($data as  $statement){
            $newStmt = '';
            foreach($statement as $sentence){
                $newStmt = $newStmt.trim($sentence)."\n";
            }
            if(!empty($newStmt)){
                 $result[] = $newStmt;
            }
        }
        return $result;
    }
    private function query($sql){
        $stmt=$this->_db->prepare($sql);
        $stmt->execute();
        return $stmt;
    }
}