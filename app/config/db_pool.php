<?php
/**
 * Created by PhpStorm
 * @desc: 数据库配置
 * @package: db_pool.php
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/11/10
 */
return array(
    'user' => array(
        'read' => array(
            'db_dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=test',
            'db_user' => 'root',
            'db_pwd' => 'root',
            'db_charset' => 'utf8',
            'db_params' => '',
        ),
        'write' => array(
            'db_dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=test',
            'db_user' => 'root',
            'db_pwd' => 'root',
            'db_charset' => 'utf8',
            'db_params' => '',
        )
    ),
);