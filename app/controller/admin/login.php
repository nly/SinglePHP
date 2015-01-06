<?php
/**
 * Created by PhpStorm
 * @desc: 登录相关
 * @package:
 * @author: leandre
 * @copyright: copyright(2014)
 * @version: 14/12/20
 */

namespace Controller\Admin;

use Dr\User;
use Lib\Util\Context;
use Lib\Util\Cookie;
use Lib\Util\Session;
use Single\Controller;

class Login extends Controller
{
    public function run()
    {
        if (Context::param('action') == 'login') {
            $this->check();
            exit;
        }
        if (Context::param('action') == 'logout') {
            $this->logout();
            exit;
        }
        $now = time();
        Cookie::set('time', $now);
        $this->assign('uniq', $now);
        $this->display();
    }

    private function check()
    {
        if (Cookie::is_set('time') != Context::form('uniq')) {
            $this->redirect("/admin/login");
            exit;
        }
        $name = trim(Context::form('name'));
        $password = trim(Context::form('password'));
        if (!$name || !$password) {
            $this->redirect("/admin/login");
            exit;
        }

        // you should complete your login code here
    }

    private function logout()
    {
        Session::clear();
        $this->redirect("/admin/login");
    }
}