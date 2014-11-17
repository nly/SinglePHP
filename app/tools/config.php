<?php
/**
 * Created by PhpStorm
 * @desc: 工具相关 - 配置类
 * @package: Tools
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/11/11
 */
namespace Tools;

use Single\SingleException;

class Config
{
    /**
     * 获取配置文件中的配置项 用"."链接 不支持二维递归
     * eg: db_pool.user
     * @param $confpath
     * @return mixed
     * @throws SingleException
     * @throws \Exception
     */
    public static function get($confpath)
    {
        list($file, $key) = explode('.', $confpath, 2);
        $path = \Single\C('APP_PATH') . DS . 'config' . DS . $file . '.php';
        if (!file_exists($path)) {
            throw new SingleException('FILE ' . $path . ' not exists');
        }
        $array = include $path;
        return $array[$key];
    }
}