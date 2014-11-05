<?php
namespace Widget;
class Test extends \Single\Widget{
    public function invoke($data) {
        echo "Widget_Test";
        $this->assign('data', $data);
        $this->display();
    }
}
