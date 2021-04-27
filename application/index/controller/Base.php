<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 2021/3/13
 * Time: 11:06
 */

namespace app\index\controller;

use think\Controller;

class Base extends Controller {

    public function __construct()
    {
        parent::__construct();
        $this->isLogin();
    }

    public function isLogin(){

        $session=session('member');
//        if (empty($session)){
//            echo "please log in first！";die();
//        }
        $this->assign('session',$session);
    }

}