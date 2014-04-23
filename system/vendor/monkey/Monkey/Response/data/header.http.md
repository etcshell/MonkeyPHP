# 常见响应报头的含义：

## 以下是请求头和响应头都通用的头域（这里只介绍常用的）
===================
Cache-Control头域
----
Cache-Control指定请求和响应遵循的缓存机制。在请求消息或响应消息中设置 Cache-Control并不会修改另一个消息处理过程中的缓存处理过程。
请求时的缓存指令包括no-cache、no-store、max-age、 max-stale、min-fresh、only-if-cached，
响应消息中的指令包括public、private、no-cache、no- store、no-transform、must-revalidate、proxy-revalidate、max-age。

各个消息中的指令含义如下：

    Public指示响应可被任何缓存区缓存。
    Private指示对于单个用户的整个或部分响应消息，不能被共享缓存处理。这允许服务器仅仅描述当用户的部分响应消息，此响应消息对于其他用户的请求无效。
    no-cache指示请求或响应消息不能缓存
    no-store用于防止重要的信息被无意的发布。在请求消息中发送将使得请求和响应消息都不使用缓存。
    max-age指示客户机可以接收生存期不大于指定时间（以秒为单位）的响应。
    min-fresh指示客户机可以接收响应时间小于当前时间加上指定时间的响应。
    max-stale指示客户机可以接收超出超时期间的响应消息。如果指定max-stale消息的值，那么客户机可以接收超出超时期指定值之内的响应消息。

Connection头域
----
为 close 表示非持久连接，即告诉对方（WEB服务器或者代理服务器），在完成本次请求的响应后，断开连接，不要等待本次连接的后续请求了。
为 Keep-Alive 表示持久连接（当页面包含多个元素时，持久连接可以减少下载时间）此时还应该使用单独的Keep-Alive请求头 来表明希望 WEB 服务器保持连接多长时间（秒）。例如：Keep-Alive：300

Date头域
----
Date头域表示消息发送的时间，时间的描述格式由rfc822定义（GMT时间, gmdate('r',$unixTime)）。
例如，Date:Mon,31Dec200104:25:57GMT。Date描述的时间表示世界标准时，换算成本地时间，需要知道用户所在的时区。

Pragma头域
----
Pragma头域用来包含实现特定的指令。
最常用的是Pragma:no-cache，在HTTP/1.1协议中，它的含义和Cache- Control:no-cache相同。


## 以下是响应头域
==================
Location响应头
----
Location响应头表示客户应当到哪里（URI）去提取文档（浏览器通过这个仅仅知道跳转内容，但不知道这是跳转指令，务必同时设置响应状态为302才能让浏览器知道执行跳转指令）。

Server响应头
----
Server响应头包含处理请求的原始服务器的软件信息。此域能包含多个产品标识和注释，产品标识一般按照重要性排序。
这个一般不用设置

实体头
----
请求消息和响应消息都可以包含实体信息，实体信息一般由实体头域和实体组成。
实体头域包含关于实体的原信息，实体头包括

 * Content-Encoding：
 
     文档的编码（Encode）方法，如gzip、none等。
     gzip注意，因为浏览器支持原因，需要查看请求头Accept-Encoding是否支持gzip，这个信息包含在$_SERVER['HTTP_ACCEPT_ENCODING']中。

 * Content-Language：
 
     实体内容的自然语言种类，如 en 或 zh-cn 等

 * Content-Length：
 
     表示实际传送的字节数，即实体内容的长度。只有当浏览器使用持久HTTP连接时才需要这个数据。

 * Content-Location：
 
     重新定位实体内容，一般不用这个，直接用 Location响应头 即可，二者意义相同。
     IIS中这个实体头会自动包含服务器的默认首页网址和IP，
     可以通过：右键弹出网站属性/HTTP头/添加，然后在弹出框中分别输入Content-Location和自定义的网站首页地址，从而防止IP地址泄漏。

 * Content-MD5：
 
     内容的MD5值

 * Content-Range：
 
     告诉浏览器，该响应的内容为整个文档对象的哪个部分。例如：Content-Range: bytes 21010-47021/47022 其中/后的47022表示总字节数
     这个在请求头使用Range请求头时使用。比如，请求头设置为 Range:bytes=206-5513 则响应头就应该是相对应的 Content-Range: bytes 206-5513
     这个在 多点下载 和 断点续传 中使用，一般还要 Accept-Ranges 响应头和Etag标记结合使用才能保证下载文件的唯一性（下载首段文档时必须设置 ETag响应头 ）。
     表示头500个字节：bytes=0-499
     表示第二个500字节：bytes=500-999
     表示最后500个字节：bytes=-500
     表示500字节以后的范围：bytes=500-
     表示0个字节后的所有字节（即整个文件）：bytes=0- （一般不用这个，因为不用这个就表示响应整个文档了）
     第一个和最后一个字节：bytes=0-0,-1
     同时指定几个范围：bytes=500-600,601-999

 * Content-Type：

     表示响应文档属于什么MIME类型，见MIME数据文件，客户端可以根据这个类型做出相应的处理方法。
     注意这个头还可以包含文档内容的编码字符集。

 * Content-Disposition：
 
     是 MIME 协议的扩展，指示浏览器如何显示实体内容。
     一定要加上 attachment（作为附件下载） 或 inline（在线打开）。
     还可以设置内容的编码集。
     例如header('Content-Disposition: attachment; filename="' . $filename . '.xls"; charset=utf-8');
     表示下载为电子表格并且字符集为UTF-8。
     另外这个响应头通常要和Content-Type配合使用。

 * Etag：

     设置一个特殊的标志，然后随同Expires头或Last-Modified头发送到浏览器，
     然后浏览器再请求刷新时，就可以检测回传的Etag标志（$_SERVER['HTTP_IF_NONE_MATCH']），来判断是否需要重新发送实体内容，
     如果是重复的内容只需要header('Etag:'.$etag,true,304);然后就可以退出程序了。
     注意这个标志需要禁止使用Session！

 * Expires：
 
     过期时间，时间的描述格式由rfc822定义（GMT时间, gmdate('r',$unixTime)），这段时间之内浏览器不会向服务器刷新请求，除非人为刷新操作；
     这段时间后应该将文档认作是过期，浏览器会抛弃之前的缓存 。

 * Last-Modified：

     文档的最后改动时间（GMT时间, gmdate('r',$unixTime)）。
     在这个时间到期时会向服务器询问是否有新版的文档，如果有则提出刷新请求，如果服务器响应状态为304（Not Modified）则不会提出刷新请求。
     这样可以防止浏览器过度刷新一定程度上放在了DDOS攻击。

 * Extension-Header：

    允许客户端定义新的实体头，但是这些域可能无法未接受方识别。
    实体可以是一个经过编码的字节流，
    它的编码方式由Content-Encoding或Content-Type定义，
    它的长度由Content-Length或Content-Range定义。

Allow：
----
服务器支持哪些请求方法（如GET、POST等）

Accept-Ranges响应头：
----
服务器申明自己是否支持发送文档的一部分（片段）。
bytes：表示支持发送以字节为单位的片段，none：表示不支持片段发送。
如 Accept-Ranges: bytes

Refresh响应头：
----
表明浏览器多长时间后请求最新的页面（单位为秒，而且仅刷新一次），同时还可以包括应该连接到的 URL 。
注意这种功能通常是通过设置HTML页面HEAD区的
＜META HTTP-EQUIV="Refresh" CONTENT="5;URL=http://host/path"＞实现，
这是因为，自动刷新或重定向对于那些不能使用CGI或Servlet的HTML编写者十分重要。
相比于Location响应头，Location响应头是立即跳转，而Refresh响应头是 延时跳转 或 延时刷新。
注意Refresh头不属于HTTP 1.1正式规范的一部分，而是一个扩展，但多数浏览器都支持它。

Vary响应头：
----
用于表示使用服务器驱动的协商从可用的响应表示中选择响应实体。
WEB服务器用该头部的内容告诉代理 Cache 服务器，在什么条件下才能用本响应所返回的对象响应后续的请求。

例1：

    比如，一个响应有两个版本压缩和非压缩的：
    当请求头 Accept-Encoding 包含 gzip（表示浏览器接受gzip压缩格式的内容），
    那么 Vary：Accept-Encoding 告诉代理服务器检查 Accept-Encoding请求头，发现其包含 gzip 了，就发送压缩后的版本。

例2：

    又比如，网站是国际话，为了加快响应生成了静态页面，一个响应就有多个自然语言的版本了（如zh-cn、en），这个在请求头 Accept-Language 中有定义
    当请求头 Accept-Language 中包含 zh-cn
    那么 Vary：Accept-Language 告诉代理服务器 检查 Accept-Language请求头，发现使用的是 zh-cn 语言，则发送简体中文版的缓存。

另外

    1.Vary响应头中可以设置多个协商条件，如 Vary：Accept-Encoding, User-Agent 。
    2.伪静态缓存也可以由开发者自己写的php程序来选择伪静态缓存，而不必每次响应都重新生成整个网页。

Set-Cookie响应头：
----
设置Cookies的过期时间，时间的描述格式由rfc822定义（GMT时间, gmdate('r',$unixTime)）
另外设置和页面关联的Cookie，不要直接使用php原生的Cookie设置方法！而应该使用Cookie类或Response类提供的专用方法；

WWW-Authenticate响应头：
----
表示客户应该在请求头Authorization中提供什么类型的授权信息。在包含401（Unauthorized）状态行的应答中这个头是必需的。
例如"WWW-Authenticate"设置为"BASIC realm=＼"executives＼""，
Monkey的Request请求类中不会处理客户端中回传过来的Authorization，而是直接让IIS或Apache去处理，如在.htaccess中处理。

Retry-After：
----
告诉客户程序多久后重复它的请求
由服务器和状态编码503（无法提供服务）配合发送，以标明再次请求之前应该等待多长时间。
此时间既可以是一个日期，也可以是一种一秒为单位的数目。如：
Retry-After:8
Retry-After: Mon.16.Mar 2000 18:22:22 GMT

Warning：
----
提供关于响应状态的补充信息。

缓存控制实例：下例强制浏览器不缓存
----
Expires: Mon, 26 Jul 1997 05:00:00 GMT
Cache-Control: no-cache
Pragma: no-cache
