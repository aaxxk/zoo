<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
function ajaxReturn($code,$data,$msg){

    $param['code']=$code;
    $param['data']=$data;
    $param['msg']=$msg;

    echo json_encode($param,true);die();
}

function ajaxRuturn2($code,$data,$msg){
    $param['code']=$code;
    $param['data']=$data;
    $param['msg']=$msg;

    return $param;
}

//获取ip 地址
function get_client_ip() {
    static $ip = null;
    if ($ip !== null) {
        return $ip;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($arr[0]);
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}


function return_json($data,$status = 200,$msg = ''){

    $param['data'] = $data;
    $param['status'] = $status;
    $param['msg']  = $msg;

    echo json_encode($param);exit();
}

/*
     * layui分页查询插件特定返回数据格式 code msg count data
     * */
function layuiReturn($param,$count){
    $data['code'] = 0;
    $data['msg']  = '';
    $data['count']= $count;
    $data['data'] = $param;

    echo json_encode($data);exit();
}
/*
     * layui分页查询插件特定返回数据格式 code msg count data
     * */
function MessageLayuiPage($param,$count){
    $data['code']='0';
    $data['msg']='';
    $data['count']=$count;

    $row=array();
    foreach($param as $k=>$v){
        $row[$k]['id']=$v['id'];
        $row[$k]['name']=$v['name'];
        $row[$k]['email']=$v['email'];
        $row[$k]['addtime']=$v['addtime'];
        $row[$k]['ip']=$v['ip'];
        $row[$k]['mess']=$v['mess'];
    }
    $data['data']=$row;
    return $data;
}