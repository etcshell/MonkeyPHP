<?php
/**
     * 分析浏览器语言所得结果表：
     * 下表的名称即成FrameworkPHP的标准语言名称
     * 语言包下的语言子目录应以下面的名称为标准
    af           公用荷兰语
    af-za        公用荷兰语 – 南非
    sq           阿尔巴尼亚
    sq-al        阿尔巴尼亚 -阿尔巴尼亚
    ar           阿拉伯语
    ar-dz        阿拉伯语 -阿尔及利亚
    ar-bh        阿拉伯语 -巴林
    ar-eg        阿拉伯语 -埃及
    ar-iq        阿拉伯语 -伊拉克
    ar-jo        阿拉伯语 -约旦
    ar-kw        阿拉伯语 -科威特
    ar-lb        阿拉伯语 -黎巴嫩
    ar-ly        阿拉伯语 -利比亚
    ar-ma        阿拉伯语 -摩洛哥
    ar-om        阿拉伯语 -阿曼
    ar-qa        阿拉伯语 -卡塔尔
    ar-sa        阿拉伯语 – 沙特阿拉伯
    ar-sy        阿拉伯语 -叙利亚共和国
    ar-tn        阿拉伯语 -北非的共和国
    ar-ae        阿拉伯语 – 阿拉伯联合酋长国
    ar-ye        阿拉伯语 -也门
    hy           亚美尼亚
    hy-am        亚美尼亚的 -亚美尼亚
    az           azeri
    az-az-cyrl   azeri-(西里尔字母的) 阿塞拜疆
    az-az-latn   azeri(拉丁文)- 阿塞拜疆
    eu           巴斯克
    eu-es        巴斯克 -巴斯克
    be           belarusian
    be-by        belarusian-白俄罗斯
    bg           保加利亚
    bg-bg        保加利亚 -保加利亚
    ca           嘉泰罗尼亚
    ca-es        嘉泰罗尼亚 -嘉泰罗尼亚
    zh-hk        华 – 香港的 sar
    zh-mo        华 – 澳门的 sar
    zh-cn        华 -中国
    zh-chs       华 (单一化)
    zh-sg        华 -新加坡
    zh-tw        华 -台湾
    zh-cht       华 (传统的)
    hr           克罗埃西亚
    hr-hr        克罗埃西亚 -克罗埃西亚
    cs           捷克
    cs-cz        捷克 – 捷克
    da           丹麦文
    da-dk        丹麦文 -丹麦
    div          dhivehi
    div-mv       dhivehi-马尔代夫
    nl           荷兰
    nl-be        荷兰 -比利时
    nl-nl        荷兰 – 荷兰
    en           英国
    en-au        英国 -澳洲
    en-bz        英国 -伯利兹
    en-ca        英国 -加拿大
    en-cb        英国 -加勒比海
    en-ie        英国 -爱尔兰
    en-jm        英国 -牙买加
    en-nz        英国 – 新西兰
    en-ph        英国 -菲律宾共和国
    en-za        英国 – 南非
    en-tt        英国 – 千里达托贝哥共和国
    en-gb        英国 – 英国
    en-us        英国 – 美国
    en-zw        英国 -津巴布韦
    et           爱沙尼亚
    et-ee        爱沙尼亚的 -爱沙尼亚
    fo           faroese
    fo-fo        faroese- 法罗群岛
    fa           波斯语
    fa-ir        波斯语 -伊朗王国
    fi           芬兰语
    fi-fi        芬兰语 -芬兰
    fr           法国
    fr-be        法国 -比利时
    fr-ca        法国 -加拿大
    fr-fr        法国 -法国
    fr-lu        法国 -卢森堡
    fr-mc        法国 -摩纳哥
    fr-ch        法国 -瑞士
    gl           加利西亚
    gl-es        加利西亚 -加利西亚
    ka           格鲁吉亚州
    ka-ge        格鲁吉亚州 -格鲁吉亚州
    de           德国
    de-at        德国 -奥地利
    de-de        德国 -德国
    de-li        德国 -列支敦士登
    de-lu        德国 -卢森堡
    de-ch        德国 -瑞士
    el           希腊
    el-gr        希腊 -希腊
    gu           gujarati
    gu-in        gujarati-印度
    he           希伯来
    he-il        希伯来 -以色列
    hi           北印度语
    hi-in        北印度的 -印度
    hu           匈牙利
    hu-hu        匈牙利的 -匈牙利
    is           冰岛语
    is-is        冰岛的 -冰岛
    id           印尼
    id-id        印尼 -印尼
    it           意大利
    it-it        意大利 -意大利
    it-ch        意大利 -瑞士
    ja           日本
    ja-jp        日本 -日本
    kn           卡纳达语
    kn-in        卡纳达语 -印度
    kk           kazakh
    kk-kz        kazakh-哈萨克
    kok          konkani
    kok-in       konkani-印度
    ko           韩国
    ko-kr        韩国 -韩国
    ky           kyrgyz
    ky-kz        kyrgyz-哈萨克
    lv           拉脱维亚
    lv-lv        拉脱维亚的 -拉脱维亚
    lt           立陶宛
    lt-lt        立陶宛 -立陶宛
    mk           马其顿
    mk-mk        马其顿 -fyrom
    ms           马来
    ms-bn        马来 -汶莱
    ms-my        马来 -马来西亚
    mr           马拉地语
    mr-in        马拉地语 -印度
    mn           蒙古
    mn-mn        蒙古 -蒙古
    no           挪威
    nb-no        挪威 (bokm?l) – 挪威
    nn-no        挪威 (nynorsk)- 挪威
    pl           波兰
    pl-pl        波兰 -波兰
    pt           葡萄牙
    pt-br        葡萄牙 -巴西
    pt-pt        葡萄牙 -葡萄牙
    pa           punjab        语
    pa-in        punjab        语 -印度
    ro           罗马尼亚语
    ro-ro        罗马尼亚语 -罗马尼亚
    ru           俄国
    ru-ru        俄国 -俄国
    sa           梵文
    sa-in        梵文 -印度
    sr-sp-cyrl   塞尔维亚 -(西里尔字母的) 塞尔维亚共和国
    sr-sp-latn   塞尔维亚 (拉丁文)- 塞尔维亚共和国
    sk           斯洛伐克
    sk-sk        斯洛伐克 -斯洛伐克
    sl           斯洛文尼亚
    sl-si        斯洛文尼亚 -斯洛文尼亚
    es           西班牙
    es-ar        西班牙 -阿根廷
    es-bo        西班牙 -玻利维亚
    es-cl        西班牙 -智利
    es-co        西班牙 -哥伦比亚
    es-cr        西班牙 – 哥斯达黎加
    es-do        西班牙 – 多米尼加共和国
    es-ec        西班牙 -厄瓜多尔
    es-sv        西班牙 – 萨尔瓦多
    es-gt        西班牙 -危地马拉
    es-hn        西班牙 -洪都拉斯
    es-mx        西班牙 -墨西哥
    es-ni        西班牙 -尼加拉瓜
    es-pa        西班牙 -巴拿马
    es-py        西班牙 -巴拉圭
    es-pe        西班牙 -秘鲁
    es-pr        西班牙 – 波多黎各
    es-es        西班牙 -西班牙
    es-uy        西班牙 -乌拉圭
    es-ve        西班牙 -委内瑞拉
    sw           swahili
    sw-ke        swahili-肯尼亚
    sv           瑞典
    sv-fi        瑞典 -芬兰
    sv-se        瑞典 -瑞典
    syr          syriac
    syr-sy       syriac-叙利亚共和国
    ta           坦米尔
    ta-in        坦米尔 -印度
    tt           tatar
    tt-ru        tatar-俄国
    te           telugu
    te-in        telugu-印度
    th           泰国
    th-th        泰国 -泰国
    tr           土耳其语
    tr-tr        土耳其语 -土耳其
    uk           乌克兰
    uk-ua        乌克兰 -乌克兰
    ur           urdu
    ur-pk        urdu-巴基斯坦
    uz           uzbek
    uz-uz-cyrl   uzbek-(西里尔字母的) 乌兹别克斯坦
    uz-uz-latn   uzbek(拉丁文)- 乌兹别克斯坦
    vi           越南
    vi-vn        越南 -越南
     */