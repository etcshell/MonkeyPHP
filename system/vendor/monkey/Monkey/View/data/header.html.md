# HtmlHead
——HTML代码的<head>和</head>之间的内容


HTML头信息的内容虽不显示在网页正文中，但其中的信息被浏览器所使用，同时也包含了搜索引擎（baidu/google等）所检索的信息。

HTML头信息常用的HTML元素有：meta、title、style、link、script、base。
其中Style基本上不建议使用，单独放到css文件中更好；link和script由于解析的原因，link最好在前，script应该在后，如果条件可以，script放到body的最后（如果文档中没有对script引入的脚本文件的依赖）。


# meta元素

meta元素主要用来模拟http响应头的，以帮助正确和精确地显示网页内容，但也不完全是。其的属性有name和http-equiv两种。

## name属性：

    1、<meta name="Generator" contect="PCDATA|FrontPage|">
用以说明生成工具——编辑器（如Microsoft FrontPage 4.0）等；

    2、<meta name="Keywords" contect="Monkey,MonkeyPHP">
向搜索引擎说明你的网页的关键词；各关键词间用英文逗号“,”隔开。META的通常用处是指定搜索引擎用来提高搜索质量的关键词。当数个META元素提供文档语言从属信息时，搜索引擎会使用lang特性来过滤并通过用户的语言优先参照来显示搜索结果。

    3、<meta name="Description" contect="Monkey,MonkeyPHP">
Description用来告诉搜索引擎你的网站主要内容。

    4、<meta name="Author" contect="你的姓名，abc@sina.com">
告诉搜索引擎你的站点的制作的作者；

    5、<Meta name="Copyright" Content="本页版权归MonkeyPHP所有。All Rights Reserved">
标注版权

    6、<meta name="Robots" contect= "all|none|index|noindex|follow|nofollow">

　　网页搜索机器人向导。其中的属性说明如下：（默认为all）
　　all：文件将被检索，且页面上的链接可以被查询；
　　none：文件将不被检索，且页面上的链接不可以被查询；
　　index：文件将被检索；
　　follow：页面上的链接可以被查询；
　　noindex：文件将不被检索，但页面上的链接可以被查询；
　　nofollow：文件将不被检索，页面上的链接可以被查询。
许多搜索引擎都通过放出robot/spider搜索来登录网站，这些robot/spider就要用到meta元素的一些特性来决定怎样登录。

除了上面的值外，name属性还可以指定其他任意值，如：creationdate(创建日期) 、document ID(文档编号)和level[等级——则对应的Content可能是beginner(初级)、intermediate(中级)、advanced(高级)。]

## http-equiv属性

    1、<meta http-equiv="Content-Type" contect="text/html; charset=gb_2312-80">
说明页面所用的字符集，如英文是ISO-8859-1字符集，还有BIG5、utf-8、shift-Jis、Euc、Koi8-2等字符集；

    2、<meta http-equiv="Content-Language" contect="zh-CN">
说明页面所使用自然语言，如EN、FR等；

    3、<meta http-equiv="Refresh" contect="n; url=http://yourlink">
在n秒后，跳转到指定页面http://yourlink；
其中URL可以为空，表示定时刷新本页，这下可以不用js来刷新页面了！例如：
<Meta http-equiv="Refresh" Content="30">


    4、<meta http-equiv="Expires" contect="Mon,12 May 2001 00:20:00 GMT">
可以用于设定网页的到期时间，一旦过期则必须到服务器上重新调用。需要注意的是必须使用GMT时间格式，gmdate('r', $unixTime) 可以指定这个值；（个别浏览器兼容整数，代表秒数。）

    5、<meta http-equiv="Pragma" contect="no-cache">
是用于设定禁止浏览器从本地机的缓存中调阅页面内容，设定后一旦离开网页就无法从Cache中再调出；这样设定的副作用是，访问者将无法脱机浏览。

    6、<META HTTP-EQUIV="Window-target" CONTENT="_top">
强制页面在当前窗口以独立页面显示。用来防止别人在框架里调用你的页面。
所有选项有：_blank、_top、_self、_parent。

    7、<Meta http-equiv="Set-Cookie" Content="cookievalue=xxx; expires=Wednesday,
　　　　　　 21-Oct-98 16:14:21 GMT; path=/">
浏览器访问某个页面时会将它存在缓存中，下次再次访问时就可从缓存中读取，以提高速度。当你希望访问者每次都刷新你广告的图标，或每次都刷新你的计数器，就要禁用缓存了。通常HTML文件没有必要禁用缓存，对于ASP等页面，就可以使用禁用缓存，因为每次看到的页面都是在服务器动态生成的，缓存就失去意义。如果网页过期，那么存盘的cookie将被删除。必须使用GMT的时间格式。

    8、<META http-equiv="Pics-label" Contect="(PICS－1.1'http://www.rsac.org/ratingsv01.html'I gen comment 'RSACi North America Sever' by 'inet@microsoft.com'for 'http://www.microsoft.com' on '1997.06.30T14:21－0500' r(n0 s0 v0 l0))">
在IE的Internet选项中有一项内容设置，可以防止浏览一些受限制的网站，而网站的限制级别就是通过该参数来设置的。
不要将级别设置的太高。RSAC的评估系统提供了一种用来评价Web站点内容的标准。用户可以设置Microsoft Internet Explorer（IE3.0以上）来排除包含有色情和暴力内容的站点。上面这个例子中的HTML取自Microsoft的主页。代码中的（n 0 s 0 v 0 l 0）表示该站点不包含不健康内容。级别的评定是由RSAC，即美国娱乐委员会的评级机构评定的，如果你想进一步了解RSAC评估系统的等级内容，或者你需要评价自己的网站，可以访问RSAC的站点：http://www.rsac.org/。

    9、<Meta http-equiv="Page-Enter" Content="blendTrans(Duration=0.5)"> 和 <Meta http-equiv="Page-Exit" Content="blendTrans(Duration=0.5)">
这个是页面被载入和退出时的一些特效。
blendTrans是动态滤镜的一种，产生渐隐效果。另一种动态滤镜RevealTrans也可以用于页面进入与退出效果:

<Meta http-equiv="Page-Enter" Content="revealTrans(duration=x, transition=y)">

和

<Meta http-equiv="Page-Exit" Content="revealTrans(duration=x, transition=y)">

其中：
Duration　　表示滤镜特效的持续时间(单位：秒)
Transition　滤镜类型。表示使用哪种特效，取值为0-23。

	0 矩形缩小
	1 矩形扩大
	2 圆形缩小
	3 圆形扩大
	4 下到上刷新
	5 上到下刷新
	6 左到右刷新
	7 右到左刷新
	8 竖百叶窗
	9 横百叶窗
	10 错位横百叶窗
	11 错位竖百叶窗
	12 点扩散
	13 左右到中间刷新
	14 中间到左右刷新
	15 中间到上下
	16 上下到中间
	17 右下到左上
	18 右上到左下
	19 左上到右下
	20 左下到右上
	21 横条
	22 竖条
	23 以上22种随机选择一种

    
10、<Meta http-equiv="Content-Script-Type" Content="text/javascript">

这是近来W3C的规范，指明页面中脚本的类型。


# title元素

网页标题

    例如： <title>我的网页标题</title>

# link元素

    引用css文件 <LINK href="path/style.css" rel="stylesheet" type="text/css">


# script元素

引用js文件，或直接嵌入js代码（不提倡）
    1、<script type="text/javascript" src="path/your.js"></script>
    2、<script type="text/javascript" >
    3、    your js code ;
    4、</script>

# 其它用法
1、scheme (方案)

    说明：scheme can be used when name is used to specify how the value of content should be interpreted.

    用法：<meta scheme="ISBN" name="identifier" content="0-14-043205-1" />

2、Link (链接)

    说明：链接到文件
    用法：<Link href="soim.ico" rel="Shortcut Icon">
    注意：很多网站如果你把她保存在收件夹中后，会发现它连带着一个小图标，如果再次点击进入之后还会发现地址栏中也有个小图标。现在只要在你的页头加上这段话，就能轻松实现这一功能。
    <LINK> 用来将目前文件与其它 URL 作连结，但不会有连结按钮，用於 <HEAD> 标记间， 格式如：<link href="URL" rel="relationship">

3、Base (基链接)

    说明：插入网页基链接属性
    用法：<Base href="http://www.webjx.com/" target="_blank">
    注意：你网页上的所有相对路径在链接时都将在前面加上“http://www.webjx.com/”。
    其中target="_blank"是链接文件在新的窗口中打开，你可以做其他设置。将“_blank”改为“_parent”是链接文件将在当前窗口的父级窗口中打开；改为“_self”链接文件在当前窗口（帧）中打开；改为“_top”链接文件全屏显示。


# 题外话

以上是META标签的一些基本用法，其中最重要的就是：Keywords和Description的设定。为什么呢？道理很简单，这两个语句可以让搜索引擎能准确的发现你，吸引更多的人访问你的站点!根据现在流行搜索引擎(Google，Lycos，AltaVista等)的工作原理，搜索引擎先派机器人自动在WWW上搜索，当发现新的网站时，便于检索页面中的Keywords和Description，并将其加入到自己的数据库，然后再根据关键词的密度将网站排序。

由此看来，我们必须记住添加Keywords和Description的META标签，并尽可能写好关键字和简介。否则，后果就会是：

    ●如果你的页面中根本没有Keywords和Description的META标签，那么机器人是无法将你的站点加入数据库，网友也就不可能搜索到你的站点。
    ●如果你的关键字选的不好，关键字的密度不高，被排列在几十甚至几百万个站点的后面被点击的可能性也是非常小的。

写好Keywords(关键字)要注意以下几点：

    ●不要用常见词汇。例如www、homepage、net、web等。
    ●不要用形容词，副词。例如最好的，最大的等。
    ●不要用笼统的词汇，要尽量精确。例如“爱立信手机”，改用“T28SC”会更好。

“三人之行，必有我师”，寻找合适关键词的技巧是：到Google、Lycos、Alta等著名搜索引擎，搜索与你的网站内容相仿的网站，查看排名前十位的网站的META关键字，将它们用在你的网站上，效果可想而知了。

★小窍门

为了提高搜索点击率，这里还有一些“捷径”可以帮得到你：

    ●为了增加关键词的密度，将关键字隐藏在页面里(将文字颜色定义成与背景颜色一样)。
    ●在图像的ALT注释语句中加入关键字。如：<IMG SRC="xxx.gif" Alt="Keywords">
    ●利用HTML的注释语句，在页面代码里加入大量关键字。用法： <!-- 这里插入关键字 -->

# 最后来一个实例：
	<head>
	<title>文件头，显示在浏览器标题区</title>
	<meta http-equiv="Content-Language" content="zh-cn">
	<meta http-equiv="Content-Type" content="text/html; charset=gb2312">
	<meta name="GENERATOR" content="Microsoft FrontPage 4.0">
	<meta name="ProgId" content="FrontPage.Editor.Document">
	<meta name="制作人" content="闪电">
	<meta name="主题词" content="HTML 网页制作 网页">
	</head>