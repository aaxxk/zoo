<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2021/2/24
 * Time: 21:57
 */

namespace app\model;

class RecordModel extends BaseModel{

    protected $table = "zl_record";

    public function addRecord($data){
        return $this->think_insert($this->table,$data);
    }

    public function getRecord($mem_id,$aid){
        return $this->fetchRow("select * from {$this->table} where mem_id = {$mem_id} and aid = {$aid}");
    }

    public function getUserRecord($mem_id){
        return $this->fetchAll("select * from {$this->table} where mem_id = {$mem_id}");
    }

    public function recordList($optData,$page,$pageSize){

        $where = "status > -1";

        if(!empty($optData['status'])){
            if($optData['status'] == 2){
                $where = "status = 0";
            }else{
                $where = "status = 1";
            }
        }

        $sql = "select count(*) as pro_count from {$this->table} where {$where}";
        $pro_count = $this->fetchRow($sql);
        $count = $pro_count['pro_count'];

        $page = intval($page);
        $pageSize = intval($pageSize);
        $page = ($page < 1) ? 1 : $page;
        $begin = ($page -1) * $pageSize;

        $sql = "select * from {$this->table} where {$where}  order by id desc limit {$begin},{$pageSize}";
        $pro_list = $this->fetchAll($sql);

        return ["list"=>$pro_list,'count'=>$count];

    }

    public function getInfo($id){
        return $this->fetchRow("select * from {$this->table} where id = {$id}");
    }

    public function updateRecord($id,$data){
        return $this->think_update($this->table,$data,["id"=>$id]);
    }

}