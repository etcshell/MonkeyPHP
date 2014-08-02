<?php
// list of valid extensions, ex. array("jpeg", "xml", "bmp")
$allowedExtensions = array("jpeg", "jpg", "png", "bmp");
// max file size in bytes
$sizeLimit = 10 * 1024 * 1024;
$uploader = new \Uploader\Uploader($allowedExtensions, $sizeLimit, 'GB2312');
$result = $uploader->saveToDir(__DIR__ . '/upload/');
// to pass data through iframe you will need to encode all html tags
echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);