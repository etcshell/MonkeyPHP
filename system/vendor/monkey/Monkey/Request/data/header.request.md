
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
1. 为 close 表示非持久连接，即告诉对方（WEB服务器或者代理服务器），在完成本次请求的响应后，断开连接，不要等待本次连接的后续请求了
2. 为 Keep-Alive 表示持久连接（当页面包含多个元素时，持久连接可以减少下载时间）此时还应该使用单独的Keep-Alive请求头 来表明希望 WEB 服务器保持连接多长时间（秒）。例如：Keep-Alive：300

Date头域
----
Date头域表示消息发送的时间，时间的描述格式由rfc822定义（GMT时间）。
例如，Date:Mon,31 Dec 2001 04:25:57 GMT。Date描述的时间表示世界标准时，换算成本地时间，需要知道用户所在的时区。

Pragma头域
----
Pragma头域用来包含实现特定的指令。
最常用的是Pragma:no-cache，在HTTP/1.1协议中，它的含义和Cache- Control:no-cache相同。

## 以下是请求头域
=================
Accept请求头：
----
浏览器端可以接受的文档或媒体类型。*/* 表示任何类型，type/* 表示该类型下的所有子类型，type/sub-type。
如果服务器不能响应这个类型，应该发送一个406错误(non acceptable)

Accept-Charset：
----
浏览器申明自己接收的字符集， big5, gb2312, gbk, utf-8 等
如 Accept-Charset: utf-8

Accept-Encoding：
----
浏览器申明自己接收的编码方法，通常指定压缩方法，是否支持压缩，支持什么压缩方法（gzip，deflate）
如 Accept-Encoding: gzip, deflate

Accept-Language：
----
浏览器申明自己接收的自然语言
如 Accept-Language: zh-cn, en-us

Accept-Ranges请求头：
----
浏览器向服务器询问是否支持获取某个文档的一部分（片段）。
bytes：表示希望获取以字节为单位的片段。
如 Accept-Ranges: bytes

Ranges请求头：
----
浏览器申明自己只索取文档的一部分（片段）。（前提是服务器支持发送文档片段）
如 Range:bytes=206-5513 表示接受指定字节的文档片段

    表示头500个字节：bytes=0-499
    表示第二个500字节：bytes=500-999
    表示最后500个字节：bytes=-500
    表示500字节以后的范围：bytes=500-
    表示0个字节后的所有字节（即整个文件）：bytes=0- （一般不用这个，因为不用这个就表示响应整个文档了）
    第一个和最后一个字节：bytes=0-0,-1
    同时指定几个范围：bytes=500-600,601-999

If-Range请求头：
----
浏览器告诉 WEB 服务器，如果我请求的对象没有改变，就把我缺少的部分给我，如果对象改变了，就把整个对象给我。
浏览器通过发送请求对象的 ETag 或者 自己所知道的最后修改时间给 WEB 服务器，让其判断对象是否改变了。
总是跟 Range 头部一起使用。

Authorization请求头：
----
回答服务器的权限验证的信息。
当浏览器接收到来自WEB服务器的 WWW-Authenticate 响应时，表名服务器要求有某种权限才能响应请求，
这时，可以用 Authorization请求头 来回应向服务器表明自己的身份。
如 Authorization: Basic QWxhZGRpbjpvcGVuIHNlc2FtZQ==

User-Agent请求头:
----
用户代理，是一个特殊字符串头，使得服务器能够识别客户端使用的操作系统及版本、CPU 类型、浏览器及版本、浏览器渲染引擎、浏览器语言、浏览器插件等。浏览器表明自己的身份（是哪种浏览器）。
例如 User-Agent：Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.8.1.14) Gecko/20080404 Firefox/2、0、0、14

Age请求头：
----
当代理服务器用自己缓存的实体去响应请求时，用该头部表明该实体从产生到现在经过多长时间了。

Host请求头：
----
指定请求的服务器的域名和端口号（80端口号可以省略）。
如 Host：rss.sina.com.cn:8080

If-Match请求头：
----
只有请求内容与实体相匹配才有效。
如果对象的 ETag 没有改变，其实也就意味著对象没有改变，才执行请求的动作。

If-None-Match请求头：
----
如果内容未改变返回304代码，参数为服务器先前发送的Etag，与服务器回应的Etag比较判断是否改变
如果对象的 ETag 改变了，其实也就意味著对象也改变了，才执行请求的动作。

If-Modified-Since请求头：
----
如果请求的对象在该头部指定的时间之后修改了，才执行请求的动作（比如返回对象），否则应该发送304代码，告诉浏览器该对象没有修改。
如 If-Modified-Since：Thu, 10 Apr 2008 09:14:42 GMT

If-Unmodified-Since请求头：
----
如果请求的对象在该头部指定的时间之后没修改过，才执行请求的动作（比如返回对象）。

Proxy-Authenticate请求头：
----
代理服务器响应浏览器，要求其提供代理身份验证信息。
如 Proxy-Authorization：浏览器响应代理服务器的身份验证请求，提供自己的身份信息。

Max-Forwards请求头：
----
限制信息通过代理和网关传送的时间
Max-Forwards: 10

Via：
----
通知中间网关或代理服务器地址，通信协议
列出从客户端到 OCS 或者相反方向的响应经过了哪些代理服务器，他们用什么协议（和版本）发送的请求。
当客户端请求到达第一个代理服务器时，该服务器会在自己发出的请求里面添加 Via 头部，并填上自己的相关信息，
当下一个代理服务器收到第一个代理服务器的请求时，会在自己发出的请求里面复制前一个代理服务器的请求的Via 头部，并把自己的相关信息加到后面，
以此类推，当 OCS 收到最后一个代理服务器的请求时，检查 Via 头部，就知道该请求所经过的路由。
例如：Via：1.0 236.D0707195.sina.com.cn:80 (squid/2.6.STABLE13)

Referer请求头：
----
浏览器向 WEB 服务器表明自己是从哪个 网页/URL 获得/点击 当前请求中的网址/URL。
例如 Referer：http://www.sina.com/index.html
但是这个可以伪造，不太可靠。

Cookie请求头：
----
浏览器会把保存在该请求域名下的所有cookie值（不论用户是否选择）一起发送给服务器
如 Cookie: Version=1; Skin=new;

Content-Type请求头：
----
请求的与实体对应的MIME信息
如 Content-Type: application/x-www-form-urlencoded

Content-Length请求头：
----
请求的内容长度
如 Content-Length: 348

Expect请求头：
----
请求的特定的服务器行为

From请求头：
----
发出请求的用户的Email
如 From: user@email.com

Warning请求头：
----
关于消息实体的警告信息
