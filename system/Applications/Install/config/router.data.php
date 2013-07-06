<?php
if (!defined('in_IDream'))exit('Access Denied');
/******** 控制器配置，开发时用，发布后改到缓存中 ********/
return array(
    'index'=>array(
        'controller'=>'frame_install',
        'action'=>'index',
        'enable'=>true,
        'role'=>'',
    ),
    'install'=>array(
        'controller'=>'frame_install',
        'action'=>'index',
        'enable'=>true,
        'role'=>'',
    ),
    'install/license'=>array(
        'controller'=>'frame_install',
        'action'=>'license',
        'enable'=>true,
        'role'=>'',
    ),
    'install/test'=>array(
        'controller'=>'frame_install',
        'action'=>'test',
        'enable'=>true,
        'role'=>'',
    ),
    'install/database'=>array(
        'controller'=>'frame_install',
        'action'=>'database',
        'enable'=>true,
        'role'=>'',
    ),
);