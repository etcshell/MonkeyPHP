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
     * @param integer $width 图像宽
     * @param integer $height 图像高
     * @param string $charset 字符集
     * @param integer $charNum 图像里的字符数量
     * @param integer $fontSize 字号大小
     * @param string $fontFile 字体文件
     * 生成png图像直接输出到屏幕
     * @return string 验证码字符串
     */
    public function verificationByCode(
        $width = 120, $height = 40, $charset = '', $charNum = 4, $fontSize = 25, $fontFile = NULL
    ) {
        if (empty($fontFile) || !file_exists($fontFile)) {
            $fontFile = dirname(__FILE__) . '/font/Molengo-Regular.ttf';
        }
        //验证码字符全集
        if (empty($charset)) {
            $charset = 'abcdefghijklmnpqrstuvwxyz123456789';
        }
        $charsArray = str_split($charset, 1);
        $charTotal = count($charsArray);
        $chars = $char = ''; //已选出的验证码字符子集 和 当前选择的验证码字符
        $image = imagecreate($width, $height); //创建一个新图形
        imagecolorallocate($image, 255, 255, 255); //设置背景,分配颜色
        $fontColor = null;
        for ($i = 0; $i < $charNum; $i++) {
            //取出字符
            $char = $charsArray[mt_rand(0, $charTotal - 1)];
            $chars .= $char;
            //随机生成颜色
            $fontColor = imagecolorallocate($image, rand(0, 120), rand(0, 120), rand(0, 220));
            //把生成的字符写入到图形中
            imagettftext(
                $image,
                $fontSize,
                rand(-5, 15),
                $fontSize * $i + 10,
                $height - 10,
                $fontColor,
                $fontFile,
                $char
            );
        }
        //添加像素点干扰
        for ($i = 0; $i < 80; $i++) {
            $pointColor = imagecolorallocate($image, rand(0, 230), rand(0, 230), rand(0, 240));
            imagesetpixel(
                $image,
                rand(0, $width),
                rand(0, $height),
                $pointColor
            );
        }
        //添加线干扰
        for ($i = 0; $i < 8; $i++) {
            $lineColor = imagecolorallocate($image, rand(0, 180), rand(0, 180), rand(0, 200));
            imageline(
                $image,
                rand(0, $width),
                rand(0, $height),
                rand(0, $width),
                rand(0, $height),
                $lineColor
            );
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
     * @param string $imagePath 图像文件路径
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
    public function getInfo($imagePath) {
        $imageInfo = @getimagesize($imagePath);
        if ($imageInfo == false) {
            return false;
        }
        $imageType = strtolower(
            substr(image_type_to_extension($imageInfo[2]), 1)
        );
        $imageSize = intval(sprintf("%u", filesize($imagePath)));
        $info = array(
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'type' => $imageType,
            'size' => $imageSize,
            'mime' => $imageInfo['mime']
        );
        return $info;
    }

    /**
     * 生成缩略图
     * @param string $image 原图
     * @param null|string $thumbFilePath 缩略图文件名,默认输出到屏幕
     * @param string $type 图像格式
     * @param int $thumbWidthMax 宽度
     * @param int $thumbHeightMax 高度
     * @param bool $interlace 启用隔行扫描
     * @return bool 成功返回true，失败返回false
     */
    public function thumb(
        $image, $thumbFilePath = null, $type = '', $thumbWidthMax = 200, $thumbHeightMax = 50, $interlace = true
    ) {
        $info = $this->getInfo($image); // 获取原图信息
        if ($info == false) {
            return false;
        }
        $sourceWidth = $info['width'];
        $sourceHeight = $info['height'];
        $type = empty($type) ? $info['type'] : $type;
        $type = strtolower($type);
        if ($type == 'jpg') {
            $type = 'jpeg';
        }
        $interlace = $interlace ? 1 : 0;
        unset($info);
        $scale = min(
            $thumbWidthMax / $sourceWidth,
            $thumbHeightMax / $sourceHeight
        ); // 计算缩放比例
        // 缩略图尺寸
        if ($scale >= 1) { // 超过原图大小不再缩略
            $thumbWidth = $sourceWidth;
            $thumbHeight = $sourceHeight;
        }
        else {
            $thumbWidth = (int)($sourceWidth * $scale);
            $thumbHeight = (int)($sourceHeight * $scale);
        }
        $createFun = 'ImageCreateFrom' . $type;
        $sourceImage = $createFun($image); // 载入原图
        //创建缩略图
        if ($type != 'gif' && function_exists('imagecreatetruecolor')) {
            $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
        }
        else {
            $thumbImage = imagecreate($thumbWidth, $thumbHeight);
        }
        // 复制图片
        if (function_exists('ImageCopyResampled')) {
            imagecopyresampled(
                $thumbImage,
                $sourceImage,
                0,
                0,
                0,
                0,
                $thumbWidth,
                $thumbHeight,
                $sourceWidth,
                $sourceHeight
            );
        }
        else {
            imagecopyresized(
                $thumbImage,
                $sourceImage,
                0,
                0,
                0,
                0,
                $thumbWidth,
                $thumbHeight,
                $sourceWidth,
                $sourceHeight
            );
        }
        if ('gif' == $type || 'png' == $type) {
            $backgroundColor = imagecolorallocate($thumbImage, 0, 255, 0); //  指派一个绿色
            imagecolortransparent($thumbImage, $backgroundColor); //  设置为透明色，若注释掉该行则输出绿色的图
        }
        // 对jpeg图形设置隔行扫描
        if ('jpeg' == $type) {
            imageinterlace($thumbImage, $interlace);
        }
        // 生成图片
        $imageFun = 'image' . $type;
        if (is_null($thumbFilePath)) {
            header('Content-type: image/' . $type);
            $imageFun($thumbImage);
        }
        else {
            dir_check(dirname($thumbFilePath));
            $imageFun($thumbImage, $thumbFilePath);
        }
        imagedestroy($thumbImage);
        imagedestroy($sourceImage);
        return true;
    }

    /**
     * 图片加水印
     * @param string $imagePath 原图路径
     * @param string $waterPath 水印图片路径
     * @param int $waterPositionOption =9 水印位置(0-9) 0为随机，其他代表九宫格的9个位置
     * @param array $waterTransColor 水印透明色 array(red,green,blue)
     * @param int $alpha =85       水印的透明度，0（完全透明）-100（完全不透明）之间
     * @param string $resultPath 带水印的结果图片路径,默认输出到浏览器
     * @return boolean
     */
    public function waterByPicture(
        $imagePath, $waterPath, $waterPositionOption = 9, $waterTransColor = array(), $alpha = 85,
        $resultPath = NULL
    ) {
        //检查图片是否存在
        if (!file_exists($imagePath) || !file_exists($waterPath)) {
            return false;
        }
        if (!is_null($resultPath)) {
            dir_check(dirname($resultPath));
        }
        //读取原图像文件
        $imageInfo = $this->getInfo($imagePath);
        $imageFun = 'imagecreatefrom' . $imageInfo['type'];
        $imageHandler = $imageFun($imagePath);
        //读取水印文件
        $waterInfo = $this->getInfo($waterPath);
        $waterFun = 'imagecreatefrom' . $waterInfo['type'];
        $waterHandler = $waterFun($waterPath);
        //设置透明色
        if (!empty($waterTransColor) && in_array($waterInfo['type'], array('gif', 'png', 'jpeg'))) {
            $transColor = imagecolorallocate(
                $waterHandler,
                $waterTransColor[0],
                $waterTransColor[1],
                $waterTransColor[2]
            );
            imagecolortransparent($waterHandler, $transColor);
        }
        $this->water(
            $imageHandler,
            $waterHandler,
            $resultPath,
            $imageInfo['type'],
            $imageInfo['width'],
            $imageInfo['height'],
            $waterInfo['width'],
            $waterInfo['height'],
            $waterPositionOption,
            $alpha,
            ($waterInfo['type'] == 'png')
        );
        return true;
    }

    /**
     * 生成文字水印
     * @param string $imagePath 原图路径
     * @param string $waterText 水印文本
     * @param string $resultPath 保存带水印的结果图片路径,默认输出到浏览器
     * @param int $waterPositionOption =9 水印位置(0-9) 0为随机，其他代表九宫格的9个位置
     * @param int $alpha =85       水印的透明度，0（完全透明）-100（完全不透明）之间
     * @param string $fontFile 字体文件
     * @param array $fontStyle 字体样式：array('size'=>12,'color'=>array(192,192,192),'angle'=>0,//逆时针旋转角度)
     * @return boolean
     */
    public function waterByText(
        $imagePath, $waterText, $resultPath = NULL, $waterPositionOption = 9, $alpha = 85, $fontFile = NULL,
        $fontStyle = array('size' => 12, 'color' => array(192, 192, 192), 'angle' => 0)
    ) {
        //检查图片是否存在
        if (!file_exists($imagePath)) {
            return false;
        }
        if (!is_null($resultPath)) {
            dir_check(dirname($resultPath));
        }
        if (empty($fontFile) || !file_exists($fontFile)) {
            $fontFile = __DIR__ . '/font/Molengo-Regular.ttf';
        }
        //读取原图像文件
        $imageInfo = $this->getInfo($imagePath);
        $imageFun = "imagecreatefrom" . $imageInfo['type'];
        $imageHandler = $imageFun($imagePath);
        //设置水印文本
        $waterTextBox = imagettfbbox(
            $fontStyle['size'],
            $fontStyle['angle'],
            $fontFile,
            $waterText
        );
        //取得文本框大小
        $waterTextw = abs($waterTextBox[2] - $waterTextBox[6]) + 10; //为margin_right加5px,
        $waterTexth = abs($waterTextBox[3] - $waterTextBox[7]) + 10;
        $waterHandler = imagecreatetruecolor($waterTextw, $waterTexth); //创建文本框
        $background = imagecolorallocate(
            $waterHandler,
            255 - $fontStyle['color'][0],
            255 - $fontStyle['color'][1],
            255 - $fontStyle['color'][2]
        );
        //设置背景颜色
        imagefill($waterHandler, 0, 0, $background); //填充背景颜色
        $textColor = imagecolorallocate(
            $waterHandler,
            $fontStyle['color'][0],
            $fontStyle['color'][1],
            $fontStyle['color'][2]
        );
        //设置文本颜色
        imagettftext(
            $waterHandler,
            $fontStyle['size'],
            $fontStyle['angle'],
            abs($waterTextBox[0]) + 3,
            abs($waterTextBox[7]) + 3,
            $textColor,
            $fontFile,
            $waterText
        );
        //写入文本
        $this->water(
            $imageHandler,
            $waterHandler,
            $resultPath,
            $imageInfo['type'],
            $imageInfo['width'],
            $imageInfo['height'],
            $waterTextw,
            $waterTexth,
            $waterPositionOption,
            $alpha
        );
        return true;
    }

    /**
     * 生成浮雕或浊刻文字水印
     * @param string $imagePath 原图路径
     * @param string $waterText 水印文本
     * @param string $fontFile 字体文件
     * @param int $fontSize 字号
     * @param int $fontAngle 逆时针旋转角度
     * @param bool $emboss 浮雕效果，TRUE为浮雕，FALSE为浊刻
     * @param int $waterPositionOption =9 水印位置(0-9) 0为随机，其他代表九宫格的9个位置
     * @param int $alpha =85       水印的透明度，0（完全透明）-100（完全不透明）之间
     * @param string $resultPath 带水印的结果图片路径,默认输出到浏览器
     * @return boolean
     */
    public function waterByTextOfEmboss(
        $imagePath, $waterText, $fontFile, $fontSize = 50, $fontAngle = 0, $emboss = true,
        $waterPositionOption = 9, $alpha = 60, $resultPath = NULL
    ) {
        //检查图片是否存在
        if (!file_exists($imagePath)) {
            return false;
        }
        if (!is_null($resultPath)) {
            dir_check(dirname($resultPath));
        }
        //读取原图像文件
        $imageInfo = $this->getInfo($imagePath);
        $imageFun = "imagecreatefrom" . $imageInfo['type'];
        $imageHandler = $imageFun($imagePath);
        //设置水印文本
        $waterTextBox = imagettfbbox(
            $fontSize,
            $fontAngle,
            $fontFile,
            $waterText
        );
        //取得文本框大小
        $waterTextw = abs($waterTextBox[2] - $waterTextBox[6]) + 10; //为margin_right加5px,
        $waterTexth = abs($waterTextBox[3] - $waterTextBox[7]) + 10;
        $waterHandler = imagecreatetruecolor($waterTextw, $waterTexth); //创建文本框
        $backgroundColor = imagecolorallocate($waterHandler, 10, 10, 10); //设置背景颜色
        imagefill($waterHandler, 0, 0, $backgroundColor); //填充背景颜色
        $color = 0;
        $pos = array(abs($waterTextBox[0]), abs($waterTextBox[7]));
        if ($emboss) {
            for ($i = 0; $i < 5; $i++) {
                $color = 65 + $i * 40;
                imagettftext(
                    $waterHandler,
                    $fontSize,
                    $fontAngle,
                    $pos[0] + 6 - $i,
                    $pos[1] + 6 - $i,
                    imagecolorallocate($waterHandler, $color, $color, $color),
                    $fontFile,
                    $waterText
                );
                //写入文本
            }
            $pos[0] += 1;
            $pos[1] += 1;
        }
        else {
            for ($i = 0; $i < 5; $i++) {
                $color = 65 + $i * 40; //$color=225-$i*40;
                imagettftext(
                    $waterHandler,
                    $fontSize,
                    $fontAngle,
                    $pos[0] + $i,
                    $pos[1] + $i,
                    imagecolorallocate($waterHandler, $color, $color, $color),
                    $fontFile,
                    $waterText
                );
                //写入文本
            }
            $pos[0] += 6;
            $pos[1] += 6;
        }
        imagettftext(
            $waterHandler,
            $fontSize,
            $fontAngle,
            $pos[0],
            $pos[1],
            imagecolorallocate($waterHandler, $color, $color, $color),
            $fontFile,
            $waterText
        );
        //写入文本
        imagecolortransparent($waterHandler, $backgroundColor);
        $this->water(
            $imageHandler,
            $waterHandler,
            $resultPath,
            $imageInfo['type'],
            $imageInfo['width'],
            $imageInfo['height'],
            $waterTextw,
            $waterTexth,
            $waterPositionOption,
            $alpha
        );
        return true;
    }

    /**
     * 生成水印
     * @param resource|int $imageHandler 源图像句柄
     * @param resource|int $waterHandler 水印图像句柄
     * @param string $resultImagePath 带水印的结果图片路径
     * @param string $resultImageType 带水印的结果图片类型
     * @param int $image_w 源宽
     * @param int $image_h 源高
     * @param int $water_w 水印宽
     * @param int $water_h 水印高
     * @param int $waterPositionOption 水印位置(0-9) 0为随机，其他代表九宫格的9个位置
     * @param int $alpha 水印的透明度，0（完全透明）-100（完全不透明）之间
     * @param bool $waterTypeIsPng 是否生成png格式，否则生成jpg格式
     */
    private function water(
        $imageHandler, $waterHandler, $resultImagePath, $resultImageType, $image_w, $image_h, $water_w, $water_h,
        $waterPositionOption = 9, $alpha = 85, $waterTypeIsPng = false
    ) {
        if ($alpha > 100) {
            $alpha = 100;
        }
        if ($alpha < 0) {
            $alpha = 0;
        }
        $pos = array();
        $pos[0] = array(rand(0, ($image_w - $water_w)), rand(0, ($image_h - $water_h))); //随机
        $pos[1] = array(0, 0); //1为顶端居左
        $pos[2] = array(($image_w - $water_w) / 2, 0); //2为顶端居中
        $pos[3] = array($image_w - $water_w, 0); //3为顶端居右
        $pos[4] = array(0, ($image_h - $water_h) / 2); //4为中部居左
        $pos[5] = array(($image_w - $water_w) / 2, ($image_h - $water_h) / 2); //5为中部居中
        $pos[6] = array($image_w - $water_w, ($image_h - $water_h) / 2); //6为中部居右
        $pos[7] = array(0, $image_h - $water_h); //7为底端居左
        $pos[8] = array(($image_w - $water_w) / 2, $image_h - $water_h); //8为底端居中
        $pos[9] = array($image_w - $water_w, $image_h - $water_h); //9为底端居右
        if (!isset($pos[$waterPositionOption])) {
            $waterPositionOption = 0;
        }
        $pos = $pos[$waterPositionOption];
        imagealphablending($imageHandler, true);
        if ($waterTypeIsPng) {
            $cut = imagecreatetruecolor($water_w, $water_h);
            imagecopy($cut, $imageHandler, 0, 0, $pos[0], $pos[1], $water_w, $water_h);
            imagecopy($cut, $waterHandler, 0, 0, 0, 0, $image_w, $image_h);
            imagecopymerge($imageHandler, $cut, $pos[0], $pos[1], 0, 0, $water_w, $water_h, $alpha); //拷贝水印到目标图片对象
            imagedestroy($cut);
        }
        else {
            imagecopymerge(
                $imageHandler,
                $waterHandler,
                $pos[0],
                $pos[1],
                0,
                0,
                $water_w,
                $water_h,
                $alpha
            ); //拷贝水印到目标图片对象
        }
        //生成带水印的图片，并保存它
        $saveImageFunction = 'image' . $resultImageType;
        if (is_null($resultImagePath)) {
            header('Content-type: image/' . $resultImageType);
            $saveImageFunction($imageHandler);
        }
        else {
            $saveImageFunction($imageHandler, $resultImagePath);
        }
        //释放内存
        imagedestroy($imageHandler);
        imagedestroy($waterHandler);
    }

}