<?php
/**
 * Created by PhpStorm
 * @desc: 参数校验
 * @package: Lib\Argchecker
 * @author: leandre <nly92@foxmai.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/11/3
 */
namespace Lib\Argchecker;

use Lib\Exception\Dto_Exception;

class Argchecker
{
    const NEED_NO_DEFAULT = 1; //参数不存在，不使用默认值
    const NEED_USE_DEFAULT = 2; //参数不存在，则使用默认值
    const NEED_MUST = 3; //参数必须

    const WRONG_NO_DEFAULT = 1; //参数错误不使用默认值
    const WRONG_USE_DEFAULT = 2; //参数错误则使用默认值
    const RIGHT = 3; //参数必须正确

    const ARGCHACKER_NS = 'Lib\Argchecker\\'; //校验器路径空间

    /**
     * 整数型校验
     * @param $data
     * @param $rule
     * @param int $is_needed
     * @param int $must_correct
     * @param null $default
     * @return mixed
     */
    public static function int($data, $rule, $is_needed = 1, $must_correct = 1, $default = null)
    {
        return self::run_checker('int', $data, $rule, $is_needed, $must_correct, $default);
    }

    /**
     * 字符串类型校验
     * @param $data
     * @param $rule
     * @param int $is_needed
     * @param int $must_correct
     * @param null $default
     * @return mixed
     */
    public static function string($data, $rule, $is_needed = 1, $must_correct = 1, $default = null)
    {
        return self::run_checker('string', $data, $rule, $is_needed, $must_correct, $default);
    }

    /**
     * 浮点数类型校验
     * @param $data
     * @param $rule
     * @param int $is_needed
     * @param int $must_correct
     * @param null $default
     * @return mixed
     */
    public static function float($data, $rule, $is_needed = 1, $must_correct = 1, $default = null)
    {
        return self::run_checker('float', $data, $rule, $is_needed, $must_correct, $default);
    }

    /**
     * 枚举类型校验
     * @param $data
     * @param $rule
     * @param int $is_needed
     * @param int $must_correct
     * @param null $default
     * @return mixed
     */
    public static function enum($data, $rule, $is_needed = 1, $must_correct = 1, $default = null)
    {
        return self::run_checker('enum', $data, $rule, $is_needed, $must_correct, $default);
    }

    /**
     * 校验器
     * @param $argchecker_type
     * @param $data
     * @param $rule
     * @param $is_needed
     * @param $must_correct
     * @param $default
     * @return mixed
     * @throws Dto_Exception
     */
    private static function run_checker($argchecker_type, $data, $rule, $is_needed, $must_correct, $default)
    {
        if (($ret_data = self::get_value($data, $is_needed, $default) !== true)) {
            return $ret_data;
        }
        $argchecker_type = self::ARGCHACKER_NS . $argchecker_type;
        $check_rules = self::check_rules($argchecker_type, $rule);
        if ($check_rules) {
            $data = self::check($argchecker_type, $check_rules, $data, $must_correct, $default);
        }
        return self::get_return($data, $is_needed, $must_correct, $default);
    }

    /**
     * 获取值
     * @param $data
     * @param $is_needed
     * @param $default
     * @return bool|null
     * @throws Dto_Exception
     */
    private static function get_value($data, $is_needed, $default)
    {
        if (!in_array($is_needed, array(self::NEED_NO_DEFAULT, self::NEED_USE_DEFAULT, self::NEED_MUST))) {
            throw new Dto_Exception('argchecker NEED option param error');
        }
        if ($data === null) {
            //参数不存在也不需要使用默认值
            if ($is_needed == self::NEED_NO_DEFAULT) {
                return null;
            }
            //参数不存在则使用默认值
            if ($is_needed == self::NEED_USE_DEFAULT) {
                return $default;
            }
            //必须存在
            if ($is_needed == self::NEED_MUST) {
                throw new Dto_Exception('this field is a must field');
            }
        }
        return true;
    }

    /**
     * 返回处理校验规则
     * @param $argchecker_type
     * @param $rule
     * @return array
     * @throws Dto_Exception
     */
    private static function check_rules($argchecker_type, $rule)
    {
        $rules = explode(';', $rule);
        if (class_exists($argchecker_type) && method_exists($argchecker_type, 'basic')) {
            $check_rules = array(array('method' => 'basic', 'param' => array()));
        } else {
            $check_rules = array();
        }
        if (is_array($rules) && !empty($rule)) {
            foreach ($rules as $per_rule_str) {
                $per_rule_arr = explode(',', $per_rule_str);
                $rule_name = array_shift($per_rule_arr);
                if (!method_exists($argchecker_type, $rule_name)) {
                    throw new Dto_Exception('method ' . $rule_name . ' doesn\'t exist in ' . $argchecker_type);
                } else {
                    $check_rules[] = array('method' => $rule_name, 'param' => $per_rule_arr);
                }
            }
        }
        return $check_rules;
    }

    /**
     * 数据校验
     * @param $argchecker_type
     * @param $check_rules
     * @param $data
     * @param $must_correct
     * @param $default
     * @return null
     * @throws Dto_Exception
     */
    private static function check($argchecker_type, $check_rules, $data, $must_correct, $default)
    {
        if (!in_array($must_correct, array(self::WRONG_NO_DEFAULT, self::WRONG_USE_DEFAULT, self::RIGHT))) {
            throw new Dto_Exception('argchecker WRONG option param error');
        }
        foreach ($check_rules as $rule) {
            array_unshift($rule['param'], $data); //把数据放到数组最前列
            $ret = call_user_func_array(array($argchecker_type, $rule['method']), $rule['param']);
            if ($ret === false) {
                if ($must_correct == self::WRONG_NO_DEFAULT) {
                    return null;
                }
                if ($must_correct == self::WRONG_USE_DEFAULT) {
                    return $default;
                }
                if ($must_correct == self::NEED_MUST) {
                    throw new Dto_Exception($data . ' does not match ' . $rule['method']);
                }
            }
        }
        return $data;
    }

    /**
     * 获取返回值
     * @param $data
     * @param $is_needed
     * @param $must_correct
     * @param $default
     * @return mixed
     */
    private static function get_return($data, $is_needed, $must_correct, $default)
    {
        if ($data === null && ($is_needed == self::NEED_USE_DEFAULT || $must_correct == self::WRONG_USE_DEFAULT)) {
            return $default;

        }
        return $data;
    }

}