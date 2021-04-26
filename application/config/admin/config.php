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

//Replace the directory in html
    'view_replace_str'  =>  [
        '__PUBLIC__'=>'/public/static/admin',
        '__ROOT__' => '/',
    ],
    //Load the head and tail in html
    'template'  =>  [
        'layout_on'     =>  true,
        'layout_name'   =>  'layout/index',
        'layout_item'   =>  '{__REPLACE__}'
    ],
    //Homepage exception
    'exception'=>[
        //'home'=>'all',
        'home'=>['index','welcome']
    ]
];
