<?php
/**
 * Created by PhpStorm
 * @desc: Cookie操作
 * @package: Lib\Util
 * @author: leandre
 * @copyright: copyright(2014)
 * @version: 14/12/20
 */

namespace Lib\Util;

/**
 * Cookie操作
 * Class Cookie
 * @package Lib\Util
 */
class Cookie
{
    private static $prefix = "single_"; //cookie前缀
    private static $expire = 2592000; //cookie时间
    private static $path = '/'; //cookie路径
    private static $domain = '';

    /**
     * 设置cookie的值
     * @param  string $name cookie的名称
     * @param  string $val cookie值
     * @param  string $expire cookie失效时间
     * @param  string $path cookie路径
     * @param  string $domain cookie作用的主机
     * @return string
     */
    public static function set($name, $val, $expire = '', $path = '', $domain = '')
    {
        $expire = (empty($expire)) ? time() + self::$expire : $expire; //cookie时间
        $path = (empty($path)) ? self::$path : $path; //cookie路径
        $domain = (empty($domain)) ? self::$domain : $domain; //主机名称
        if (empty($domain)) {
            setcookie(self::$prefix . $name, $val, $expire, $path);
        } else {
            setcookie(self::$prefix . $name, $val, $expire, $path, $domain);
        }
        $_COOKIE[self::$prefix . $name] = $val;
    }

    /**
     * 获取cookie的值
     * @param  string $name cookie的名称
     * @return string
     */
    public static function get($name)
    {
        return $_COOKIE[self::$prefix . $name];
    }

    /**
     * 删除cookie值
     * @param  string $name cookie的名称
     * @param  string $path cookie路径
     * @return string
     */
    public static function del($name, $path = '')
    {
        self::set($name, '', time() - 3600, $path);
        $_COOKIE[self::$prefix . $name] = '';
        unset($_COOKIE[self::$prefix . $name]);
    }

    /**
     * 检查cookie是否存在
     * @param  string $name cookie的名称
     * @return string
     */
    public static function is_set($name)
    {
        return isset($_COOKIE[self::$prefix . $name]);
    }
}