<?php
/**
 * Created by PhpStorm
 * @desc: string型校验
 * @package: string.php
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/11/5
 */
namespace Lib\Argchecker;

class String
{
    /**
     * 校验字符的ASCII码，过滤特殊字符
     * @param $data
     * @return bool
     */
    public static function basic($data)
    {
        $str = strval($data);
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $char_value = ord($str[$i]);
            if (($char_value < 32 && ($char_value !== 13 && $char_value !== 10 && $char_value !== 9)) || $char_value == 127) {
                return false;
            }
        }
        return true;
    }

    /**
     * 最小长度 中文汉字算3个
     * @param $data
     * @param $min
     * @return bool
     */
    public static function min($data, $min)
    {
        return strlen($data) > $min;
    }

    /**
     * 最大长度 中文汉字算3个
     * @param $data
     * @param $max
     * @return bool
     */
    public static function max($data, $max)
    {
        return strlen($data) < $max;
    }

    /**
     * 最小长度 中文汉字算1个长度
     * @param $data
     * @param $min
     * @return bool
     */
    public static function min_cn($data, $min)
    {
        return mb_strlen($data, 'utf-8') > $min;
    }

    /**
     * 最大长度 中文汉字算1个长度
     * @param $data
     * @param $max
     * @return bool
     */
    public static function max_cn($data, $max)
    {
        return mb_strlen($data, 'utf-8') < $max;
    }

    /**
     * 最小宽度 中文汉字算2个宽度
     * @param $data
     * @param $min
     * @return bool
     */
    public static function min_width_cn($data, $min)
    {
        return mb_strwidth($data, 'utf-8') > $min;
    }

    /**
     * 最大宽度 中文汉字算2个宽度
     * @param $data
     * @param $max
     * @return bool
     */
    public static function max_width_cn($data, $max)
    {
        return mb_strwidth($data, 'utf-8') < $max;
    }

    /**
     * 正则匹配
     * @param $data
     * @param $reg_exp
     * @return int
     */
    public static function regex($data, $reg_exp)
    {
        return preg_match($reg_exp, $data);
    }

    /**
     * 验证email
     * @param $email
     * @return bool
     */
    public static function email($email)
    {
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        return ($email) ? true : false;
    }

    /**
     * 验证url
     * @param $url
     * @return bool
     */
    public static function url($url)
    {
        $reg = "/^(http:\/\/|https:\/\/|ftp:\/\/){2,}/is";
        $matches = array();
        preg_match($reg, $url, $matches);
        if (!empty($matches)) {
            return false;
        }
        $url = filter_var($url, FILTER_VALIDATE_URL);
        return ($url) ? true : false;
    }

    /**
     * 验证ip
     * @param $ip
     * @return bool
     */
    public static function ip($ip)
    {
        $ip = filter_var($ip, FILTER_VALIDATE_IP);
        return ($ip) ? true : false;
    }

}