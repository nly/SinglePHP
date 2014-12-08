<?php
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

