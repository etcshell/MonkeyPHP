<?php
/**
 * Project MonkeyPHP
 *
 * PHP Version 5.3.9
 *
 * @package   Monkey\Cache
 * @author    找不到了 <也未知@qq.com>
 * @version   GIT:<git_id>
 */
namespace Monkey\Cache;

use Monkey;

/**
 * Class File
 *
 * cache的File实现
 *
 * @package Monkey\Cache
 */
final class File implements CacheInterface {
    private $idxNodeSize = 40;
    private $idxNodeBase;
    private $dataBasePos = 262588; //40+20+24*16+16*16*16*16*4;
    private $schemaItemSize = 24;
    private $headerPadding = 20; //保留空间 放置php标记防止下载
    private $infoSize = 20; //保留空间 4+16 maxsize|ver
    //40起 添加20字节保留区域
    private $idxSeqPos = 40; //id 计数器节点地址
    private $dFileCurPos = 44; //id 计数器节点地址
    private $idxFreePos = 48; //id 空闲链表入口地址
    private $idxBasePos = 444; //40+20+24*16
    private $schemaStruct = array('size', 'free', 'lru_head', 'lru_tail', 'hits', 'miss');
    private $ver = '$Rev: 3 $';
    private $cacheSize = '15M'; //预设的缓存大小
    private $existsFileLock = false; //是否存在文件锁，设置为false，将模拟文件锁
    private $cachePath = ''; //存储缓存文件的目录
    private $cacheFile = ''; //缓存的数据文件名
    private $dataOnCheck = false; //是否验证数据
    private $maxSize;
    private $rs;
    private $bsizeList;
    private $blockSizeList;
    private $nodeStruct;
    private $expire = 3600;
    private $TIME;

    /**
     * @param Monkey\App $app
     */
    public function __construct($app) {
        $config = $app->config()->getComponentConfig('cache', 'file');
        $this->TIME = $app->TIME;
        $this->expire = $config['expire'];

        $this->cachePath = dir_format($app->TEMP . ($config['dir'] ? $config['dir'] : '/fileCache'));
        $this->cacheFile = $config['filename'] ? '/' . $config['filename'] : '/data';
        $this->cacheSize = $config['filesize'] ? $config['filesize'] : '15M';
        $this->dataOnCheck = $config['check'] ? $config['check'] : false; //是否验证数据
        if (!dir_check($this->cachePath)) {
            $this->error('缓存目录校验失败。');
        }
        $this->workat($this->cachePath . $this->cacheFile);
    }

    /**
     * 读取缓存
     * @param string $key 要读取的缓存项目名称
     * @param mixed &$result 要保存的结果地址
     * @return bool             成功返回true，失败返回false
     */
    public function fetch($key, &$result) {
        $result = null;
        if (!$this->_fetch(md5($key), $content))
            return false;
        $time = (int)substr($content, 0, 12);
        if ($time != -1 && $this->TIME >= $time)
            return false;
        if ($this->dataOnCheck) {
            //开启数据校验
            $check = substr($content, 12, 32);
            $content = substr($content, 44);
            if ($check != md5($content))
                return false; //校验错误
        }
        else {
            $content = substr($content, 12);
        }
        $result = unserialize($content); //解序列化数据
        return true;
    }

    /**
     * 设置缓存
     * @param string $key 要设置的缓存项目名称
     * @param mixed $value 要设置的缓存项目内容
     * @param int $time 要设置的缓存项目的过期时长，默认保存时间为 -1，永久保存为 0
     * @return bool 保存是成功为true ，失败为false
     */
    public function store($key, $value, $time = -1) {
        //数据为空，缓存时间为0,则不缓存
        if (empty($key) || empty($value))
            return false;
        $data = serialize($value); //将数据序列化
        if ($time == -1)
            $time = $this->expire;
        if ($time != 0)
            $time = $this->TIME + $time; //过期时间
        //是否开启数据校验
        $check = $this->dataOnCheck ? md5($data) : '';
        $data = sprintf('%012d', $time) . $check . $data;
        return $this->_store(md5($key), $data); //存储数据
    }

    /**
     * 清空缓存
     * @return bool
     */
    public function clear() {
        $this->format(true);
        fclose($this->rs);
        return unlink($this->cacheFile);
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
    private function allSchemas() {
        $schema = array();
        for ($i = 0; $i < 16; $i++) {
            $this->seek(60 + $i * $this->schemaItemSize);
            $info = unpack('V1' . implode('/V1', $this->schemaStruct), fread($this->rs, $this->schemaItemSize));
            if (!$info['size'])
                return $schema;
            $info['id'] = $i;
            $schema[$i] = $info;
        }
        return $schema;
    }

    private function allocIdx($data) {
        $this->seek($this->idxFreePos);
        list(, $listPos) = unpack('V', fread($this->rs, 4));
        if ($listPos) {
            $this->seek($listPos * $this->idxNodeSize + $this->idxNodeBase);
            list(, $prevFreeNode) = unpack('V', fread($this->rs, 4));
            $this->puts($this->idxFreePos, pack('V', $prevFreeNode));
        }
        else {
            $this->seek($this->idxSeqPos);
            list(, $listPos) = unpack('V', fread($this->rs, 4));
            $this->puts($this->idxSeqPos, pack('V', $listPos + 1));
        }
        return $this->createNode($listPos, $data);
    }

    /**
     * 建立缓存文件
     * @return bool
     */
    private function create() {
        $this->rs = @fopen($this->cacheFile, 'wb+');
        fseek($this->rs, 0);
        fputs($this->rs, '<' . '?php exit()?' . '>');
        return $this->format();
    }

    private function createNode($pos, $data) {
        $this->puts($pos * $this->idxNodeSize + $this->idxNodeBase, pack('V1V1V1V1V1V1H*', $data['next'], $data['prev'], $data['data'], $data['size'], $data['lru_right'], $data['lru_left'], $data['key']));
        return $pos;
    }

    private function dalloc($schemaId, $lruFreed = false) {
        $free = $this->getSchema($schemaId, 'free');
        if ($free) { //如果lru里有链表
            $this->seek($free);
            list(, $next) = unpack('V', fread($this->rs, 4));
            $this->setSchema($schemaId, 'free', $next);
            return $free;
        }
        elseif ($lruFreed) {
            $this->error(__METHOD__ . ':弹出lru区（最少使用区），空间被释放了');
        }
        else {
            $dsOffset = $this->getDcurPos();
            $size = $this->getSchema($schemaId, 'size');
            if ($size + $dsOffset > $this->maxSize) {
                $info = $this->lruPop($schemaId);
                if ($info)
                    return $this->dalloc($schemaId, $info);
                $this->error(__METHOD__ . ':不能分配存储空间');
            }
            else {
                $this->setDcurPos($dsOffset + $size);
                return $dsOffset;
            }
        }
        return $free;
    }

    /**
     * 删除缓存文件中指定的内容
     * @param string $md5Key 要删除的项目的md5Key
     * @param int|bool $pos 要删除的项目的定位指针偏移量
     * @return bool
     */
    private function _delete($md5Key, $pos = false) {
        if (!$pos && !$this->search($md5Key, $pos))
            return false;
        $info = $this->getNode($pos);
        if (!$info)
            return false;
        //删除data区域
        if ($info['prev']) {
            $this->setNode($info['prev'], 'next', $info['next']);
            $this->setNode($info['next'], 'prev', $info['prev']);
        }
        else { //改入口位置
            $this->setNode($info['next'], 'prev', 0);
            $this->setNodeRoot($md5Key, $info['next']);
        }
        $this->freeDspace($info['size'], $info['data']);
        $this->lruDelete($info);
        $this->freeNode($pos);
        return $info['prev'];
    }

    private function dfollow($pos, &$c) {
        $c++;
        $this->seek($pos);
        list(, $next) = unpack('V1', fread($this->rs, 4));
        if ($next)
            return $this->dfollow($next, $c);
        return $pos;
    }

    /**
     * 提取缓存内容
     * @param string $md5Key 提取缓存项目的键值，为md5数字串
     * @param mixed $return 存放提取缓存项目内容的指针，变量前加&，成功后结果为序列化数据，要反序列化后才能用
     * @return bool
     */
    private function _fetch($md5Key, &$return) {
        $locked = $this->lockCacheFile(false) ? true : false;
        if (!$this->search($md5Key, $offset)) {
            $locked && $this->unlockCacheFile();
            return false;
        }
        $info = $this->getNode($offset);
        $schemaId = $this->getSizeSchemaId($info['size']);
        if ($schemaId === false) {
            if ($locked)
                $this->unlockCacheFile();
            return false;
        }
        $this->seek($info['data']);
        $return = fread($this->rs, $info['size']);
        if ($return === false) {
            if ($locked)
                $this->unlockCacheFile();
            return false;
        }
        if (!$locked)
            return true;
        $this->lruPush($schemaId, $info['offset']);
        $this->setSchema($schemaId, 'hits', $this->getSchema($schemaId, 'hits') + 1);
        return $this->unlockCacheFile();
    }

    /**
     * 格式化缓存文件
     * @param bool $truncate
     * @return bool
     */
    private function format($truncate = false) {
        !$this->lockCacheFile(true, true) && $this->error(__METHOD__ . ':不能锁定缓存文件');
        if ($truncate) {
            $this->seek(0);
            ftruncate($this->rs, $this->idxNodeBase);
        }
        $this->maxSize = $this->parseStrSize($this->cacheSize, 15728640); //default:15m
        $this->puts($this->headerPadding, pack('V1a*', $this->maxSize, $this->ver));
        ksort($this->bsizeList);
        $dsOffset = $this->dataBasePos;
        $i = 0;
        foreach ($this->bsizeList as $size => $count) {
            //将预分配的空间注册到free链表里
            $count *= min(3, floor($this->maxSize / 10485760));
            $nextFreeNode = 0;
            for ($j = 0; $j < $count; $j++) {
                $this->puts($dsOffset, pack('V', $nextFreeNode));
                $nextFreeNode = $dsOffset;
                $dsOffset += intval($size);
            }
            $code = pack(str_repeat('V1', count($this->schemaStruct)), $size, $nextFreeNode, 0, 0, 0, 0);
            $this->puts(60 + $i * $this->schemaItemSize, $code);
            $i++;
        }
        $this->setDcurPos($dsOffset);
        $this->puts($this->idxBasePos, str_repeat("\0", 262144));
        $this->puts($this->idxSeqPos, pack('V', 1));
        $this->unlockCacheFile();
        return true;
    }

    private function freeDspace($size, $pos) {
        ($pos > $this->maxSize) && $this->error(__METHOD__ . ':释放缓存空间时溢出[' . $pos . ']');
        $schemaId = $this->getSizeSchemaId($size);
        $free = $this->getSchema($schemaId, 'free');
        if ($free) {
            $this->puts($free, pack('V1', $pos));
        }
        else {
            $this->setSchema($schemaId, 'free', $pos);
        }
        $this->puts($pos, pack('V1', 0));
    }

    private function freeNode($pos) {
        $this->seek($this->idxFreePos);
        list(, $prevFreeNode) = unpack('V', fread($this->rs, 4));
        $this->puts($pos * $this->idxNodeSize + $this->idxNodeBase, pack('V', $prevFreeNode) . str_repeat("\0", $this->idxNodeSize - 4));
        return $this->puts($this->idxFreePos, pack('V', $pos));
    }

    private function getDcurPos() {
        $this->seek($this->dFileCurPos);
        list(, $dsOffset) = unpack('V', fread($this->rs, 4));
        return $dsOffset;
    }

    /**
     * 取得当前偏移量的存储内容
     * @param integer $offset 当前偏移量
     * @return array
     */
    private function getNode($offset) {
        $this->seek($offset * $this->idxNodeSize + $this->idxNodeBase);
        $info = unpack('V1next/V1prev/V1data/V1size/V1lru_right/V1lru_left/H*key', fread($this->rs, $this->idxNodeSize));
        $info['offset'] = $offset;
        return $info;
    }

    /**
     * 取得键的索引值
     * @param string $md5Key
     * @return mixed
     */
    private function getNodeRoot($md5Key) {
        $this->seek(hexdec(substr($md5Key, 0, 4)) * 4 + $this->idxBasePos);
        $a = fread($this->rs, 4);
        list(, $offset) = unpack('V', $a);
        return $offset;
    }

    /**
     * 取得内容的索引值
     * @param integer $offset 键的索引值
     * @param string $md5Key
     * @param integer $pos 存储内容索引值的指针
     * @return bool
     */
    private function getPosByKey($offset, $md5Key, &$pos) {
        if (!$offset) {
            $pos = 0;
            return false;
        }
        for (; 1;) {
            $info = $this->getNode($offset);
            if ($info['key'] == $md5Key) {
                $pos = $info['offset'];
                return true;
            }
            elseif ($info['next'] && $info['next'] != $offset) {
                $offset = $info['next'];
            }
            else {
                $pos = $offset;
                return false;
            }
        }
        return true;
    }

    private function getSchema($id, $md5Key) {
        $info = array_flip($this->schemaStruct);
        $this->seek(60 + $id * $this->schemaItemSize);
        unpack('V1' . implode('/V1', $this->schemaStruct), fread($this->rs, $this->schemaItemSize));
        $this->seek(60 + $id * $this->schemaItemSize + $info[$md5Key] * 4);
        list(, $value) = unpack('V', fread($this->rs, 4));
        return $value;
    }

    /**
     * 获取存储大小对应的索引
     * @param integer $size
     * @return mixed
     */
    private function getSizeSchemaId($size) {
        foreach ($this->blockSizeList as $k => $blockSize) {
            if ($size <= $blockSize)
                return $k;
        }
        return false;
    }

    /**
     * 锁定缓存文件
     * @param bool $isBlock 如果文件已经锁定是否等待解锁并重新锁定
     * @param bool $whatever 是否坚持等待直到解锁，然后锁定
     * @return bool
     */
    private function lockCacheFile($isBlock, $whatever = false) {
        if ($this->existsFileLock)
            return flock($this->rs, $isBlock ? LOCK_EX : LOCK_EX + LOCK_NB);
        ignore_user_abort(true);
        $supportUsleep = version_compare(PHP_VERSION, 5, '>=') ? 20 : 1;
        $lockfile = $this->cacheFile . '.lck';
        if (file_exists($lockfile)) {
            if ($this->TIME - filemtime($lockfile) > 0) {
                unlink($lockfile);
            }
            elseif (!$isBlock) {
                return false;
            }
        }
        $lockEx = @fopen($lockfile, 'x');
        for ($i = 0; ($lockEx === false) && ($whatever || $i < 10); $i++) {
            clearstatcache();
            if ($supportUsleep == 1) {
                usleep(rand(9, 999));
            }
            else {
                sleep(1);
            }
            $lockEx = @fopen($lockfile, 'x');
        }
        return ($lockEx !== false);
    }

    private function lruDelete($info) {
        if ($info['lru_right']) {
            $this->setNode($info['lru_right'], 'lru_left', $info['lru_left']);
        }
        else {
            $this->setSchema($this->getSizeSchemaId($info['size']), 'lru_tail', $info['lru_left']);
        }
        if ($info['lru_left']) {
            $this->setNode($info['lru_left'], 'lru_right', $info['lru_right']);
        }
        else {
            $this->setSchema($this->getSizeSchemaId($info['size']), 'lru_head', $info['lru_right']);
        }
        return true;
    }

    private function lruPop($schemaId) {
        $node = $this->getSchema($schemaId, 'lru_tail');
        if (!$node)
            return false;
        $info = $this->getNode($node);
        if (!$info['data'])
            return false;
        $this->_delete($info['key'], $info['offset']);
        if (!$this->getSchema($schemaId, 'free')) {
            $this->error(__METHOD__ . ':弹出lru区（最少使用区），但是空间没有被释放');
        }
        return $info;
    }

    private function lruPush($schemaId, $offset) {
        $lruHead = $this->getSchema($schemaId, 'lru_head');
        $lruTail = $this->getSchema($schemaId, 'lru_tail');
        if ((!$offset) || ($lruHead == $offset))
            return true;
        $info = $this->getNode($offset);
        $this->setNode($info['lru_right'], 'lru_left', $info['lru_left']);
        $this->setNode($info['lru_left'], 'lru_right', $info['lru_right']);
        $this->setNode($offset, 'lru_right', $lruHead);
        $this->setNode($offset, 'lru_left', 0);
        $this->setNode($lruHead, 'lru_left', $offset);
        $this->setSchema($schemaId, 'lru_head', $offset);
        if ($lruTail == 0) {
            $this->setSchema($schemaId, 'lru_tail', $offset);
        }
        elseif ($lruTail == $offset && $info['lru_left']) {
            $this->setSchema($schemaId, 'lru_tail', $info['lru_left']);
        }
        return true;
    }

    /**
     * 设置缓存大小
     * @param string $strSize 客户设置大小
     * @param integer $default 默认大小
     * @return integer
     */
    private function parseStrSize($strSize, $default) {
        if (!preg_match('/^([0-9]+)\s*([gmk]|)$/i', $strSize, $match)) {
            return $default;
        }
        switch (strtolower($match[2])) {
            case 'g':
                if ($match[1] > 1)
                    $this->error(__METHOD__ . ':设置缓存大小时越界，最大只能支持【1G】');
                $size = $match[1] << 30;
                break;
            case 'm':
                $size = $match[1] << 20;
                break;
            case 'k':
                $size = $match[1] << 10;
                break;
            default:
                $size = $match[1];
        }
        if ($size <= 0)
            $this->error(__METHOD__ . ':设置缓存大小时越界，缓存文件大小为0则无意义！');
        if ($size < 10485760)
            return 10485760;
        return $size;
    }

    /**
     * 向缓存文件中写入数据
     * @param int $offset 偏移量（写入位置）
     * @param string $data 要写入的数据（序列化后的字符串）
     * @return bool
     */
    private function puts($offset, $data) {
        if ($offset >= $this->maxSize * 1.5) {
            $this->error(__METHOD__ . ':向缓存文件中写入数据时，缓存数据的偏移量$offset【' . $offset . '】超出配额！');
        }
        $this->seek($offset);
        return fwrite($this->rs, $data);
    }

    protected function schemaStatus() {
        $return = array();
        foreach ($this->allSchemas() as $schemaItem) {
            if ($schemaItem['free']) {
                $this->dfollow($schemaItem['free'], $schemaItem['freecount']);
            }
            $return[] = $schemaItem;
        }
        return $return;
    }

    /**
     * 查找项目
     * @param string $md5Key 要查找的项目的key （key为 md5 字符串）
     * @param int $pos 要查找的项目的定位指针偏移量 如果找到节点则$pos=节点本身 返回true 否则 $pos=树的末端 返回false
     * @return mixed
     */
    private function search($md5Key, &$pos) {
        return $this->getPosByKey($this->getNodeRoot($md5Key), $md5Key, $pos);
    }

    /**
     * 在缓存文件中定位
     * @param int $offset 在缓存文件中定位的指针偏移量
     * @return int 定位成功为0 ，失败为-1
     */
    private function seek($offset) {
        return fseek($this->rs, $offset);
    }

    /**
     * 设置存储标签
     * @param integer $pos
     * @return bool
     */
    private function setDcurPos($pos) {
        return $this->puts($this->dFileCurPos, pack('V', $pos));
    }

    private function setNode($pos, $md5Key, $value) {
        if (!$pos) {
            return false;
        }
        if (isset($this->nodeStruct[$md5Key])) {
            return $this->puts($pos * $this->idxNodeSize + $this->idxNodeBase + $this->nodeStruct[$md5Key][0], pack($this->nodeStruct[$md5Key][1], $value));
        }
        else {
            return false;
        }
    }

    private function setNodeRoot($md5Key, $value) {
        return $this->puts(hexdec(substr($md5Key, 0, 4)) * 4 + $this->idxBasePos, pack('V', $value));
    }

    private function setSchema($schemaId, $md5Key, $value) {
        $info = array_flip($this->schemaStruct);
        return $this->puts(60 + $schemaId * $this->schemaItemSize + $info[$md5Key] * 4, pack('V', $value));
    }

    protected function status(&$curBytes, &$totalBytes) {
        $totalBytes = $curBytes = 0;
        $hits = $miss = 0;
        $schemaStatus = $this->schemaStatus();
        $totalBytes = $this->maxSize;
        $freeBytes = $this->maxSize - $this->getDcurPos();
        foreach ($schemaStatus as $schema) {
            $freeBytes += $schema['freecount'] * $schema['size'];
            $miss += $schema['miss'];
            $hits += $schema['hits'];
        }
        $curBytes = $totalBytes - $freeBytes;
        $return[] = array('name' => '缓存命中', 'value' => $hits);
        $return[] = array('name' => '缓存未命中', 'value' => $miss);
        return $return;
    }

    /**
     * 存储缓存内容
     * @param string $md5Key 存储缓存项目的键值，为md5数字串
     * @param mixed $data 存储缓存项目的内容，为已序列化的数据
     * @return bool
     */
    private function _store($md5Key, $data) {
        if (!$this->lockCacheFile(true))
            $this->error(__METHOD__ . ':不能锁定文件!');
        $size = strlen($data);
        //get list_idx
        $hasKey = $this->search($md5Key, $listIdxOffset);
        $schemaId = $this->getSizeSchemaId($size);
        if ($schemaId === false) {
            $this->unlockCacheFile();
            return false;
        }
        if ($hasKey) {
            $hdseq = $listIdxOffset;
            $info = $this->getNode($hdseq);
            if ($schemaId == $this->getSizeSchemaId($info['size'])) {
                $dataoffset = $info['data'];
            }
            else {
                //破掉原有lru
                $this->lruDelete($info);
                if (!($dataoffset = $this->dalloc($schemaId))) {
                    $this->unlockCacheFile();
                    return false;
                }
                $this->freeDspace($info['size'], $info['data']);
                $this->setNode($hdseq, 'lru_left', 0);
                $this->setNode($hdseq, 'lru_right', 0);
            }
            $this->setNode($hdseq, 'size', $size);
            $this->setNode($hdseq, 'data', $dataoffset);
        }
        else {
            if (!($dataoffset = $this->dalloc($schemaId))) {
                $this->unlockCacheFile();
                return false;
            }
            $hdseq = $this->allocIdx(array('next' => 0, 'prev' => $listIdxOffset, 'data' => $dataoffset, 'size' => $size, 'lru_right' => 0, 'lru_left' => 0, 'key' => $md5Key,));
            if ($listIdxOffset > 0) {
                $this->setNode($listIdxOffset, 'next', $hdseq);
            }
            else {
                $this->setNodeRoot($md5Key, $hdseq);
            }
        }
        if ($dataoffset > $this->maxSize)
            $this->error(__METHOD__ . ':分配缓存空间时出错！');
        $this->puts($dataoffset, $data);
        $this->setSchema($schemaId, 'miss', $this->getSchema($schemaId, 'miss') + 1);
        $this->lruPush($schemaId, $hdseq);
        $this->unlockCacheFile();
        return true;
    }

    /**
     * 解除文件锁定
     * @return bool
     */
    private function unlockCacheFile() {
        if ($this->existsFileLock)
            return flock($this->rs, LOCK_UN);
        ignore_user_abort(false);
        return @unlink($this->cacheFile . '.lck');
    }

    /**
     * 设定缓存文件
     * @param string $file 缓存文件名（含路径）
     * @return bool
     */
    private function workat($file) {
        $this->cacheFile = $file . '.php';
        $this->bsizeList = array(512 => 10, 3 << 10 => 10, 8 << 10 => 10, 20 << 10 => 4, 30 << 10 => 2, 50 << 10 => 2, 80 << 10 => 2, 96 << 10 => 2, 128 << 10 => 2, 224 << 10 => 2, 256 << 10 => 2, 512 << 10 => 1, 1024 << 10 => 1,);
        $this->nodeStruct = array('next' => array(0, 'V'), 'prev' => array(4, 'V'), 'data' => array(8, 'V'), 'size' => array(12, 'V'), 'lru_right' => array(16, 'V'), 'lru_left' => array(20, 'V'), 'key' => array(24, 'H*'),);
        if (!file_exists($this->cacheFile)) {
            $this->create();
        }
        else {
            $this->rs = fopen($this->cacheFile, 'rb+') or $this->error(__METHOD__ . ':不能打开缓存文件[ ' . realpath($this->cacheFile) . ' ]');
            $this->seek($this->headerPadding);
            $info = unpack('V1max_size/a*ver', fread($this->rs, $this->infoSize));
            if ($info['ver'] != $this->ver) {
                $this->format(true);
            }
            else {
                $this->maxSize = $info['max_size'];
            }
        }
        $this->idxNodeBase = $this->dataBasePos + $this->maxSize;
        $this->blockSizeList = array_keys($this->bsizeList);
        sort($this->blockSizeList);
        return true;
    }

    private function error($message) {
        throw new \Exception('文件缓存错误：' . $message);
    }
}