<?php
/**
 * Created by PhpStorm
 * @desc: 用户数据写入
 * @package: Dw
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 11/13/14
 */

namespace Dw;
class User extends Dw_Abstruct
{
    protected static $db_pool;

    protected static function db()
    {
        if (!self::$db_pool instanceof self)
            self::$db_pool = new self('user', 'user');
        return self::$db_pool;
    }

    public static function add($dto)
    {
        $db = self::db();
        $data = $dto->to_array();
        $result = $db->insert($data);
        return $result;
    }
}