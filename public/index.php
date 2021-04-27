<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ Application entry file ]

//Configure log constant


// Define application catalog
define('APP_PATH', __DIR__ . '/../application/');
//Define the application directory of the configuration file
define('CONF_PATH', __DIR__ . '/../application/config/');

// Load the framework boot file
require __DIR__ . '/../thinkphp/start.php';
