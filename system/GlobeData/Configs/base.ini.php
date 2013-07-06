<?php

$dir_front=dir_format(dirname(FRONT_FILE));
config_add(
    array(
        'sys_timezone'      =>'PRC',


        'dir_temp'      =>APP_PATH.'/temp',
        'dir_frame'     =>SYSTEM.'/Framework',
        'dir_module'    =>SYSTEM.'/Module',
        'dir_plugin'    =>SYSTEM.'/Plugin',
        'dir_view'      =>SYSTEM.'/View',
        'dir_front'     =>$dir_front,
        'dir_statics'   =>$dir_front.'/statics',

    )
);




unset($dir_front);