<?php
/**
 * Created by PhpStorm.
 * User: wang
 * Date: 2020/10/13
 * Time: 11:27
 */

return [

    'menu_list'=>[
    [
        'id'=>1,
        'sort'=>1,
        'title'=>'System Manage',
        'alwayShow'=>false,
        'icon'=>'&#xe723;',
        'children'=>[
            [
                'title'=>'Animal Manage',
                'path'=>'/index.php/admin/animal/index'
            ],
            [
                'title'=>'Application',
                'path'=>'/index.php/admin/apply/index'
            ]
        ]
    ]
]
    ];