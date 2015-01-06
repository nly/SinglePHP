<?php
/**
 * Created by PhpStorm
 * @desc: Session相关
 * @package: Lib\Util
 * @author: leandre
 * @copyright: copyright(2014)
 * @version: 14/12/20
 */

namespace Lib\Util;

/**
 * Session
 * Class Session
 * @package Lib\Util
 */
class Session
{
    /**
     * Session-设置session值
     * @param  string $key key值，可以为单个key值，也可以为数组
     * @param  string $value value值
     * @return string
     */
    public static function set($key, $value = '')
    {
        if (!is_array($key)) {
            $_SESSION[$key] = $value;
        } else {
            foreach ($key as $k => $v) $_SESSION[$k] = $v;
        }
        return true;
    }

    /**
     * Session-获取session值
     * @param  string $key key值
     * @return string
     */
    public static function get($key)
    {
        return (isset($_SESSION[$key])) ? $_SESSION[$key] : NULL;
    }

    /**
     * Session-删除session值
     * @param  string $key key值
     * @return string
     */
    public static function del($key)
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                if (isset($_SESSION[$k])) unset($_SESSION[$k]);
            }
        } else {
            if (isset($_SESSION[$key])) unset($_SESSION[$key]);
        }
        return true;
    }

    /**
     * Session-清空session
     */
    public static function clear()
    {
        session_destroy();
        $_SESSION = array();
    }
}