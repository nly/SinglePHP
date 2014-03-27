<?php

class Controller_Event_Prize  extends Controller_Base implements Controller_Interface_Interface{
    public function _run() {
        $this->add();
        $this->del();
        $this->modify();
    }
    
    public function add() {
        echo 'add';
    }
    
    public function del () {
        echo 'del';
    }
    public function modify() {
        echo 'modify';
    }
}
