<?php
namespace Library;

use Monkey;

/**
 * DatabaseGhost
 * 数据库备份恢复类
 * @package Library
 */
class DatabaseGhost {

    /**
     * @var \PDO
     */
    private $db;
    private $dataDir;
    private $structureFile = '_tables.structure_sql.php';
    private $dataFix = '.sql.php';

    /**
     * 数据库备份恢复工具
     * @param string $dataDir 存放数据库备份文件的目录
     * @param \PDO $pdo 数据库层管理器
     */
    public function __construct($dataDir, \PDO $pdo) {
        $dataDir = dir_format($dataDir);
        $this->dataDir = $dataDir;
        dir_check($dataDir);
        dir_check_writable($dataDir);
        $this->db = $pdo;
    }

    /**
     * 获取表名列表
     * @param string $dbname 数据库名，为空时返回当前连接的数据库
     * @return array
     */
    public function getTables($dbname = null) {
        $sql = 'SHOW TABLES FROM ' . $dbname;
        $tmpArray = $this->query($sql)->fetchAll();
        $tables = array();
        foreach ($tmpArray as $table) {
            $tables[] = array_shift($table);
        }
        return $tables;
    }

    /**
     * 备份数据库
     * @param string|array $table 表名或表名数组，为空时备份所有表
     * @return bool
     */
    public function backup($table = null) {
        $tables = empty($table) ? $this->getTables() : (is_array($table) ? $table : array($table));
        $filePre = $this->dataDir . '/' . date('Ymd', time());
        $tablesStructure = '';
        $i = 0;
        $dataTemp = array();
        $dataHead = '';
        $backupData = '';
        $file = '';
        foreach ($tables as $table) {
                       $tablesStructure.='------------------------------------------'.PHP_EOL
                                   .'-- 表名：'.$table.PHP_EOL
                                   .'--------'.PHP_EOL
                                   .'DROP TABLE IF EXISTS '.$table.';'.PHP_EOL;
            $createtable = $this->query('SHOW CREATE TABLE ' . $table)->fetchAll();
            $createtable = end($createtable[0]);
            $createtable = preg_replace('/AUTO_INCREMENT=\d*/i', 'AUTO_INCREMENT=0', $createtable);
            $tablesStructure .= $createtable . ';' . PHP_EOL;
            $data = $this->query('SELECT * FROM ' . $table)->fetchAll();
            $dataHead = 'INSERT INTO ' . $table . ' VALUES' . PHP_EOL;
            foreach ($data as $rowIndex => $row) {
                foreach ($row as $colKey => $colValue) {
                    $row[$colKey] = $this->db->quote($colValue);
                }
                $dataTemp[] = '(' . implode(', ', $row) . ')';
                $data[$rowIndex] = '';
                if ($i < 200) {
                    $i++;
                }
                else {
                    $backupData .= $dataHead . implode(',' . PHP_EOL, $dataTemp) . ';' . PHP_EOL;
                    $dataTemp = array();
                    $i = 0;
                }
            }
            if (!empty($dataTemp)) {
                $backupData .= $dataHead . implode(',' . PHP_EOL, $dataTemp) . ';' . PHP_EOL;
            }
            $file = $filePre . '/' . $table . $this->dataFix;
            dir_check(dirname($file));
            file_put_contents($file, $backupData, LOCK_EX);
            $i = 0;
            $dataTemp = array();
            $dataHead = null;
            $backupData = '';
        }
        $file = $filePre . '/' . $this->structureFile;
        dir_check(dirname($file));
        file_put_contents($file, $tablesStructure, LOCK_EX);
        return true;
    }

    /**
     * 恢复数据库
     * （只能恢复由本类备份的数据）
     * @param string $dateYmd 类似 20120722 的字符串
     * @return boolean          成功返回TRUE，失败返回FALSE
     */
    public function restore($dateYmd) {
        if (empty($dateYmd)) {
            return false;
        }
        $targetDir = $this->dataDir . '/' . $dateYmd;
        $structureFile = $targetDir . '/' . $this->structureFile;
        if (!file_exists($structureFile)) {
            return false;
        }
        if (!$this->importSqlFile($structureFile)) {
            return false;
        }
        $handle = opendir($targetDir);
        $target = null;
        $extLen = strlen($this->dataFix);
        $successful = true;
        if ($handle) {
            while (($item = readdir($handle)) !== false) {
                if ($item == '.' || $item == '..') {
                    continue;
                }
                $target = $targetDir . '/' . $item;
                if (is_dir($target)) {
                    continue;
                }
                if (substr($item, 0 - $extLen) !== $this->dataFix) {
                    continue;
                }
                if (!$this->importSqlFile($target)) {
                    $successful = false;
                }
            }
            closedir($handle);
        }
        return $successful;
    }

    /**
     * 导入单个sql文件
     * @param string $sqlFile sql文件路径
     * @param string $oldPrefix 原来的表名前缀，如果前缀无变化请留空
     * @param string $newPrefix 新的表名前缀，新表名前缀使用数据库连接配置指定时请留空
     * @param string $separator sql文件中，sql语句分割符号，可为";\n"或";\r\n"或";\r"
     * @return boolean              成功返回TRUE，失败返回FALSE
     */
    public function importSqlFile($sqlFile, $oldPrefix = '', $newPrefix = '', $separator = ";\n") {
        $sqlArray = $this->parseFile($sqlFile, $oldPrefix, $newPrefix, $separator);
        if (empty($sqlArray)) {
            return true;
        }
        $successful = true;
        $this->db->beginTransaction();
        try {
            foreach ($sqlArray as $sql) {
                if (!$this->db->exec($sql)) {
                    $successful = false;
                }
            }
        }
        catch (\Exception $e) {
            $successful = false;
        }
        if ($successful) {
            $this->db->commit();
        }
        else {
            $this->db->rollBack();
        }
        return $successful;
    }

    /**
     * 解析mysql导出的数据库文件
     * 返回由mysql语句组成的数组，可用于导入mysql
     * @param string $sqlFile sql文件路径
     * @param string $newPrefix 新表前缀
     * @param string $oldPrefix 原表前缀
     * @param string $separator 分隔符 参数可为";\n"或";\r\n"或";\r"
     * @return array 返回由mysql语句组成的数组，可用于导入mysql
     */
    public function parseFile($sqlFile, $newPrefix, $oldPrefix = '', $separator = ";\n") {
        $result = array();
        $commenter = array('#', '--');
        //判断文件是否存在
        if (!file_exists($sqlFile)) {
            return false;
        }
        $content = file_get_contents($sqlFile); //读取sql文件
        if ($content[0] == '<') {
            $content = preg_replace('/[^\n]*\n/', '', $content, 1);
        }
        if (!empty($oldPrefix)) {
            $content = str_replace($oldPrefix, $newPrefix, $content);
        } //替换前缀
        //通过sql语法的语句分割符进行分割
        $segment = explode($separator, trim($content));
        //去掉注释和多余的空行
        $data = array();
        foreach ($segment as $statement) {
            $sentence = explode("\n", $statement);
            $newStatement = array();
            foreach ($sentence as $subSentence) {
                if ('' != trim($subSentence)) {
                    //判断是会否是注释
                    $isComment = false;
                    foreach ($commenter as $comer) {
                        if (preg_match("/^(" . $comer . ")/is", trim($subSentence))) {
                            $isComment = true;
                            break;
                        }
                    }
                    //如果不是注释，则认为是sql语句
                    if (!$isComment) {
                        $newStatement[] = $subSentence;
                    }
                }
            }
            $data[] = $newStatement;
        }
        //组合sql语句
        foreach ($data as $statement) {
            $newStmt = '';
            foreach ($statement as $sentence) {
                $newStmt = $newStmt . trim($sentence) . "\n";
            }
            if (!empty($newStmt)) {
                $result[] = $newStmt;
            }
        }
        return $result;
    }

    private function query($sql) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt;
    }
}