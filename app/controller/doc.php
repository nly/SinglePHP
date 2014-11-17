<?php
namespace Controller;

use DTO\User;
use Lib\Exception\Controller_Exception;

class Doc extends Base
{
    public function _run()
    {
        try {
            $userinfo = array(
                'id' => 2,
                'name' => 'bob',
                'sex' => '1',
                'age' => '22'
            );
            $do = new User($userinfo);
            var_dump($do->get_fields());
            var_dump($do);
//            $do->name = 'alice';
//            var_dump($do->to_array());
//            throw new Controller_Exception('controller test error');
        } catch (Controller_Exception $e) {
            echo $e->getMessage();
        }
        //$this->display();
        //$tihs->display('index');
    }
}

