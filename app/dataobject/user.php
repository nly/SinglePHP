<?php
/**
 * Created by PhpStorm
 * @desc: 用户数据对象 测试
 * @package: user.php
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/10/30
 */
namespace DataObject;

use Lib\Argchecker\Argchecker;

class User extends Do_Abstract
{
    public $fields = array(
        'id' => array(
            'int',
            'min,0;max,10',
            Argchecker::NEED_MUST,
            Argchecker::RIGHT,
            '5'
        ),
        'name' => array(
            'string',
            'min,1;max,10',
            Argchecker::NEED_MUST,
            Argchecker::RIGHT,
        ),
        'sex' => '',
        'age' => ''
    );
}