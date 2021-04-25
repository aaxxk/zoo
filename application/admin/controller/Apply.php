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
use app\model\RecordModel;
use think\Request;

class Apply extends MyController{

    protected $status = [
        0=>"pending",
        1=>"passed",
        -1=>"denied"
    ];

    public function index(Request $request){

        $animal = new AnimalModel();
        $member = new MemberModel();
        $record = new RecordModel();

        if($request->isAjax()){
            $data = $request->param();

            $page = isset($data['page']) ? (int)$data['page'] : 1;
            $pageSize = isset($data['limit']) ? (int)$data['limit'] : 20;
            $status    = isset($data['key']['status']) ? trim($data['key']['status']) : '';

            $optData = [
                'status'=>$status
            ];

            $data = $record->recordList($optData,$page,$pageSize);
            foreach($data['list'] as $k=>$v){

                $mem_info = $member->getUserInfo($v['mem_id'],'user_name,email');
                $animal_info = $animal->getInfo($v['aid']);
                $data['list'][$k]['user_name'] = !empty($mem_info['user_name']) ? $mem_info['user_name'] : 'nobody';
                $data['list'][$k]['email'] = !empty($mem_info['email']) ? $mem_info['email'] : '';
                $data['list'][$k]['animal_name'] = !empty($animal_info['name']) ? $animal_info['name'] : '';
                $data['list'][$k]['animal_images'] = !empty($animal_info['images']) ? $animal_info['images'] : '';
                $data['list'][$k]['status'] = $this->status[$v['status']];
            }

            layuiReturn($data['list'],$data['count']);
        }

        return $this->fetch();
    }

    public function pass(){

        $id = input('post.id/d','');

        if(empty($id)){
            return_json('',201,'id is null!');
        }

        $record = new RecordModel();
        $animal = new AnimalModel();
        $record_info = $record->getInfo($id);

        if(empty($record_info)){
            return_json('',201,'system error!');
        }
        if($record_info['status'] != 0){
            return_json('',201,'This application has been processed!');
        }

        $animal_info = $animal->getInfo($record_info['aid']);
        if(empty($animal_info)){
            return_json('',201,'system error!');
        }
        if($animal_info['mem_id'] > 0){
            return_json('',201,'The animal has been adopted by others!');
        }

        $animal->updateAnimal($record_info['aid'],["mem_id"=>$record_info['mem_id']]);
        $result = $record->updateRecord($record_info['id'],["status"=>1]);
        if($result){
            return_json('',200,'action success！');
        }
        return_json('',201,'action fail!');
    }

    public function refuse(){

        $id = input('post.id/d','');

        if(empty($id)){
            return_json('',201,'id is null!');
        }

        $record = new RecordModel();
        $record_info = $record->getInfo($id);

        if(empty($record_info)){
            return_json('',201,'system error!');
        }
        if($record_info['status'] != 0){
            return_json('',201,'This application has been processed');
        }

        $result = $record->updateRecord($record_info['id'],["status"=>-1]);
        if($result){
            return_json('',200,'action success！');
        }
        return_json('',201,'action fail!');
    }
}