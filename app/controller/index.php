<?php
namespace Controller;

class Index extends Base
{
    public function _run()
    {
        echo "hello world";
        echo testFunction();
        new \Dw\Event();
        echo "<br />";
        \Dw\Event::add();
        $this->display();
        //$this->display('index');
    }
}

