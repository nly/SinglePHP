<?php
/**
 * Created by PhpStorm
 * @desc: admin跳转器
 * @package:
 * @author: leandre
 * @copyright: copyright(2014)
 * @version: 14/12/15
 */
namespace Controller;

use Single\Controller;

class Admin extends Controller
{
    public function run()
    {
        $this->redirect("/admin/index");
    }
}