<?php
/**
 * Created by PhpStorm
 * @desc: 后台首页
 * @package:
 * @author: leandre
 * @copyright: copyright(2014)
 * @version: 14/12/15
 */
namespace Controller\Admin;

use Lib\Util\Context;
use Lib\Util\Session;

class Index extends base
{
    public $need_login = true; //需要登录

    public function run()
    {
        $sysInfo = array();
        $sysInfo['gmt_time'] = gmdate("Y年m月d日 H:i:s", time());
        $sysInfo['bj_time'] = gmdate("Y年m月d日 H:i:s", time() + 8 * 3600);
        $sysInfo['server_name'] = Context::get_domain();
        $sysInfo['server_ip'] = gethostbyname(Context::get_server('SERVER_ADDR'));
        $sysInfo['port'] = Context::get_server('SERVER_PORT');
        $sysInfo['software'] = Context::get_server('SERVER_SOFTWARE');
        $sysInfo['php_version'] = PHP_VERSION;
        $sysInfo['php_sapi'] = php_sapi_name();
        $sysInfo['root_path'] = Context::get_server('DOCUMENT_ROOT') ? str_replace('\\', '/', Context::get_server('DOCUMENT_ROOT')) :
            str_replace('\\', '/', dirname(__FILE__));
        $sysInfo['current_user'] = @get_current_user();
        $sysInfo['diskfree'] = intval(diskfreespace(".") / (1024 * 1024)) . 'Mb';
        $sysInfo['timezone'] = date_default_timezone_get();
        $this->assign('sysinfo', $sysInfo);
        $name = Session::get('name');
        $uid = Session::get('uid');
        $this->assign('uid', $uid);
        $this->assign('name', $name);
        $this->display();
    }
}