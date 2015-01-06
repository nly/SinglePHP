<?php
/**
 * Created by PhpStorm
 * @desc: 字符串处理
 * @package: Tools
 * @author: leandre
 * @copyright: copyright(2015)
 * @version: 15/1/4
 */
namespace Tools;
/**
 * Class String
 * @package Tools
 */
class String
{
    /**
     * 去掉字符串前后的空格(半全角空格)
     * @param $str
     * @return mixed
     */
    static public function trim_cn($str)
    {
        $str = ' ' . $str;
        return preg_replace('/(^[\s\x{3000}]*)|([\s\x{3000}]*$)/u', '', $str);
    }

    /**
     * 替换所有的全半角空格
     * @param $str
     * @return mixed
     */
    public static function trim_all($str)
    {
        $str = str_replace(array("　", "\n", "\r"), " ", $str);
        $str = preg_replace("/[ ]{1,}/", " ", $str);
        $str = str_replace('＠', '@', $str);
        return $str;
    }

    /**
     * 截取字符串到固定长度，并补全“...”
     * @param $content
     * @param $length
     * @param string $charset
     * @param string $etc
     * @param bool $show_title
     * @return string
     */
    public static function content_truncate($content, $length, $charset = 'UTF-8', $etc = '...', &$show_title = FALSE)
    {
        $utf_width = mb_strwidth($content, $charset);
        $real_width = (strlen($content) + mb_strlen($content, $charset)) / 2;
        if ($real_width > $length + 2) {
            $get_width = $length;
            if (($utf_width - 1) * 2 <= $real_width) $get_width = $get_width / 2; //特殊字符截取的长度
            $content = mb_strimwidth($content, 0, $get_width, "", $charset) . $etc;
            $show_title = true;
        }
        return $content;
    }
}