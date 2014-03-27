<?php

class Dw_Event extends PDO{
    protected $arr = array();//这里是配置项
    public function __construct() {
        echo "请在这里连接活动的写入数据库";
    }
    
    public static function add(){
        echo "这是数据写入器的add测试";
    }
}
