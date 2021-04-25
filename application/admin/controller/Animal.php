<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 2021/3/24
 * Time: 17:39
 */

namespace app\admin\controller;

use admin\controller\MyController;
use app\model\AnimalModel;
use app\model\MemberModel;
use think\Request;

class Animal extends MyController{

    public function index(Request $request){

        $animal = new AnimalModel();
        $member = new MemberModel();

        if($request->isAjax()){
            $data = $request->param();

            $page = isset($data['page']) ? (int)$data['page'] : 1;
            $pageSize = isset($data['limit']) ? (int)$data['limit'] : 20;
            $name    = isset($data['key']['name']) ? trim($data['key']['name']) : '';

            $optData = [
                'name'=>$name
            ];

            $data = $animal->animalList($optData,$page,$pageSize);
            foreach($data['list'] as $k=>$v){

                $mem_info = $member->getUserInfo($v['mem_id'],'user_name');
                $data['list'][$k]['adopter'] = !empty($mem_info['user_name']) ? $mem_info['user_name'] : 'nobody';
                $data['list'][$k]['animal_type_name'] = $this->animal_type[$v['animal_type']];
            }

            layuiReturn($data['list'],$data['count']);
        }

        return $this->fetch();
    }

    public function add(){

        if(input('post.')){

            $data = [
                'name'=>input('post.name/s',''),
                'birth'=>input('post.birth/s',''),
                'images'=>input('post.images/s',''),
                'animal_type'=>input('post.animal_type/d',0),
//                'descs'=>input('post.descs/s',''),
                'avail'=>input('post.avail/s','')
            ];

            $animal = new AnimalModel();
            $result = $animal->addAnimal($data);

            if($result){
                return_json('',200,'add success!');
            }
            return_json('',201,'add fail!');
        }

        $this->assign('animal_type',$this->animal_type);
        return $this->fetch();
    }

    public function edit(){

        $animal = new AnimalModel();

        if(input('post.')){

            $id = input('post.id/d','');

            if(empty($id)){
                return_json('',201,'id is null!');
            }

            $data = [
                'name'=>input('post.name/s',''),
                'birth'=>input('post.birth/s',''),
                'images'=>input('post.images/s',''),
                'animal_type'=>input('post.animal_type/d',0),
//                'descs'=>input('post.descs/s',''),
                'avail'=>input('post.avail/s','')
            ];

            if(empty($data['images'])){
                unset($data['images']);
            }

            $result = $animal->updateAnimal($id,$data);

            if($result){
                return_json('',200,'edit success!');
            }
            return_json('',201,'edit fail!');
        }

        $id = input('get.id/d','');

        if(empty($id)){
            return_json('',201,'id is null!');
        }

        $animal_info = $animal->getInfo($id);

        $this->assign('animal_info',$animal_info);
        $this->assign('animal_type',$this->animal_type);
        return $this->fetch();
    }

    public function delete(){

        $id = input('post.id/d','');

        if(empty($id)){
            return_json('',201,'id is null!');
        }

        $animal = new AnimalModel();
        $result = $animal->updateAnimal($id,['status'=>-1]);

        if($result){
            return_json('',200,'delete success！');
        }
        return_json('',201,'delete fail!');
    }
}