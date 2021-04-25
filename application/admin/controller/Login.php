<?php
namespace app\admin\controller;

use app\model\AdminModel;
use think\Controller;

class Login extends Controller
{
    public function index()
    {

        if($_POST){

            $adminModel = new AdminModel();

            $user_name = isset($_POST['username']) ? $_POST['username'] : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            if(empty($user_name) || empty($password)){
                return_json('',201,'请输入账号密码！');
            }
            $user_info = $adminModel->checkName($user_name);
            if(empty($user_info)){
                return_json('',201,'此账号还未注册！');
            }
            if($user_info['password'] != md5($_POST['password'])){
                return_json('',201,'密码不一致！');
            }
            $temp=array(
                'id'=>$user_info['user_id'],
                'username'=>$user_info['username'],
                'real_name'=>$user_info['real_name']
            );
            session('admin',$temp);
            return_json('',200,'登陆成功!');
        }

        $admin = session('admin');

        if(!empty($admin)){
            $this->success('您已登录！','/admin/index/index');
        }

        return $this->fetch('login/index');
    }


    //退出登陆
    public function outLogin(){

        session('admin',null);
        cookie('admin',null);
        $this->redirect('/zlllogin');

    }

}
