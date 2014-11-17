<?php
/**
 * Created by PhpStorm
 * @desc: 用户数据读取
 * @package: Dr
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 11/13/14
 */

namespace Dr;
class User extends Dr_Abstruct
{
    protected static $db_pool;

    protected static function db()
    {
        if (!self::$db_pool instanceof self)
            self::$db_pool = new self('user', 'user');
        return self::$db_pool;
    }

    public static function getUserList()
    {
        $db = self::db();
        $result = $db->select();
        return $result;
    }

    public static function getUserName()
    {
        $db = self::db();
        $result = $db->field('name')->select();
        return $result;
    }

    public static function getUserNameAge()
    {
        $db = self::db();
        $result = $db->field('name,age')->select();
        return $result;
    }

    public static function get5Users()
    {
        $db = self::db();
        $result = $db->limit(5)->select();
        return $result;
    }

    public static function getUserwhere()
    {
        $db = self::db();
        $result = $db->where('id=5')->select();
        return $result;
    }

    public static function one()
    {
        $db = self::db();
        $one = $db->find();
        return $one;
    }
}