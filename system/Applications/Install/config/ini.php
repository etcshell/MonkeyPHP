<?php
if (!defined('in_IDream'))exit('Access Denied');
return array(
    'system'=>array(
        'debug'         =>E_ALL ^ E_NOTICE ^ E_WARNING,//或者为0,为0时不启用调试，发布时用。
        'timezone'      =>'PRC',
        'os_charset'    =>'GB2312',//文件系统的字符集，linux用户请改成UTF8
        'gzip_auto'     =>true,
        'gzip_level'    =>6,
        'label'         =>'mk_label',
        'theme'         =>'default',
        'autoload'      =>false,
        'entrance'      =>'install.php',
    ),
    'route'=>array(
        'search_mode'   =>'get',//get（传统方式）
        'search_get'    =>'r',
        'default'       =>'index', //默认模块
        'suffix'        =>'.html',
    ),
    'cache'=> array(
        'type'          =>'mk_cache_file',
        'expire'        =>3600,//默认缓存时间
        'mk_cache_file'=>array(
            'dir'           =>'',
            'filename'      =>'data',
            'filesize'      =>'15M',
            'check'         =>false,
        ),
    ),
    'session'=> array(
        'type'          =>'mk_session_file',
        'expiry'        =>'1440',
        'mk_session_file'=>array(
            'filename'      =>'data',
            'filesize'      =>'15M',
        ),
    ),
);