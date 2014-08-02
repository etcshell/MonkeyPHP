<?php
namespace Library;

/**
 * Image
 * 图像生成和处理类
 * @package Library
 */
class Image {
    /**
     * 生成png图像验证码
     * @param integer $width        图像宽
     * @param integer $height       图像高
     * @param string  $charset      字符集
     * @param integer $char_num     图像里的字符数量
     * @param integer $font_size    字号大小
     * @param string  $font_file    字体文件
     * 生成png图像直接输出到屏幕
     * @return string 验证码字符串
     */
   public function verificationByCode( $width=120,$height=40 ,$charset='',$char_num=4,$font_size=25  ,$font_file=NULL  ){
        if(empty($font_file) || !file_exists($font_file))
            $font_file= dirname(__FILE__).'/font/Molengo-Regular.ttf';
        //验证码字符全集
        if(empty($charset)) $charset='abcdefghijklmnpqrstuvwxyz123456789';
        $chars_array=str_split($charset,1);
        $char_total=  count($chars_array);
        $chars=$char='';//已选出的验证码字符子集 和 当前选择的验证码字符
        $image = imagecreate($width,$height);//创建一个新图形
        imagecolorallocate($image, 255, 255, 255);//设置背景,分配颜色
        $font_color=null;
        for($i=0;$i<$char_num;$i++){
            //取出字符
            $char=$chars_array[mt_rand(0,$char_total-1)];
            $chars.=$char;
            //随机生成颜色
            $font_color
            =imagecolorallocate($image,rand(0,120),rand(0,120),rand(0,220));
            //把生成的字符写入到图形中
            imagettftext($image
                    ,$font_size
                    ,rand(-5,15)
                    ,$font_size*$i+10,$height-10
                    ,$font_color,$font_file
                    ,$char);
        }
        //添加像素点干扰
        for($i=0;$i<80;$i++){
            $point_color
            =imagecolorallocate($image, rand(0,230), rand(0,230), rand(0,240));
            imagesetpixel($image
                    , rand(0,$width) , rand(0,$height)
                    , $point_color);
        }
        //添加线干扰
        for($i=0;$i<8;$i++){
            $line_color
            =imagecolorallocate($image, rand(0,180), rand(0,180), rand(0,200));
            imageline($image
                    ,rand(0,$width),rand(0,$height)
                    ,rand(0,$width),rand(0,$height)
                    ,$line_color);
        }
        //输出图形
        header('Content-type: image/png');
        imagepng($image);
        //释放资源
        imagedestroy($image);
       return $chars;
    }
    /**
     * 取得图像信息
     * @param string $image_path 图像文件路径
     * @return mixed 成功时返回图像信息，失败时为false
     * 图像信息结构：
     * array(
     *          "width"=>,
     *          "height"=>,
     *          "type"=>,
     *          "size"=>,
     *          "mime"=>
     *      );
     */
    public function getInfo($image_path) {
        $imageInfo = @getimagesize($image_path);
        if( $imageInfo== false) return false;
        $imageType = strtolower(
                substr(image_type_to_extension($imageInfo[2]),1));
        $imageSize = intval(sprintf("%u", filesize($image_path)));
        $info = array(
            'width'=>$imageInfo[0],
            'height'=>$imageInfo[1],
            'type'=>$imageType,
            'size'=>$imageSize,
            'mime'=>$imageInfo['mime']);
        return $info;
    }
    /**
     * 生成缩略图
     * @param string $image             原图
     * @param null|string $thumb_filePath  缩略图文件名,默认输出到屏幕
     * @param string $type              图像格式
     * @param int $thumb_width_max   宽度
     * @param int $thumb_height_max  高度
     * @param bool $interlace           启用隔行扫描
     * @return bool 成功返回true，失败返回false
     */
    public function thumb($image,$thumb_filePath=null,$type='',$thumb_width_max=200,$thumb_height_max=50,$interlace=true){
        $info  = $this->getInfo($image);// 获取原图信息
        if($info == false) return false;
        $source_width  = $info['width'];
        $source_height = $info['height'];
        $type = empty($type)?$info['type']:$type;
        $type = strtolower($type);
       if( $type=='jpg' ) $type='jpeg';
        $interlace  =  $interlace? 1:0;
        unset($info);
        $scale = min($thumb_width_max/$source_width
                        ,$thumb_height_max/$source_height); // 计算缩放比例
        // 缩略图尺寸
        if($scale>=1){// 超过原图大小不再缩略
            $thumb_width   =  $source_width;
            $thumb_height  =  $source_height;
        }else{
            $thumb_width  = (int)($source_width*$scale);
            $thumb_height = (int)($source_height*$scale);
        }
        $createFun = 'ImageCreateFrom'.$type;
        $source_image     = $createFun($image);// 载入原图
        //创建缩略图
        if($type!='gif' && function_exists('imagecreatetruecolor')){
            $thumb_image = imagecreatetruecolor($thumb_width, $thumb_height);
        }else{
            $thumb_image = imagecreate($thumb_width, $thumb_height);
        }
        // 复制图片
        if(function_exists('ImageCopyResampled')){
            imagecopyresampled($thumb_image, $source_image
                    , 0, 0, 0, 0
                    ,$thumb_width,$thumb_height,$source_width,$source_height);
        }else{
            imagecopyresized($thumb_image, $source_image
                    , 0, 0, 0, 0
                    ,$thumb_width,$thumb_height,$source_width,$source_height);
        }
        if('gif'==$type || 'png'==$type){
            $background_color = imagecolorallocate($thumb_image, 0,255,0);  //  指派一个绿色
            imagecolortransparent($thumb_image,$background_color);  //  设置为透明色，若注释掉该行则输出绿色的图
        }
        // 对jpeg图形设置隔行扫描
        if('jpeg'==$type)
            imageinterlace($thumb_image,$interlace);
        // 生成图片
        $imageFun = 'image'.$type;
        if(is_null($thumb_filePath)){
            header('Content-type: image/'.$type);
            $imageFun($thumb_image);
        }  else {
            dir_check(dirname($thumb_filePath));
            $imageFun($thumb_image,$thumb_filePath);
        }
        imagedestroy($thumb_image);
        imagedestroy($source_image);
        return TRUE;
    }
    /**
     * 图片加水印
     * @param string $image_path    原图路径
     * @param string $water_path    水印图片路径
     * @param int $water_position_option  =9 水印位置(0-9) 0为随机，其他代表九宫格的9个位置
     * @param array $water_trans_color 水印透明色 array(red,green,blue)
     * @param int $alpha  =85       水印的透明度，0（完全透明）-100（完全不透明）之间
     * @param string $result_path   带水印的结果图片路径,默认输出到浏览器
     * @return boolean
     */
    public function waterByPicture($image_path, $water_path, $water_position_option =9, $water_trans_color=array(), $alpha=85, $result_path=NULL){
        //检查图片是否存在
        if (!file_exists($image_path) || !file_exists($water_path))
            return false;
        if(!is_null($result_path))dir_check(dirname($result_path));
        //读取原图像文件
        $imageInfo = $this->getInfo($image_path);
        $imageFun = 'imagecreatefrom' . $imageInfo['type'];
        $image_handler = $imageFun($image_path);
        //读取水印文件
        $waterInfo = $this->getInfo($water_path);
        $waterFun = 'imagecreatefrom' . $waterInfo['type'];
        $water_handler = $waterFun($water_path);
        //设置透明色
        if(!empty($water_trans_color)
                && in_array($waterInfo['type'], array('gif','png','jpeg'))){
            $trans_color =imagecolorallocate($water_handler
                    ,$water_trans_color[0]
                    ,$water_trans_color[1]
                    ,$water_trans_color[2]
                    );
            imagecolortransparent($water_handler,$trans_color);
        }
        $this->_water($image_handler, $water_handler
                , $result_path, $imageInfo['type']
                , $imageInfo['width'], $imageInfo['height']
                , $waterInfo['width'], $waterInfo['height']
                , $water_position_option, $alpha,($waterInfo['type']=='png'));
        return TRUE;
    }
    /**
     * 生成文字水印
     * @param string $image_path    原图路径
     * @param string $water_text    水印文本
     * @param string $result_path   保存带水印的结果图片路径,默认输出到浏览器
     * @param int $water_position_option  =9 水印位置(0-9) 0为随机，其他代表九宫格的9个位置
     * @param int $alpha  =85       水印的透明度，0（完全透明）-100（完全不透明）之间
     * @param string $font_file     字体文件
     * @param array $font_style     字体样式：array('size'=>12,'color'=>array(192,192,192),'angle'=>0,//逆时针旋转角度)
     * @return boolean
     */
    public function waterByText($image_path,$water_text, $result_path=NULL, $water_position_option =9, $alpha=85,$font_file=NULL,$font_style=array('size'=>12,'color'=>array(192,192,192),'angle'=>0)){
        //检查图片是否存在
        if (!file_exists($image_path))
            return false;
        if(!is_null($result_path))dir_check(dirname($result_path));
        if(empty($font_file) || !file_exists($font_file))
            $font_file= config()->dir_frame.'/font/Molengo-Regular.ttf';
        //读取原图像文件
        $imageInfo = $this->getInfo($image_path);
        $imageFun = "imagecreatefrom" . $imageInfo['type'];
        $image_handler = $imageFun($image_path);
        //设置水印文本
        $water_text_box=imagettfbbox($font_style['size']
                , $font_style['angle']
                , $font_file
                , $water_text);//取得文本框大小
        $water_text_w = abs($water_text_box[2] - $water_text_box[6])+10;//为margin_right加5px,
        $water_text_h = abs($water_text_box[3] - $water_text_box[7])+10;
        $water_handler=imagecreatetruecolor($water_text_w, $water_text_h);//创建文本框
        $background=imagecolorallocate($water_handler
                , 255-$font_style['color'][0]
                , 255-$font_style['color'][1]
                , 255-$font_style['color'][2]);//设置背景颜色
        imagefill($water_handler,0,0,$background);//填充背景颜色
        $text_color=imagecolorallocate($water_handler
                , $font_style['color'][0]
                , $font_style['color'][1]
                , $font_style['color'][2]);//设置文本颜色
        imagettftext($water_handler
                , $font_style['size']
                , $font_style['angle']
                , abs($water_text_box[0])+3, abs($water_text_box[7])+3
                , $text_color
                , $font_file
                , $water_text);//写入文本
        $this->_water($image_handler, $water_handler
                , $result_path, $imageInfo['type']
                , $imageInfo['width'], $imageInfo['height']
                , $water_text_w, $water_text_h
                , $water_position_option, $alpha);
        return TRUE;
    }
    /**
     * 生成浮雕或浊刻文字水印
     * @param string $image_path    原图路径
     * @param string $water_text    水印文本
     * @param string $font_file     字体文件
     * @param int $font_size    字号
     * @param int $font_angle   逆时针旋转角度
     * @param bool  $emboss    浮雕效果，TRUE为浮雕，FALSE为浊刻
     * @param int $water_position_option  =9 水印位置(0-9) 0为随机，其他代表九宫格的9个位置
     * @param int $alpha  =85       水印的透明度，0（完全透明）-100（完全不透明）之间
     * @param string $result_path   带水印的结果图片路径,默认输出到浏览器
     * @return boolean
     */
    public function waterByTextOfEmboss($image_path
            ,$water_text
            ,$font_file
            ,$font_size=50
            ,$font_angle=0
            ,$emboss=TRUE
            , $water_position_option =9
            , $alpha=60
            , $result_path=NULL){
        //检查图片是否存在
        if(!file_exists($image_path))return false;
        if(!is_null($result_path))dir_check(dirname($result_path));
        //读取原图像文件
        $imageInfo = $this->getInfo($image_path);
        $imageFun = "imagecreatefrom" . $imageInfo['type'];
        $image_handler = $imageFun($image_path);
        //设置水印文本
        $water_text_box=imagettfbbox($font_size
                , $font_angle
                , $font_file
                , $water_text);//取得文本框大小
        $water_text_w = abs($water_text_box[2] - $water_text_box[6])+10;//为margin_right加5px,
        $water_text_h = abs($water_text_box[3] - $water_text_box[7])+10;
        $water_handler=imagecreatetruecolor($water_text_w, $water_text_h);//创建文本框
        $background_color=imagecolorallocate($water_handler, 10, 10, 10);//设置背景颜色
        imagefill($water_handler,0,0,$background_color);//填充背景颜色
        $color=0;
        $pos=array(abs($water_text_box[0]),abs($water_text_box[7]));
        if($emboss){
            for($i=0;$i<5;$i++){
                $color=65+$i*40;
                imagettftext($water_handler
                    , $font_size
                    , $font_angle
                    , $pos[0]+6-$i, $pos[1]+6-$i
                    , imagecolorallocate($water_handler, $color,$color, $color)
                    , $font_file
                    , $water_text);//写入文本
            }
            $pos[0]+=1; $pos[1]+=1;
        }else{
            for($i=0;$i<5;$i++){
                $color=65+$i*40;//$color=225-$i*40;
                imagettftext($water_handler
                    , $font_size
                    , $font_angle
                    , $pos[0]+$i, $pos[1]+$i
                    , imagecolorallocate($water_handler, $color,$color, $color)
                    , $font_file
                    , $water_text);//写入文本
            }
            $pos[0]+=6; $pos[1]+=6;
        }
        imagettftext($water_handler
            , $font_size
            , $font_angle
            , $pos[0], $pos[1]
            , imagecolorallocate($water_handler, $color,$color, $color)
            , $font_file
            , $water_text);//写入文本
        imagecolortransparent($water_handler, $background_color);
        $this->_water($image_handler, $water_handler
                , $result_path, $imageInfo['type']
                , $imageInfo['width'], $imageInfo['height']
                , $water_text_w, $water_text_h
                , $water_position_option, $alpha);
        return TRUE;
    }
    /**
     * 生成水印
     * @param resource|int $image_handler 源图像句柄
     * @param resource|int $water_handler 水印图像句柄
     * @param string $result_image_path 带水印的结果图片路径
     * @param string $result_image_type 带水印的结果图片类型
     * @param int $image_w 源宽
     * @param int $image_h 源高
     * @param int $water_w 水印宽
     * @param int $water_h 水印高
     * @param int $water_position_option 水印位置(0-9) 0为随机，其他代表九宫格的9个位置
     * @param int $alpha 水印的透明度，0（完全透明）-100（完全不透明）之间
     * @param bool $water_type_is_png 是否生成png格式，否则生成jpg格式
     */
    private function _water($image_handler,$water_handler
            ,$result_image_path,$result_image_type
            ,$image_w,$image_h
            ,$water_w,$water_h
            ,$water_position_option=9, $alpha=85,$water_type_is_png=FALSE){
        if($alpha>100 ) $alpha=100;
        if($alpha<0   ) $alpha=0;
        $pos=array();
        $pos[0]=array(rand(0,($image_w - $water_w)),rand(0,($image_h - $water_h)));//随机
        $pos[1]=array(0,0);//1为顶端居左
        $pos[2]=array(($image_w - $water_w)/2,0);//2为顶端居中
        $pos[3]=array($image_w - $water_w,0);//3为顶端居右
        $pos[4]=array(0,($image_h - $water_h)/2);//4为中部居左
        $pos[5]=array(($image_w - $water_w)/2,($image_h - $water_h)/2);//5为中部居中
        $pos[6]=array($image_w - $water_w,($image_h - $water_h)/2);//6为中部居右
        $pos[7]=array(0,$image_h - $water_h);//7为底端居左
        $pos[8]=array(($image_w - $water_w)/2,$image_h - $water_h);//8为底端居中
        $pos[9]=array($image_w - $water_w,$image_h - $water_h);//9为底端居右
        if(!isset($pos[$water_position_option]))$water_position_option=0;
        $pos=$pos[$water_position_option];
        imagealphablending($image_handler, true);
        if($water_type_is_png){
            $cut=imagecreatetruecolor($water_w,$water_h);
            imagecopy($cut,$image_handler,0,0,$pos[0],$pos[1],$water_w,$water_h);
            imagecopy($cut,$water_handler,0,0,0, 0, $image_w,$image_h);
            imagecopymerge($image_handler,$cut,$pos[0],$pos[1],0,0,$water_w,$water_h,$alpha);//拷贝水印到目标图片对象
            imagedestroy($cut);
        }  else {
            imagecopymerge($image_handler,$water_handler,$pos[0],$pos[1],0,0,$water_w,$water_h,$alpha);//拷贝水印到目标图片对象
        }
        //生成带水印的图片，并保存它
        $save_image_function = 'image' . $result_image_type;
        if(is_null($result_image_path)){
            header('Content-type: image/'.$result_image_type);
            $save_image_function($image_handler);
        }  else {
            $save_image_function($image_handler, $result_image_path);
        }
        //释放内存
        imagedestroy($image_handler);
        imagedestroy($water_handler);
    }

}