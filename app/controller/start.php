<?php
namespace Controller;

use DTO\User;

class Start extends Base
{
    public function _run()
    {
        /**
         * $data = array(
         * 'name' => 'nly',
         * 'age' => 22,
         * 'sex' => 1,
         * 'email' => 'nly92@foxmail.com'
         * );
         * $dto = new User($data);
         * $add = \DW\User::add($dto);
         * var_dump($add);
         */


        $list = \Dr\User::getUserList();
        //var_dump($list);
        /*
        $namelist = \Dr\User::getUserName();
        var_dump($namelist);
        $nameagelist = \Dr\User::getUserNameAge();
        var_dump($nameagelist);
        $user5 = \Dr\User::get5Users();
        var_dump($user5);
        $where = \Dr\User::getUserwhere();
        var_dump($where);
        $one = \Dr\User::one();
        var_dump($one);
        */


        //$this->display('start');
    }
}

