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

// [ 应用入口文件 ]

//配置log常量
if(isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] == '127.0.0.1')
{
    define('NEW_LOG_HOST','121.196.171.252');
    define('NEW_LOG_PORT','5152');
    define('NEW_LOG_CHANNEL','voice_changer');
    define('NEW_LOG_EXECUTE_TIME',0);
    define('NEW_LOG_REQUEST',true);
    define('NEW_LOG_REQUEST_PARAM',true);
    define('NEW_LOG_MYSQL_SQL',true);

}else
{
    define('NEW_LOG_HOST','121.196.171.252');
    define('NEW_LOG_PORT','5151');
    define('NEW_LOG_CHANNEL','voice_changer');
    define('NEW_LOG_EXECUTE_TIME',0.1);
    define('NEW_LOG_REQUEST',true);
    define('NEW_LOG_REQUEST_PARAM',true);
    define('NEW_LOG_MYSQL_SQL',true);

}

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
//定义配置文件的应用目录
define('CONF_PATH', __DIR__ . '/../application/config/');
define("Web_url","http://ceshi.my");
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
