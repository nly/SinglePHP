<?php
/**
 * Created by PhpStorm
 * @desc: int类型校验
 * @package: Lib\Argchecker
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/11/3
 */
namespace Lib\Argchecker;

class Int
{
    /**
     * 基本校验 是数字并且不是浮点数 包含所有进制
     * @param $data
     * @return bool
     */
    public static function basic($data)
    {
        return is_numeric($data) && !is_float($data);
    }

    /**
     * 最小值
     * @param $data
     * @param $min
     * @return bool
     */
    public static function min($data, $min)
    {
        return $data > $min;
    }

    /**
     * 最大值
     * @param $data
     * @param $max
     * @return bool
     */
    public static function max($data, $max)
    {
        return $data < $max;
    }

    /**
     * 手机号验证
     * @param $data
     * @return int
     */
    public static function phone($data)
    {
        return preg_match("/^1\d{10}$/", $data);
    }
}