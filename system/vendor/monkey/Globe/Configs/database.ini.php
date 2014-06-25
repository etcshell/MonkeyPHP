<?php
/**
 * 数据库组件提供者
 */
\Monkey\App\Config::setComponentProvider('database',
    array(
        'default_provider'=>'default',
        'default'=>'\Monkey\Database\Database',
    )
);
/**
 * MonkeyPHP提供的默认值数据库组件的配置
 */
\Monkey\App\Config::setComponentConfig('database','default',
    array(
        'default_connection'   =>'master',
        //链接池子
        'pool'=>array(
            'master'=>1,//本数组采用了键值倒置设计，目的是方便操作和提高检索速度
            'slave'=>1,//后面的值 1 是随便设置的值，只要是TRUE类型都可以
            'sqlite'=>1
        ),
        //每个连接的键名可以为数字（省略或不省略都可以），比如 下面这个表示 links[0]
        'master'=>array(
            'protocol'      =>'mysql', //数据库协议
            //dsn配置是可选的、优先的
            'dsn'           =>'mysql:host=localhost;port=3306;dbname=test',//可以代替下面四项
            'host'          =>'localhost', //主机名
            'port'          =>'3306', //服务端口号
            //数据库名
            //（即使使用dsn，这个都不能省略，并且必须和dsn中的设置一致）
            'dbname'        =>'test',
            'unix_socket'   =>'',
            'charset'       =>'utf8',//字符集设置
            'collation'     =>'',//这个要charset首先设置才有用
            'username'      =>'root', //数据库用户名
            'password'      =>'root', //数据库用户密码
            'prefix'        =>'test_',//表前缀

            'transactions'  =>true,//数据库（引擎）是否支持事务

            'options'       => array(),//PDO连接的操作选项
        ),

        //也可以为每个连接起一个关联字符名字，比如 下面这个表示 links['slave']
        'slave'=>array(
            'protocol'      =>'mssql', //数据库协议
            //dsn配置是可选的、优先的，主要用于应对Mssql连接的多种情况（各有 1 例）：
            //其协议部分可能是mssql | dblib | sqlsrv | odbc（需要:Driver={SQL Server};）
            'dsn'           =>'mssql:host=localhost;dbname=test;',//默认生成这样的连接方式。
            //'dsn'           =>'dblib:host=localhost;dbname=test;',
            //'dsn'           =>'sqlsrv:Server=localhost;Database=test;',
            //'dsn'           =>'odbc:Driver={SQL Server};Server=localhost;Database=test;',
            //因为多样性，所以建议使用DSN来代替下面三项
            'host'          =>'localhost', //主机名
            'port'          =>'3306', //服务端口号
            //数据库名
            //（即使使用dsn，这个都不能省略，并且必须和dsn中的设置一致）
            'dbname'        =>'test',
            'charset'       =>'utf8',//字符集设置
            'username'      =>'root', //数据库用户名
            'password'      =>'123', //数据库用户密码
            'prefix'        =>'test_',//表前缀
            'transactions'  =>true,//数据库（引擎）是否支持事务
        ),

        //sqlite连接比较特殊，仅支持PDO（能连接sqlite2和sqlite3），没有了主机名等，却增加了file键名
        'sqlite'=>array(
            'protocol'      =>'sqlite', //数据库协议
            //dsn配置是可选的、优先的。以下给出两例
            'dsn'           =>'',//'sqlite:'. '/Configs/sqlite.db',
            //'dsn'           =>'sqlite::memory:',
            'file'          =>'',//'/Configs/sqlite.db', //数据文件（绝对路径），也可以是“:memory:”（内存表）
            'charset'       =>'utf8',//字符集设置，Sqlite只支持utf8，设不设置都UTF8
            'username'      =>'root', //数据库用户名
            'password'      =>'123', //数据库用户密码
            'prefix'        =>'test_',//表前缀
            'transactions'  =>true,//数据库（引擎）是否支持事务
        ),
    )
);