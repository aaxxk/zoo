<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2021/3/22
 * Time: 22:47
 */

namespace app\index\controller;

use app\model\AnimalModel;
use app\model\MemberModel;
use app\model\RecordModel;

class User extends Base{


    public function record(){

        $member_info = session('member');

        if(empty($member_info)){
            echo "<alert>please login!</alert>";
        }

        $record = new RecordModel();
        $animal = new AnimalModel();
        $user_record = $record->getUserRecord($member_info['id']);

        foreach($user_record as $k=>$v){
            $animal_info = $animal->getInfo($v['aid']);
            $user_record[$k]['animal_image'] = !empty($animal_info['images']) ? '/'.$animal_info['images'] : '';
            $user_record[$k]['animal_name'] = !empty($animal_info['name']) ? $animal_info['name'] : '';
            $user_record[$k]['birth'] = !empty($animal_info['birth']) ? $animal_info['birth'] : '';
            $user_record[$k]['status'] = $v['status'] == 1 ? "PASS" : ($v['status'] == 0 ? "PENDING" : "DENIED");
        }

        $this->assign('user_record',$user_record);

        return $this->fetch();
    }

    public function animal(){

        $member_info = session('member');

        if(empty($member_info)){
            echo "<alert>please login!</alert>";
        }

        $animal = new AnimalModel();
        $user_animal = $animal->getUserAnimal($member_info['id']);

        $this->assign('user_animal',$user_animal);
        return $this->fetch();
    }

    public function register(){

        $user_name = input('post.user_name/s','');
        $email = input('post.email/s','');
        $password = input('post.password/s','');
        $confirm_password = input('post.confirm_password/s','');

        if(empty($user_name) || empty($email) || empty($password) || empty($confirm_password)){
            return_json('',201,'param is null!');
        }
        if($password != $confirm_password){
            return_json('',201,'password is diff!');
        }

        $member = new MemberModel();
        $user_info = $member->checkuser($user_name);

        if(!empty($user_info)){
            return_json('',201,'user_name is register!');
        }

        $data = [
            'user_name'=>$user_name,
            'email'=>$email,
            'password'=>md5($password)
        ];

        $result = $member->addMember($data);

        if($result){
            $data['id'] = $result;
            session('member',$data);
            return_json('',200,'register success!');
        }
        return_json('',201,'register error!');
    }

    public function login(){

        $user_name = input('post.user_name/s','');
        $password = input('post.password/s','');

        if(empty($user_name) || empty($password)){
            return_json('',201,'user_name password is null！');
        }

        $member = new MemberModel();
        $user_info = $member->checkuser($user_name);

        if(empty($user_info)){
            return_json('',201,'user_name is not register！');
        }
        if($user_info['password'] != md5($password)){
            return_json('',201,'password is error!');
        }

        session('member',$user_info);
        return_json('',200,'login success!');
    }

    public function logout(){
        session('member',null);
        return_json('',200,'logout success!');
    }

    public function apply(){

        $id = input('post.id/d','');

        if(empty($id)){
            return_json('',201,'id is null!');
        }

        $mem_info = session('member');
        if(empty($mem_info)){
            return_json('',201,'please login!');
        }

        $data = [
            'mem_id'=>$mem_info['id'],
            'aid'=>$id
        ];
        $record = new RecordModel();
        $record_info = $record->getRecord($data['mem_id'],$data['aid']);

        if(!empty($record_info)){
            return_json('',201,'You have already applied for it!');
        }
        $result = $record->addRecord($data);

        if($result){
            return_json('',200,'application succcess!');
        }
        return_json('',201,'application fail!');
    }
}