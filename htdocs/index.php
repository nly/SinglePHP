<?php
/**
 * Created by PhpStorm
 * @desc: 网站入口文件
 * @package: index.php
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/10/27
 */
header("Content-Type:text/html; charset=utf-8");
//error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
define('DS', DIRECTORY_SEPARATOR);
require '../SinglePHP.class.php';
$config = array(
    'APP_PATH' => dirname(dirname(__FILE__)) . DS . 'app',
    'LOG_PATH' => dirname(dirname(__FILE__)) . DS . 'logs',
    'USE_SESSION' => true, //开启SESSION会话
    'SHOW_LOAD_TIME' => true, //显示执行耗时
    'OUTPUT_ENCODE' => true, //压缩模板代码
    'DEBUG_MODE' => true, //开启调试模式
);
Single\SinglePHP::getInstance($config)->run();
