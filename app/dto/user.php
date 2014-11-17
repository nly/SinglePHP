<?php
/**
 * Created by PhpStorm
 * @desc: 用户数据对象 测试
 * @package: DTO
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/10/30
 */
namespace DTO;

use Lib\Argchecker\Argchecker;

class User extends Dto_Abstract
{
    public $fields = array(
        'id' => array(
            'int',
            'min,0',
            Argchecker::NEED_NO_DEFAULT,
            Argchecker::RIGHT,
            ''
        ),
        'name' => array(
            'string',
            'min,1;max,20',
            Argchecker::NEED_MUST,
            Argchecker::RIGHT,
        ),
        'sex' => array(
            'int',
            'max,2',
            Argchecker::NEED_MUST,
            Argchecker::RIGHT,
            '0'
        ),
        'age' => array(
            'int',
            'min,0;max,100',
            Argchecker::NEED_MUST,
            Argchecker::RIGHT,
        ),
        'email' => array(
            'string',
            'email',
            Argchecker::NEED_MUST,
            Argchecker::RIGHT,
        )
    );
}