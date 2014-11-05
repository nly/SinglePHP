<?php
/**
 * Created by PhpStorm
 * @desc: 数据库接口类
 * @package: db_interface.php
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/10/29
 */
namespace Lib\Db;
interface Db_Interface
{
    public function connect($config);

    public function beginTransaction();

    public function commit();

    public function rollback();

    public function execute($sql);

    public function query($sql);

    public function prepare($sql = null);

    public function last_insert_id();

}