<?php
/**
 * Created by PhpStorm.
 * User: zhanglili
 * Date: 2021/2/22
 * Time: 13:55
 */

namespace app\model;

class AdminModel extends BaseModel{

    protected $table = 'wt_user';

    public function checkName($user_name){
        return $this->fetchRow("select * from {$this->table} where username = '{$user_name}'");
    }

}