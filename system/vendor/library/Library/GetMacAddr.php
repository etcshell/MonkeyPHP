<?php
namespace Library;

/**
 * GetMacAddr
 * 网卡MAC地址原码获取类
 * 目前支持WIN/LINUX系统
 * @package Library
 */
class GetMacAddr {

    var $returnArray = array(); // 返回带有MAC地址的字串数组
    var $macAddr;

    function GetMacAddr($osType) {
        switch (strtolower($osType)) {
            case "linux":
                $this->forLinux();
                break;
            case "solaris":
                break;
            case "unix":
                break;
            case "aix":
                break;
            default:
                $this->forWindows();
                break;

        }


        $tempArray = array();
        foreach ($this->returnArray as $value) {

            if (preg_match(
                "/[0-9a-f][0-9a-f][:-]" .
                "[0-9a-f][0-9a-f][:-]" .
                "[0-9a-f][0-9a-f][:-]" .
                "[0-9a-f][0-9a-f][:-]" .
                "[0-9a-f][0-9a-f][:-]" .
                "[0-9a-f][0-9a-f]/i",
                $value,
                $tempArray
            )
            ) {
                $this->macAddr = $tempArray[0];
                break;
            }

        }
        unset($tempArray);
        return $this->macAddr;
    }


    function forWindows() {
        @exec("ipconfig /all", $this->returnArray);
        if ($this->returnArray) {
            return $this->returnArray;
        }
        else {
            $ipconfig = $_SERVER["WINDIR"] . "\system32\ipconfig.exe";
            if (is_file($ipconfig)) {
                @exec($ipconfig . " /all", $this->returnArray);
            }
            else {
                @exec($_SERVER["WINDIR"] . "\system\ipconfig.exe /all", $this->returnArray);
            }
            return $this->returnArray;
        }
    }


    function forLinux() {
        @exec("ifconfig -a", $this->returnArray);
        return $this->returnArray;
    }

}
//方法使用
//$mac = new GetMacAddr(PHP_OS);
//echo $mac->macAddr;
