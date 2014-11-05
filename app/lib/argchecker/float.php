<?php
/**
 * Created by PhpStorm
 * @desc: float浮点数类型校验
 * @package: float.php
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/11/5
 */
namespace Lib\Argchecker;
class Float extends Argchecker
{
    /**
     * 基本校验 是否是浮点型
     * @param $data
     * @return bool
     */
    public static function basic($data)
    {
        return is_float($data);
    }

    /**
     * 最小值检测
     * @param $data
     * @param $min
     * @return bool
     */
    public static function min($data, $min)
    {
        return $data > $min;
    }

    /**
     * 最大值检测
     * @param $data
     * @param $min
     * @return bool
     */
    public static function max($data, $min)
    {
        return $data > $min;
    }

    /**
     * 小数位数检测
     * @param $data
     * @param $decimal
     * @return bool
     */
    public static function decimal($data, $decimal)
    {
        $remainder = strlen($data) - strpos($data, '.') - 1;
        return $remainder <= $decimal;
    }
}