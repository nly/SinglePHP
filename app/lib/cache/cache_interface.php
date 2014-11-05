<?php
/**
 * Created by PhpStorm
 * @desc: 缓存接口类
 * @package: cache_interface.php
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/10/29
 */
namespace Lib\Cache;
interface Cache_Interface
{
    public function connect($config);

    public function get($key);

    public function set($key, $value, $expire);

    public function del($key);

    public function mget(array $keys);

    public function mset(array $values, $expire);

    public function mdel(array $keys);

}