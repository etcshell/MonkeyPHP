<?php

config_add(
    array(
        'db_log_enable'     =>true,//在必要时开启。
        'db_connection_pool'=>array(
            'cms',
            'slave',
            'mysqlite'
        ),

        'db_cms'=>array(
            'protocol'      =>'mysql', //数据库协议
            'host'          =>'localhost', //主机名
            'port'          =>'3306', //服务端口号
            'dbname'        =>'test', //数据库名
            'charset'       =>'utf8',//字符集设置
            'username'      =>'root', //数据库用户名
            'password'      =>'123', //数据库用户密码
            'prefix'        =>'mk_',//表前缀
        ),

        'db_slave'=>array(
            'protocol'      =>'mssql', //数据库协议
            'host'          =>'localhost', //主机名
            'port'          =>'3306', //服务端口号
            'dbname'        =>'test', //数据库名
            'charset'       =>'utf8',//字符集设置
            'username'      =>'root', //数据库用户名
            'password'      =>'123', //数据库用户密码
            'prefix'        =>'test_',//表前缀
        ),

        'db_mysqlite'=>array(
            'protocol'      =>'sqlite', //数据库协议
            'file'          =>'/apps/index/data/sqlite.db', //数据文件（绝对路径），也可以是“:memory:”（内存表）
            'charset'       =>'utf8',//字符集设置，Sqlite只支持utf8，设不设置都UTF8
            'username'      =>'root', //数据库用户名
            'password'      =>'123', //数据库用户密码
            'prefix'        =>'test_',//表前缀
        )
    )
);