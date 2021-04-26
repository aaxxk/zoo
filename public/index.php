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

// Define application catalog
define('APP_PATH', __DIR__ . '/../application/');
//Define the application directory of the configuration file
define('CONF_PATH', __DIR__ . '/../application/config/');
define("Web_url","http://ceshi.my");
// Load the framework boot file
require __DIR__ . '/../thinkphp/start.php';
