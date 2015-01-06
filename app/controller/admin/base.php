<?php
/**
 * Created by PhpStorm
 * @desc: 后台基础类
 * @package:
 * @author: leandre
 * @copyright: copyright(2014)
 * @version: 14/12/15
 */
namespace Controller\Admin;

use Lib\Util\Session;
use Single\Controller;

class base extends Controller
{
    public $userinfo = array();

    public function _init()
    {
        if ($this->need_login) {
            if (!$this->isLogin()) {
                $this->redirect('/admin/login');
            }
            $this->userinfo['id'] = Session::get('id');
            $this->userinfo['name'] = Session::get('name');
        }
    }

    /**
     * 检查是否登录
     * @return mixed
     */
    public function isLogin()
    {
        return Session::get('SingleAuth');
    }
}
