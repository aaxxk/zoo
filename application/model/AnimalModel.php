<?php
/**
 * Created by PhpStorm.
 * User: Wang
 * Date: 2021/2/24
 * Time: 21:57
 */

namespace app\model;

class AnimalModel extends BaseModel{

    protected $table = "zl_animal";

    public function addAnimal($data){
        return $this->think_insert($this->table,$data);
    }

    public function animalList($optData,$page,$pageSize){

        $where = " status > -1";

        if(!empty($optData['name'])){
            $where .= " and name like '%{$optData['mobile_type']}%'";
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

    public function updateAnimal($pid,$data){
        return $this->think_update($this->table,$data,["id"=>$pid]);
    }

    public function getAnimals(){
        return $this->fetchAll("select * from {$this->table} where mem_id = 0 and status >-1");
    }

    public function getUserAnimal($mem_id){
        return $this->fetchAll("select * from {$this->table} where mem_id = {$mem_id}");
    }
}