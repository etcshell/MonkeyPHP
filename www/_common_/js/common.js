// 全局js代码
/**
 * 添加页面到收藏夹
 * sURL 目标链接，一般为window.location
 * sTitle 在收藏夹里显示的标题，一般为document.title即可
 */
function AddFavorite(sURL, sTitle){
    try{
        window.external.addFavorite(sURL, sTitle);
    }catch (e){
        try{
            window.sidebar.addPanel(sTitle, sURL, "");
        }catch (e){
            alert("加入收藏失败，请使用Ctrl+D进行添加");
        }
    }
}
/**
 * 将页面设置为主页
 * obj 窗体对象，一般为this即可
 * vrl 目标链接，一般为window.location
 */
function SetHome(obj,vrl){
    try{
            obj.style.behavior='url(#default#homepage)';obj.setHomePage(vrl);
    }catch(e){
            if(window.netscape) {
                    try {
                            netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
                    }catch (e) {
                            alert("此操作被浏览器拒绝！\n请在浏览器地址栏输入“about:config”并回车\n然后将[signed.applets.codebase_principal_support]设置为'true'");
                    }
                    var prefs = Components.classes['@mozilla.org/preferences-service;1'].getService(Components.interfaces.nsIPrefBranch);
                    prefs.setCharPref('browser.startup.homepage',vrl);
             }
    }
}
/** 同步动态加载js 用法:
    load_js_t(filename);
*/
function load_js_t(js_file){$.ajax({type:"POST",async:false,dataType:"script",url:js_file})}
/** 异步动态加载js 用法:
    load_js_y(filename);
*/
function load_js_y(js_file){$.ajax({type:"POST",async:true,dataType:"script",url:js_file})}
/**
    异步动态加载css
    用法:
    include_css(filename);
*/
function include_css(css_filename){
    if(!css_file_isLoaded(css_filename)){
        var fileref=document.createElement("link");
        fileref.setAttribute("rel", "stylesheet");
        fileref.setAttribute("type", "text/css");
        fileref.setAttribute("href", css_filename);
        document.getElementsByTagName("head")[0].appendChild(fileref);
    }
}
function css_file_isLoaded(css_file){
    var links=document.getElementsByTagName("link"); //得到所有的link对象集合
    for(i=0;i<links.length;i++){        //依次判断每个link对象
         if(links[i].href && links[i].href==css_file){ return true; }
    }
    return false;
}
/**
 * 鼠标掠过时高亮表格行
 * @param table_ID
 * @param high_light_css
 */
function bind_tr_mouse_over_out(table_ID,high_light_css){
    $("#"+table_ID)
        .find("tr")
        .each(function(i,item){
            $(this).mouseover(function(){
                $(this).addClass(high_light_css);
            });
            $(this).mouseout(function(){
                $(this).removeClass(high_light_css);
            });
        });
}
/**
 * 过滤表格中的显示数据
 * @param header_id
 * @param list_id
 * @param filtelabel_in_list
 */
function bind_list_filter(header_id,list_id,filtelabel_in_list){
    var searchlabel=filtelabel_in_list||"td";
    $('#'+header_id)
        .find('[filter]')
        .each(function(i,item){
            $(item).change(function(){
                $("#"+list_id)
                    .find('tr')
                    .show();
                $('#'+header_id)
                    .find('[filter]')
                    .each(function(i,col_item){
                        var sub_text=$(col_item).val();
                        if(sub_text.length=0)return true;
                        var filter_name=$(col_item).attr('filter');
                        $("#"+list_id)
                            .find('tr')
                            .each(function(i,row){
                                var all_text=$(row).find(searchlabel+'[filter="'+filter_name+'"]').text();
                                if(-1==all_text.indexOf(sub_text))$(row).hide()
                            })
                    })
            })
        });
}
/*********hy_validator**********/
/*用法：
*****第一步*****在需验证的表单元素中设置验证类型属性和参数属性
非空验证
             yz_type="empty" [yz_msg="您的出错提示"]
字符安全性验证
             yz_type="safe" [yz_msg="您的出错提示"]
数据库安全性验证
             yz_type="mysql" [yz_msg="您的出错提示"]
电子邮件验证
             yz_type="email" [yz_msg="您的出错提示"]
邮政编码验证
             yz_type="zcode" [yz_msg="您的出错提示"]
网址验证
             yz_type="url" [yz_msg="您的出错提示"]
整数验证
             yz_type="int" [yz_msg="您的出错提示"]
2位小数的实数验证
             yz_type="float" [yz_msg="您的出错提示"]
重复一致性验证
             yz_type="repeat" yz_to_id="对应比较元素的ID(对应元素必须存在)" [yz_msg="您的出错提示"]
电话号码,含手机,座机及座机区号验证
             yz_type="phone" [yz_msg="您的出错提示"]
字符长度验证
             yz_type="len" yz_max=最长值必须制定 [yz_min=最短值默认为0 yz_msg="您的出错提示"]
自定义正则表达式验证(异常强大)
             yz_type="reg" yz_reg="自定义正则表达式" yz_msg="您的出错提示(必须给出)"
*****第二步*****在网页中加入如下代码
<script type="text/javascript" src="浏览器路径/jquery.min.js" language="javascript"></script>
<script type="text/javascript" src="浏览器路径/common.js" language="javascript"></script>
<script type="text/javascript">
    event_yz("需要验证的表单ID");
    //除此之外，还要在submit事件中用onsubmit_yz("需要验证的表单ID")的返回值来判别是否提交，别忘了哦。
</script>
*/
var yz_ruler={
	add:function(ruler_name,ruler_fun,err_msg){
        this[ruler_name]={ruler:ruler_fun,msg:err_msg}
    },
    empty:{
        ruler:function(e){return ''!=e.value.replace(/(^\s*)|(\s*$)/g, "");},
        msg:'这里不能留空哦!'
    },
    safe:{
        ruler:function(e){return !/[<>\?\#\$\*\&;\\\/\[\]\{\}=\(\)\^%,]/i.test(e.value);},
        msg:'你输入的内容含有不安全字符！'
    },
    mysql:{
        ruler:function(e){return !/["'`\-\*;\s\[\]\{\}=\(\)\^%,]/i.test(e.value);},
        msg:'你输入的内容含有危险字符'
    },
    email:{
        ruler:function(e){return /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/i.test(e.value);},
        msg:'您输入的电子邮件的格式不对'
    },
    zcode:{
        ruler:function(e){return /^[1-9]\d{5}$/.test(e.value);},
        msg:'您输入的邮政编码的格式不对'
    },
    url:{
        ruler:function(e){return /^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/i.test(e.value);},
        msg:'您输入的网址的格式不对'
    },
    int:{
        ruler:function(e){return /^[-\+]?\d+$/.test(e.value);},
        msg:'您输入的内容不是数字'
    },
    float:{
        ruler:function(e){return /^[-\+]?[1-9]\d*(\.\d{0,2})?$/.test(e.value);},
        msg:'您输入的内容不是数字,或小数位超过2位'
    },
    phone:{
        ruler:function(e){return /^((\(\d{2,3}\))|(\d{3}\-))?(\(\d{3,4}\)|\d{3,4}-?)?[1-9]\d{6,7}(\-\d{1,4})?$/.test(e.value);},
        msg:'请正确输入电话号码'
    },
    repeat:{
        ruler:function(e){
            var compare=e.getAttribute("yz_to_id");
            if (!compare){return false;}
            compare=document.getElementById(compare);
            if (!compare){return false;}
            return compare.value== e.value;
        },
        msg:'您两次输入前后不一致!'
    },
    len:{
        ruler:function(e){
            var max=e.getAttribute("yz_max");
            if (!max){return false;}
            var min=e.getAttribute("yz_min") || 0;
            return e.value.length>=min && e.value.length<=max;
        },
        msg:'您输入的字符长度不在指定范围内!'
    },
    reg:{
        ruler:function(e){
            var yz_reg=e.getAttribute("yz_reg");
            if (!yz_reg){return false;}
            return RegExp(yz_reg,"gi").test(e.value);
        },
        msg:'自定义验证失败!'
    }
};
var alone_yz=function (e) {
    this.e=e;
    this.err_msg=this.e.getAttribute("yz_msg");
    this.type=this.e.getAttribute("yz_type");
    this.types=this.type.replace(/(^\s*)|(\s*$)/g, "");
    this.types=this.types.split("|");
    this.test=function(){
        if(!this.e){ this.err_msg='元素不存在!'; return false;}
        if(!this.type){return true;}
        if(!this.types){ this.err_msg='验证类型为空!'; return true;}
        for(var i=0; i<this.types.length; i++){
            if(yz_ruler[this.types[i]] && !yz_ruler[this.types[i]].ruler(this.e)){
                if(!this.err_msg)this.err_msg=yz_ruler[this.types[i]].msg;
                return false;
            }
        }
        return true;
    }
};
function event_yz(form_id){
    $(function(){
        $('#'+form_id)
        .find("[yz_type]")
        .blur(function(){
			var _yz=new alone_yz(this);
			if(_yz.test()){
                $('#'+this.getAttribute('err_id')).text('');
            }else{
                $('#'+this.getAttribute('err_id')).text(_yz.err_msg);
            }
            return false;
        })
	})
}
function onsubmit_yz(form_id){
    var is_pass=true;
    $('#'+form_id)
        .find("[yz_type]")
        .each(function(){
            var _yz=new alone_yz(this);
            if(!_yz.test()){
                $('#'+this.getAttribute('err_id')).text(_yz.err_msg);
                is_pass=false;
            }
        });
    return is_pass;
}