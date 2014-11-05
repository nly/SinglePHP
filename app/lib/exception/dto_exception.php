<?php
/**
 * Created by PhpStorm
 * @desc: 数据对象异常类
 * @package: Dto_Exception.php
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/10/30
 */
namespace Lib\Exception;
class Dto_Exception extends \Single\SingleException
{
    public function __construct($message, array $extra = array())
    {
        parent::__construct($message, $extra);
    }
}