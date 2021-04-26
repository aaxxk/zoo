<?php
namespace app\admin\controller;

use admin\controller\MyController;
use think\newlog\NewLog;

class Index extends MyController
{
    public function index()
    {

        $menu_list = require_once CONF_PATH.'admin/menu.php';
        $this->assign('menu_list',$menu_list['menu_list']);
        return $this->fetch();
    }

    public function welcome(){
        return $this->fetch();
    }
}
