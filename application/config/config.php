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
define('Static_Url','https://cs2410-web01pvm.aston.ac.uk');
return [
    // +----------------------------------------------------------------------
    // | Application settings
    // +----------------------------------------------------------------------

    // Application debugging mode
    'app_debug'              => true,
    // Application Trace
    'app_trace'              => false,
    // Application mode status
    'app_status'             => '',
    // Whether to support multiple modules
    'app_multi_module'       => true,
    // Entrance automatic binding module
    'auto_bind_module'       => false,
    // Registered root namespace
    'root_namespace'         => [],
    // Extended function file
    'extra_file_list'        => [THINK_PATH . 'helper' . EXT],
    // Default output type
    'default_return_type'    => 'html',
    // Default AJAX data return format, optional json xml...
    'default_ajax_return'    => 'json',
    // The processing method returned by the default JSONP format
    'default_jsonp_handler'  => 'jsonpReturn',
    // Default JSONP processing method
    'var_jsonp_handler'      => 'callback',
    // Default time zone
    'default_timezone'       => 'PRC',
    // Whether to open multilingual
    'lang_switch_on'         => false,
    // The default global filtering method Separate multiple with commas
    'default_filter'         => 'stripslashes',
    // default language
    'default_lang'           => 'en',
    // Application library suffix
    'class_suffix'           => false,
    // Controller class suffix
    'controller_suffix'      => false,

    // +----------------------------------------------------------------------
    // | Module settings
    // +----------------------------------------------------------------------

    // Default module name
    'default_module'         => 'index',
    // Block access to modules
    'deny_module_list'       => ['common'],
    // Default controller name
    'default_controller'     => 'Index',
    // Default operation name
    'default_action'         => 'index',
    // Default validator
    'default_validate'       => '',
    // Default empty controller name
    'empty_controller'       => 'Error',
    // Operation method suffix
    'action_suffix'          => '',
    // Automatic search controller
    'controller_auto_search' => false,

    // +----------------------------------------------------------------------
    // | URL settings
    // +----------------------------------------------------------------------

    // PATHINFO variable name for compatibility mode
    'var_pathinfo'           => 's',
    // Compatible with PATH_INFO to obtain
    'pathinfo_fetch'         => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo separator
    'pathinfo_depr'          => '/',
    // URL pseudo-static suffix
    'url_html_suffix'        => '',
    // URL common mode parameters for automatic generation
    'url_common_param'       => false,
    // URL parameter method 0 parsed in pairs by name 1 parsed in order
    'url_param_type'         => 0,
    // Whether to enable routing
    'url_route_on'           => true,
    // Route use full match
    'route_complete_match'   => true,
    // Routing configuration file (multiple configurations are supported)
    'route_config_file'      => ['route'],
    // Whether to force the use of routing
    'url_route_must'         => false,
    // Domain deployment
    'url_domain_deploy'      => false,
    // Domain root, such as thinkphp.cn
    'url_domain_root'        => '',
    // Whether to automatically convert the controller and operation name in the URL
    'url_convert'            => true,
    // Default access controller layer
    'url_controller_layer'   => 'controller',
    // Form request type camouflage variable
    'var_method'             => '_method',
    // Form ajax camouflage variable
    'var_ajax'               => '_ajax',
    // Form pjax camouflage variables
    'var_pjax'               => '_pjax',
    // Whether to enable request caching true automatic caching Support setting request caching rules
    'request_cache'          => false,
    // Request cache validity period
    'request_cache_expire'   => null,
    // Global request cache exclusion rules
    'request_cache_except'   => [],

    // +----------------------------------------------------------------------
    // | Template settings
    // +----------------------------------------------------------------------

    'template'               => [
        // Template engine type support php think support extension
        'type'         => 'Think',
        // Template path
        'view_path'    => '',
        // Template suffix
        'view_suffix'  => 'html',
        // Template file name separator
        'view_depr'    => DS,
        // Template engine normal tag start tag
        'tpl_begin'    => '{',
        // Template engine normal tag end tag
        'tpl_end'      => '}',
        // Tag library tag start tag
        'taglib_begin' => '{',
        // Tag library tag end tag
        'taglib_end'   => '}',
    ],

    // View output string content replacement
    'view_replace_str'       => [],
    // The template file corresponding to the default jump page
    'dispatch_success_tmpl'  => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',
    'dispatch_error_tmpl'    => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',

    // +----------------------------------------------------------------------
    // | Exceptions and error settings
    // +----------------------------------------------------------------------

    // Template file for exception page
    'exception_tmpl'         => THINK_PATH . 'tpl' . DS . 'think_exception.tpl',

    // Error display information, valid in non-debug mode
    'error_message'          => 'Page error! Please try again later~',
    // Show error message
    'show_error_msg'         => false,
    // Exception handling handle class Leave blank to use \think\exception\Handle
    'exception_handle'       => '',

    // +----------------------------------------------------------------------
    // | Log settings
    // +----------------------------------------------------------------------

    'log'                    => [
        // Logging mode, built-in file socket supports extension
        'type'  => 'File',
        // Log save directory
        'path'  => LOG_PATH,
        // Logging level
        'level' => [],
    ],

    // +----------------------------------------------------------------------
    // | Trace setting is valid after opening app_trace
    // +----------------------------------------------------------------------
    'trace'                  => [
        // Built-in Html Console supports extension
        'type' => 'Html',
    ],

    // +----------------------------------------------------------------------
    // | Cache settings
    // +----------------------------------------------------------------------

//    'cache'                  => [
//        // Drive way
//        'type'   => 'File',
//        // Cache save directory
//        'path'   => CACHE_PATH,
//        // Cache prefix
//        'prefix' => '',
//        // Cache validity period 0 means permanent cache
//        'expire' => 0,
//    ],
    'redis'                  => [
        // Drive method
        'type'   => 'redis',
        // Cache save directory
        'host'   => '127.0.0.1',
        // Cache prefix
        'port' => '6379',
        // Cache validity period 0 means permanent cache
        'password' => '',
        'timeout'=>3600
    ],

    // +----------------------------------------------------------------------
    // | Session settings
    // +----------------------------------------------------------------------

    'session'                => [
        'id'             => '',
        // The submission variable of SESSION_ID, solve flash upload cross-domain
        'var_session_id' => '',
        // SESSION prefix
        'prefix'         => 'think',
        // Drive mode support redis memcache memcached
        'type'           => '',
        // Whether to automatically open SESSION
        'auto_start'     => true,
    ],

    // +----------------------------------------------------------------------
    // | Cookie settings
    // +----------------------------------------------------------------------
    'cookie'                 => [
        // cookie Name prefix
        'prefix'    => '',
        // cookie save time
        'expire'    => 0,
        // cookie save route
        'path'      => '/',
        // cookie Valid domain name
        'domain'    => '',
        //  cookie Enable secure transmission
        'secure'    => false,
        // httponly setting
        'httponly'  => '',
        // use setcookie or not
        'setcookie' => true,
    ],

    //Paging configuration
    'paginate'               => [
        'type'      => 'bootstrap',
        'var_page'  => 'page',
        'list_rows' => 15,
    ],
];
