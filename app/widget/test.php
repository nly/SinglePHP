<?php

class Widget_Test extends Widget{
    public function invoke($data) {
        echo "Widget_Test";
        $this->assign('data', $data);
        $this->display();
    }
}
