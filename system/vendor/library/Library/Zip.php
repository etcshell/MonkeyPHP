<?php
namespace Library;

/**
 * Zip
 * Zip算法压缩解压类
 * @package Library
 */
class Zip {

    protected $datasec = array();
    protected $ctrlDir = array();
    protected $eofCtrlDir = "\x50\x4b\x05\x06\x00\x00\x00\x00";
    protected $oldOffset = 0;
    protected $totalFiles = 0;
    protected $totalFolders = 0;

    public function compress($dir, $zipFilename) {
        if (function_exists('gzcompress')) {
            $filelist = $this->getFileList($dir);
            if (count($filelist) > 0) {
                foreach ($filelist as $filename) {
                    if (is_file($filename)) {
                        $fd = fopen($filename, 'r');
                        $content = fread($fd, filesize($filename));
                        fclose($fd);
                        $this->addFile($content, $filename);
                    }
                }
                $out = $this->file();
                $fp = fopen($zipFilename, 'w');
                fwrite($fp, $out, strlen($out));
                fclose($fp);
                return true;
            }
        }
        return false;
    }

    public function decompress($zipFilename, $dir = './') {
        $index = array(-1);
        $stat = array();
        //        $ok = 0;
        $zipFp = @fopen($zipFilename, 'rb');
        if (!$zipFp) {
            return false;
        }
        $cdir = $this->readCentralDir($zipFp, $zipFilename);
        $posEntry = $cdir['offset'];
        if (!is_array($index)) {
            $index = array($index);
        }
        for ($i = 0; $index[$i]; $i++) {
            if (intval($index[$i]) != $index[$i] || $index[$i] > $cdir['entries']) {
                return false;
            }
        }
        for ($i = 0; $i < $cdir['entries']; $i++) {
            @fseek($zipFp, $posEntry);
            $header = $this->readCentralFileHeaders($zipFp);
            $header['index'] = $i;
            $posEntry = ftell($zipFp);
            @rewind($zipFp);
            fseek($zipFp, $header['offset']);
            if (in_array('-1', $index) || in_array($i, $index)) {
                $stat[$header['filename']] = $this->extractFile($header, $dir, $zipFp);
            }
        }
        fclose($zipFp);
        return $stat;
    }

    private function getFileList($dir) {
        $file = array();
        if (file_exists($dir)) {
            if (substr($dir, -1) != "/") {
                $dir .= "/";
            }
            $dh = opendir($dir);
            while ($files = readdir($dh)) {
                if (($files != ".") && ($files != "..")) {
                    if (is_dir($dir . $files)) {
                        $file = array_merge($file, $this->getFileList($dir . $files));
                    }
                    else {
                        $file[] = $dir . $files;
                    }
                }
            }
            closedir($dh);
        }
        $file[] = '';
        return $file;
    }

    private function unixToDostime($unixtime = 0) {
        $timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);
        if ($timearray['year'] < 1980) {
            $timearray['year'] = 1980;
            $timearray['mon'] = 1;
            $timearray['mday'] = 1;
            $timearray['hours'] = 0;
            $timearray['minutes'] = 0;
            $timearray['seconds'] = 0;
        }
        return
            (($timearray['year'] - 1980) << 25) |
            ($timearray['mon'] << 21) |
            ($timearray['mday'] << 16) |
            ($timearray['hours'] << 11) |
            ($timearray['minutes'] << 5) |
            ($timearray['seconds'] >> 1);
    }

    private function addFile($data, $name, $time = 0) {
        $name = str_replace('\\', '/', $name);
        $dtime = dechex($this->unixToDostime($time));
        $hexdtime =
            '\x' . $dtime[6] . $dtime[7] . '\x' . $dtime[4] . $dtime[5] . '\x' . $dtime[2] . $dtime[3] . '\x' .
            $dtime[0] . $dtime[1];
        eval('$hexdtime = "' . $hexdtime . '";');
        $fr = "\x50\x4b\x03\x04";
        $fr .= "\x14\x00"; // ver needed to extract
        $fr .= "\x00\x00"; // gen purpose bit flag
        $fr .= "\x08\x00"; // compression method
        $fr .= $hexdtime; // last mod time and date
        // "local file header" segment
        $uncLen = strlen($data);
        $crc = crc32($data);
        $zdata = gzcompress($data);
        $cLen = strlen($zdata);
        $zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
        $fr .= pack('V', $crc); // crc32
        $fr .= pack('V', $cLen); // compressed filesize
        $fr .= pack('V', $uncLen); // uncompressed filesize
        $fr .= pack('v', strlen($name)); // length of filename
        $fr .= pack('v', 0); // extra field length
        $fr .= $name;
        // "file data" segment
        $fr .= $zdata;
        // "data descriptor" segment (optional but necessary if archive is not
        // served as file)
        $fr .= pack('V', $crc); // crc32
        $fr .= pack('V', $cLen); // compressed filesize
        $fr .= pack('V', $uncLen); // uncompressed filesize
        // add this entry to array
        $this->datasec[] = $fr;
        $newOffset = strlen(implode('', $this->datasec));
        // now add to central directory record
        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .= "\x00\x00"; // version made by
        $cdrec .= "\x14\x00"; // version needed to extract
        $cdrec .= "\x00\x00"; // gen purpose bit flag
        $cdrec .= "\x08\x00"; // compression method
        $cdrec .= $hexdtime; // last mod time & date
        $cdrec .= pack('V', $crc); // crc32
        $cdrec .= pack('V', $cLen); // compressed filesize
        $cdrec .= pack('V', $uncLen); // uncompressed filesize
        $cdrec .= pack('v', strlen($name)); // length of filename
        $cdrec .= pack('v', 0); // extra field length
        $cdrec .= pack('v', 0); // file comment length
        $cdrec .= pack('v', 0); // disk number start
        $cdrec .= pack('v', 0); // internal file attributes
        $cdrec .= pack('V', 32); // external file attributes - 'archive' bit set
        $cdrec .= pack('V', $this->oldOffset); // relative offset of local header
        $this->oldOffset = $newOffset;
        $cdrec .= $name;
        $this->ctrlDir[] = $cdrec;
    }

    private function file() {
        $data = implode('', $this->datasec);
        $ctrldir = implode('', $this->ctrlDir);
        return
            $data .
            $ctrldir .
            $this->eofCtrlDir .
            pack('v', sizeof($this->ctrlDir)) .
            pack('v', sizeof($this->ctrlDir)) .
            pack('V', strlen($ctrldir)) .
            pack('V', strlen($data)) .
            "\x00\x00";
    }

    //下面是zip解压部分
    protected function readFileHeader($zip) {
        $binaryData = fread($zip, 30);
        $header = array();
        $data = unpack(
            'vchk/vid/vversion/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len',
            $binaryData
        );
        $header['filename'] = fread($zip, $data['filename_len']);
        if ($data['extra_len'] != 0) {
            $header['extra'] = fread($zip, $data['extra_len']);
        }
        else {
            $header['extra'] = '';
        }
        $header['compression'] = $data['compression'];
        $header['size'] = $data['size'];
        $header['compressed_size'] = $data['compressed_size'];
        $header['crc'] = $data['crc'];
        $header['flag'] = $data['flag'];
        $header['mdate'] = $data['mdate'];
        $header['mtime'] = $data['mtime'];
        if ($header['mdate'] && $header['mtime']) {
            $hour = ($header['mtime'] & 0xF800) >> 11;
            $minute = ($header['mtime'] & 0x07E0) >> 5;
            $seconde = ($header['mtime'] & 0x001F) * 2;
            $year = (($header['mdate'] & 0xFE00) >> 9) + 1980;
            $month = ($header['mdate'] & 0x01E0) >> 5;
            $day = $header['mdate'] & 0x001F;
            $header['mtime'] = mktime($hour, $minute, $seconde, $month, $day, $year);
        }
        else {
            $header['mtime'] = TIME;
        }
        $header['stored_filename'] = $header['filename'];
        $header['status'] = 'ok';
        return $header;
    }

    protected function readCentralFileHeaders($zip) {
        $binaryData = fread($zip, 46);
        $header = unpack(
            'vchkid/vid/vversion/vversion_extracted/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len/vcomment_len/vdisk/vinternal/Vexternal/Voffset',
            $binaryData
        );
        if ($header['filename_len'] != 0) {
            $header['filename'] = fread($zip, $header['filename_len']);
        }
        else {
            $header['filename'] = '';
        }
        if ($header['extra_len'] != 0) {
            $header['extra'] = fread($zip, $header['extra_len']);
        }
        else {
            $header['extra'] = '';
        }
        if ($header['comment_len'] != 0) {
            $header['comment'] = fread($zip, $header['comment_len']);
        }
        else {
            $header['comment'] = '';
        }
        if ($header['mdate'] && $header['mtime']) {
            $hour = ($header['mtime'] & 0xF800) >> 11;
            $minute = ($header['mtime'] & 0x07E0) >> 5;
            $seconde = ($header['mtime'] & 0x001F) * 2;
            $year = (($header['mdate'] & 0xFE00) >> 9) + 1980;
            $month = ($header['mdate'] & 0x01E0) >> 5;
            $day = $header['mdate'] & 0x001F;
            $header['mtime'] = mktime($hour, $minute, $seconde, $month, $day, $year);
        }
        else {
            $header['mtime'] = TIME;
        }
        $header['stored_filename'] = $header['filename'];
        $header['status'] = 'ok';
        if (substr($header['filename'], -1) == '/') {
            $header['external'] = 0x41FF0010;
        }
        return $header;
    }

    protected function readCentralDir($zip, $zipName) {
        $centd = array();
        $size = filesize($zipName);
        if ($size < 277) {
            $maximumSize = $size;
        }
        else {
            $maximumSize = 277;
        }
        @fseek($zip, $size - $maximumSize);
        $pos = ftell($zip);
        $bytes = 0x00000000;
        while ($pos < $size) {
            $byte = @fread($zip, 1);
            $bytes = ($bytes << 8) | ord($byte);
            if ($bytes == 0x504b0506 or $bytes == 0x2e706870504b0506) {
                $pos++;
                break;
            }
            $pos++;
        }
        $fdata = fread($zip, 18);
        $data = @unpack('vdisk/vdisk_start/vdisk_entries/ventries/Vsize/Voffset/vcomment_size', $fdata);
        if ($data['comment_size'] != 0) {
            $centd['comment'] = fread($zip, $data['comment_size']);
        }
        else {
            $centd['comment'] = '';
        }
        $centd['entries'] = $data['entries'];
        $centd['disk_entries'] = $data['disk_entries'];
        $centd['offset'] = $data['offset'];
        $centd['disk_start'] = $data['disk_start'];
        $centd['size'] = $data['size'];
        $centd['disk'] = $data['disk'];
        return $centd;
    }

    protected function extractFile($header, $to, $zip) {
        $header = $this->readFileHeader($zip);
        if (substr($to, -1) != '/') {
            $to .= '/';
        }
        if ($to == './') {
            $to = '';
        }
        $pth = explode('/', $to . $header['filename']);
        $mydir = '';
        for ($i = 0; $i < count($pth) - 1; $i++) {
            if (!$pth[$i]) {
                continue;
            }
            $mydir .= $pth[$i] . '/';
            if ((!is_dir($mydir) && @mkdir($mydir, 0777)) ||
                (($mydir == $to . $header['filename'] || ($mydir == $to && $this->totalFolders == 0)) &&
                    is_dir($mydir))
            ) {
                @chmod($mydir, 0777);
                $this->totalFolders++;
            }
        }
        if (strrchr($header['filename'], '/') == '/') {
            return true;
        }
        if (!($header['external'] == 0x41FF0010) && !($header['external'] == 16)) {
            if ($header['compression'] == 0) {
                $fp = @fopen($to . $header['filename'], 'wb');
                if (!$fp) {
                    return (-1);
                }
                $size = $header['compressed_size'];
                while ($size != 0) {
                    $readSize = ($size < 2048 ? $size : 2048);
                    $buffer = fread($zip, $readSize);
                    $binaryData = pack('a' . $readSize, $buffer);
                    @fwrite($fp, $binaryData, $readSize);
                    $size -= $readSize;
                }
                fclose($fp);
                touch($to . $header['filename'], $header['mtime']);
            }
            else {
                $fp = @fopen($to . $header['filename'] . '.gz', 'wb');
                if (!$fp) {
                    return (-1);
                }
                $binaryData = pack(
                    'va1a1Va1a1',
                    0x8b1f,
                    Chr($header['compression']),
                    Chr(0x00),
                    TIME,
                    Chr(0x00),
                    Chr(3)
                );
                fwrite($fp, $binaryData, 10);
                $size = $header['compressed_size'];
                while ($size != 0) {
                    $readSize = ($size < 1024 ? $size : 1024);
                    $buffer = fread($zip, $readSize);
                    $binaryData = pack('a' . $readSize, $buffer);
                    @fwrite($fp, $binaryData, $readSize);
                    $size -= $readSize;
                }
                $binaryData = pack('VV', $header['crc'], $header['size']);
                fwrite($fp, $binaryData, 8);
                fclose($fp);
                $gzp = @gzopen($to . $header['filename'] . '.gz', 'rb') or die("Cette archive est compress!");
                if (!$gzp) {
                    return (-2);
                }
                $fp = @fopen($to . $header['filename'], 'wb');
                if (!$fp) {
                    return (-1);
                }
                $size = $header['size'];
                while ($size != 0) {
                    $readSize = ($size < 2048 ? $size : 2048);
                    $buffer = gzread($gzp, $readSize);
                    $binaryData = pack('a' . $readSize, $buffer);
                    @fwrite($fp, $binaryData, $readSize);
                    $size -= $readSize;
                }
                fclose($fp);
                gzclose($gzp);
                touch($to . $header['filename'], $header['mtime']);
                @unlink($to . $header['filename'] . '.gz');
            }
        }
        $this->totalFiles++;
        return true;
    }
}
