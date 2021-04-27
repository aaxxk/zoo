<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 2021/3/13
 * Time: 11:05
 */

namespace app\index\controller;

use app\model\AnimalModel;

class Index extends Base{

    public function index(){


        $animal = new AnimalModel();
        $animals = $animal->getAnimals();
        $member = session('member');

        $this->assign('member',$member);
        $this->assign('animal',$animals);

        return $this->fetch();
    }
}