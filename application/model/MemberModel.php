<?php
/**
 * Created by PhpStorm.
 * User: ZLL
 * Date: 2021/3/1
 * Time: 22:29
 */

namespace app\model;

class MemberModel extends BaseModel{

    protected $table = "zl_member";

    public function updateUserInfo($uid,$data){
        return $this->think_update($this->table,$data,["uid"=>$uid]);
    }

    public function insertUser($data){
        return $this->think_insert($this->table,$data);
    }

    public function addMember($data){
        return $this->think_insert($this->table,$data);
    }

    public function checkuser($user_name){
        return $this->fetchRow("select * from {$this->table} where user_name = '{$user_name}'");
    }

    public function getUserInfo($mem_id,$filed = "*"){

        if(empty($mem_id)){
            return false;
        }
        return $this->fetchRow("select {$filed} from {$this->table} where id = {$mem_id}");
    }
}