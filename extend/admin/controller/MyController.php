<?php
namespace admin\controller;

use think\Controller;
class MyController extends Controller
{

    protected $animal_type = [
        0 =>'catamount',
        1 =>"canine",
        2 =>"reptilian",
        3 =>"amphibian",
        4=>"megafauna"
    ];

    //init
    public function __construct()
    {
        parent::__construct();
        $this->isLogin();
    }

    public function isLogin(){

        $session=session('admin');
        if (empty($session)){
            echo "Hello world！";die();
        }
        $this->assign('session',$session);
    }



}