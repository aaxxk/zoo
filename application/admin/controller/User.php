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

    //增
    public function add(){

        if(request()->isAjax()){
            $param=request()->param();
            $data=array(
                'username'=>$param['username'],
                'password'=>$param['password']
            );
            $result=db('user')->insert($data);
            if($result!=false){

                return ajaxRuturn2(200,'','添加成功');
            }
            return ajaxRuturn2(500,'','添加失败');
        }

       return $this->fetch();
    }

    //修改密码
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
                return ajaxRuturn2('200','','密码修改成功');
            }
            return ajaxRuturn2('500','','密码修改失败');
        }

        $admin=db('user')->where('id',$id)->find();
        $this->assign('admin',$admin);
        return $this->fetch();
    }

    //删除用户
    public function delete(){

        if(request()->isAjax()){
            $param=request()->param();
            $result=db('user')->where('id',$param['id'])->delete();
            if($result!=false){
                return ajaxRuturn2('200','','删除成功');
            }
            return ajaxRuturn2('500','','删除失败');
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

            ajaxReturn(200,'','修改成功！');
        }

        $id = input('id') ?? '';

        if(empty($id)){
            ajaxReturn(201,'','id 不能为空！');
        }

        $userInfo = $model->getUserInfo($id);
        $this->assign('user',$userInfo);

        return $this->fetch();
    }

}
