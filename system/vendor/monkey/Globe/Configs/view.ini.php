<?php
/**
 * 视图组件提供者
 */
\Monkey\App\Config::setComponentProvider('view',
    array(
        'default_provider'=>'default',
        'default'=>'\Monkey\View\View',
    )
);
/**
 * MonkeyPHP提供的默认视图组件的配置
 */
\Monkey\App\Config::setComponentConfig('view','default',
    array(
        'charset'               =>'UTF-8',//
        'template_root'         =>'/template',//相对于应用目录
        'compiled_root'         =>'/temp/template_compiled',//相对于应用目录

        //分页栏配置：
        'page_style_name'       =>'def',
        'def_link'              =>'<a href="http://urlPre{number}">{text}</a>',
        'def_link_ajax'         =>'<a href="javascript:ajaxActionName(\'http://urlPre{number}\')">{text}</a>',
        'def_span_current'      =>'<span class="current_style">{number}</span>',
        'def_span_total'        =>'<span class="total_style">共{number}页</span>',
        'def_input_jump'        =>'转到<input type="text" class="jump_style" size="2" title="输入页码，按回车快速跳转" value="1" onkeydown="if(event.keyCode==13) {window.location=\'http://urlPre\'+this.value; doane(event);}" />',
        'def_text_first'        =>'首页',//另外，图片可以设置为：'<img src="..." width="16" height="11" />'，下同
        'def_text_pre'          =>'上一页',
        'def_text_next'         =>'下一页',
        'def_text_last'         =>'尾页',
        'def_layout'            =>'pre-current-next',//'first-pre-current-next-last'， 'first-pre-list-next-last'（list包含current）
                                                     //支持 first、pre、current、list（包含current）、next、last、total、jump

        //主题配置：结果是 'theme_url_base'.'theme_dir'.'cssFile'
        'theme_url_base'        =>'',//主题的基础url,空表示index.php所在目录
        'theme_dir'             =>'/mySkinName',
    )
);
