<?php
namespace app\admin\controller;

use admin\controller\MyController;
use app\admin\model\UserModel;

class User extends MyController
{


    public function index(){

        $user = new UserModel();
        $user_list = $user->getUserList();

        $this->assign('total',$user_list['count']);
        $this->assign('user',$user_list['list']);
        return $this->fetch();
    }

    //add
    public function add(){

        if(request()->isAjax()){
            $param=request()->param();
            $data=array(
                'username'=>$param['username'],
                'password'=>$param['password']
            );
            $result=db('user')->insert($data);
            if($result!=false){

                return ajaxRuturn2(200,'','Added successfully');
            }
            return ajaxRuturn2(500,'','add failed');
        }

       return $this->fetch();
    }

    //change Password
    public function editpass(){

        $id=input('id');

        if(request()->isAjax()){
            $param=request()->param();
            $data=array(
                'password'=>md5($param['password']),
                'id'=>$param['id']
            );

            $result=db('user')->where('id',$param['id'])->update($data);
            if($result!=false){
                return ajaxRuturn2('200','','Password reset complete');
            }
            return ajaxRuturn2('500','','Password modification failed');
        }

        $admin=db('user')->where('id',$id)->find();
        $this->assign('admin',$admin);
        return $this->fetch();
    }

    //delete users
    public function delete(){

        if(request()->isAjax()){
            $param=request()->param();
            $result=db('user')->where('id',$param['id'])->delete();
            if($result!=false){
                return ajaxRuturn2('200','','successfully deleted');
            }
            return ajaxRuturn2('500','','failed to delete');
        }

    }

    public function edit(){
        $model = model('UserModel');

        if($_POST){
            $data = [
                'username'=>$_POST['username'] ?? '',
                'real_name'=>$_POST['real_name'] ?? '',
                'net_name'=>$_POST['net_name'] ?? '',
                'profes'=>$_POST['profes'] ?? '',
                'email'=>$_POST['email'] ?? '',
                'motto'=>$_POST['motto'] ?? '',
                'user_id'=>$_POST['user_id'] ?? ''
            ];
            if(!empty($_POST['image'])){
                $data['image'] = $_POST['image'];
            }
            $model->updateInfo($data);

            ajaxReturn(200,'','Successfully modified!');
        }

        $id = input('id') ?? '';

        if(empty($id)){
            ajaxReturn(201,'','id cannot be empty！');
        }

        $userInfo = $model->getUserInfo($id);
        $this->assign('user',$userInfo);

        return $this->fetch();
    }

}
