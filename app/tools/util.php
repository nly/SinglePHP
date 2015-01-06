<?php
/**
 * Created by PhpStorm
 * @desc:
 * @package: Tools
 * @author: leandre
 * @copyright: copyright(2014)
 * @version: 14/12/20
 */

namespace Tools;

/**
 * Class Util
 * @package Tools
 */
class Util
{
    /**
     * 判断php宿主环境是否是64bit
     * ps: 在64bit下，php有诸多行为与32bit不一致，诸如mod、integer、json_encode/decode等，具体请自行google。
     * @return bool
     */
    public static function is_64bit()
    {
        return (int)0xFFFFFFFF !== -1;
    }

    /**
     * 修正过的ip2long
     *
     * 可去除ip地址中的前导0。32位php兼容，若超出127.255.255.255，则会返回一个float
     *
     * for example: 02.168.010.010 => 2.168.10.10
     *
     * 处理方法有很多种，目前先采用这种分段取绝对值取整的方法吧……
     * @param string $ip
     * @return float 使用unsigned int表示的ip。如果ip地址转换失败，则会返回0
     */
    public static function ip2long($ip)
    {
        $ip_chunks = explode('.', $ip, 4);
        foreach ($ip_chunks as $i => $v) {
            $ip_chunks[$i] = abs(intval($v));
        }
        return sprintf('%u', ip2long(implode('.', $ip_chunks)));
    }

    /**
     * 判断是否是内网ip
     * @param string $ip
     * @return boolean
     */
    public static function is_private_ip($ip)
    {
        $ip_value = self::ip2long($ip);
        return ($ip_value & 0xFF000000) === 0x0A000000 //10.0.0.0-10.255.255.255
        || ($ip_value & 0xFFF00000) === 0xAC100000 //172.16.0.0-172.31.255.255
        || ($ip_value & 0xFFFF0000) === 0xC0A80000 //192.168.0.0-192.168.255.255
            ;
    }
}