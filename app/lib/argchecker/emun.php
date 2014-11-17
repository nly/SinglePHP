<?php
/**
 * Created by PhpStorm
 * @desc: 枚举类型校验
 * @package: Lib\Argchecker
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/11/6
 */
namespace Lib\Argchecker;

class Enum extends Argchecker
{
    public static function enum($data, $enumerates)
    {
        $args = func_get_args();
        array_shift($args);
        return in_array(strval($data), $args, true);
    }
}