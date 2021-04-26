<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    // +----------------------------------------------------------------------
    // | Application settings
    // +----------------------------------------------------------------------

    //Whether to enable permission control
    'on'=>true,
    //Types of access control 1: Instant control 2: Login access control
    'type'=>1,
    //data sheet
    'table'=>[
        'user'=>'admin',
        'role'=>'role',
        'node'=>'node',
        'role_has_node'=>'role_has_node',
        'admin_has_role'=>'admin_has_role'
    ],
    //Homepage exception
    'exception'=>[
        //'home'=>'all',
        'home'=>['index','welcome']
    ],
    //Super administrator
    'super'=>['zhangli'],
];
