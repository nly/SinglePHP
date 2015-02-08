<?php
/**
 * Description: 验证码相关
 * @package:
 * @author: leandre
 * @copyright: copyright(2015)
 * @version: 15/2/1
 */
namespace Tools;

use Lib\Util\Session;

class Code
{

    const width = 62;
    const height = 30;

    /**
     * 获取随机数值
     * @param $key
     * @return int 返回转换后的字符串
     */
    private static function get_random_val($key)
    {
        srand((double)microtime() * 1000000);
        while (($authnum = rand() % 100000) < 10000) ;
        Session::set($key . 'singlephp_code', $authnum);
        return $authnum;
    }

    /**
     * 获取验证码图片
     * @param string $key
     * @return bool
     */
    public static function getCode($key = 'user_')
    {
        Header("Content-type: image/PNG");
        $im = imagecreate(self::width, self::height); //制定图片背景大小
        $black = imagecolorallocate($im, 0, 0, 0); //设定三种颜色
        $white = imagecolorallocate($im, 255, 255, 255);
        $gray = imagecolorallocate($im, 200, 200, 200);
        imagefill($im, 0, 0, $gray); //采用区域填充法，设定（0,0）
        $authnum = self::get_random_val($key);
        imagestring($im, 5, 10, 3, $authnum, $black);
        // 用 col 颜色将字符串 s 画到 image 所代表的图像的 x，y 座标处（图像的左上角为 0, 0）。
        //如果 font 是 1，2，3，4 或 5，则使用内置字体
        for ($i = 0; $i < 200; $i++) {
            $randcolor = imagecolorallocate($im, rand(0, 255), rand(0, 255), rand(0, 255));
            imagesetpixel($im, rand() % 70, rand() % 30, $randcolor);
        }
        $a = imagepng($im);
        imagedestroy($im);
        return $a;
    }

    public static function checkCode($code, $key = 'user_')
    {
        if (Session::get($key . 'singlephp_code') == $code) return true;
        return false;
    }
}