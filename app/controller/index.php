<?php
/**
 * Created by PhpStorm
 * @desc: index控制器
 * @package: index.php
 * @author: leandre
 * @copyright: copyright(2014)
 * @version: 14/10/27
 */
namespace Controller;

class Index extends Base
{
    public function run()
    {
        echo "hello world";
        \Single\W('test');

//        new \Dw\Event();
//        echo "<br />";
//        \Dw\Event::add();
//        $this->display();
//        //$this->display('index');
    }
}
