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

namespace think\controller;

use think\App;
use think\Request;
use think\Response;

abstract class Rest
{

    protected $method; // Current request type
    protected $type; // Current resource type
    // Output type
    protected $restMethodList    = 'get|post|put|delete';
    protected $restDefaultMethod = 'get';
    protected $restTypeList      = 'html|xml|json|rss';
    protected $restDefaultType   = 'html';
    protected $restOutputType    = [ // List of resource types allowed by REST
        'xml'  => 'application/xml',
        'json' => 'application/json',
        'html' => 'text/html',
    ];

    /**
     * Constructor Get the template object instance
     * @access public
     */
    public function __construct()
    {
        // Resource type detection
        $request = Request::instance();
        $ext     = $request->ext();
        if ('' == $ext) {
            // Automatically detect resource type
            $this->type = $request->type();
        } elseif (!preg_match('/(' . $this->restTypeList . ')$/i', $ext)) {
            // If the resource type is illegal, use the default resource type to access
            $this->type = $this->restDefaultType;
        } else {
            $this->type = $ext;
        }
        // Request method detection
        $method = strtolower($request->method());
        if (!preg_match('/(' . $this->restMethodList . ')$/i', $method)) {
            // If the request method is illegal, use the default request method
            $method = $this->restDefaultMethod;
        }
        $this->method = $method;
    }

    /**
     * REST transfer
     * @access public
     * @param string $method Method name
     * @return mixed
     * @throws \Exception
     */
    public function _empty($method)
    {
        if (method_exists($this, $method . '_' . $this->method . '_' . $this->type)) {
            // RESTFul method support
            $fun = $method . '_' . $this->method . '_' . $this->type;
        } elseif ($this->method == $this->restDefaultMethod && method_exists($this, $method . '_' . $this->type)) {
            $fun = $method . '_' . $this->type;
        } elseif ($this->type == $this->restDefaultType && method_exists($this, $method . '_' . $this->method)) {
            $fun = $method . '_' . $this->method;
        }
        if (isset($fun)) {
            return App::invokeMethod([$this, $fun]);
        } else {
            // Throw an exception
            throw new \Exception('error action :' . $method);
        }
    }

    /**
     * Output return data
     * @access protected
     * @param mixed     $data The data to be returned
     * @param String    $type Return type JSON XML
     * @param integer   $code HTTP status code
     * @return Response
     */
    protected function response($data, $type = 'json', $code = 200)
    {
        return Response::create($data, $type)->code($code);
    }

}
