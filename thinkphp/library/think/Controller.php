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

namespace think;

use think\exception\ValidateException;
use traits\controller\Jump;

Loader::import('controller/Jump', TRAIT_PATH, EXT);

class Controller
{
    use Jump;

    /**
     * @var \think\View View class instance
     */
    protected $view;

    /**
     * @var \think\Request Request Instance
     */
    protected $request;

    /**
     * @var bool Whether the verification fails to throw an exception
     */
    protected $failException = false;

    /**
     * @var bool Whether batch verification
     */
    protected $batchValidate = false;

    /**
     * @var array List of pre-operation methods
     */
    protected $beforeActionList = [];

    /**
     * Constructor
     * @access public
     * @param Request $request Request object
     */
    public function __construct(Request $request = null)
    {
        $this->view    = View::instance(Config::get('template'), Config::get('view_replace_str'));
        $this->request = is_null($request) ? Request::instance() : $request;

        // Controller initialization
        $this->_initialize();

        // Pre-operation method
        if ($this->beforeActionList) {
            foreach ($this->beforeActionList as $method => $options) {
                is_numeric($method) ?
                $this->beforeAction($options) :
                $this->beforeAction($method, $options);
            }
        }
    }

    /**
     * Initial operation
     * @access protected
     */
    protected function _initialize()
    {
    }

    /**
     * Pre-operation
     * @access protected
     * @param  string $method  Pre-operation method name
     * @param  array  $options Call parameters ['only'=>[...]] or ['except'=>[...]]
     * @return void
     */
    protected function beforeAction($method, $options = [])
    {
        if (isset($options['only'])) {
            if (is_string($options['only'])) {
                $options['only'] = explode(',', $options['only']);
            }

            if (!in_array($this->request->action(), $options['only'])) {
                return;
            }
        } elseif (isset($options['except'])) {
            if (is_string($options['except'])) {
                $options['except'] = explode(',', $options['except']);
            }

            if (in_array($this->request->action(), $options['except'])) {
                return;
            }
        }

        call_user_func([$this, $method]);
    }

    /**
     * Load template output
     * @access protected
     * @param  string $template Template file name
     * @param  array  $vars     Template output variables
     * @param  array  $replace  Template replacement
     * @param  array  $config   Template parameter
     * @return mixed
     */
    protected function fetch($template = '', $vars = [], $replace = [], $config = [])
    {
        return $this->view->fetch($template, $vars, $replace, $config);
    }

    /**
     * Render content output
     * @access protected
     * @param  string $content Template content
     * @param  array  $vars    Template output variables
     * @param  array  $replace Replace content
     * @param  array  $config  Template parameter
     * @return mixed
     */
    protected function display($content = '', $vars = [], $replace = [], $config = [])
    {
        return $this->view->display($content, $vars, $replace, $config);
    }

    /**
     * Template variable assignment
     * @access protected
     * @param  mixed $name  Template variable to display
     * @param  mixed $value Variable value
     * @return $this
     */
    protected function assign($name, $value = '')
    {
        $this->view->assign($name, $value);

        return $this;
    }

    /**
     * Initialize the template engine
     * @access protected
     * @param array|string $engine Engine parameters
     * @return $this
     */
    protected function engine($engine)
    {
        $this->view->engine($engine);

        return $this;
    }

    /**
     * Set whether to throw an exception after verification fails
     * @access protected
     * @param bool $fail Whether to throw an exception
     * @return $this
     */
    protected function validateFailException($fail = true)
    {
        $this->failException = $fail;

        return $this;
    }

    /**
     * verify the data
     * @access protected
     * @param  array        $data     data
     * @param  string|array $validate Validator name or validation rule array
     * @param  array        $message  Prompt information
     * @param  bool         $batch    Whether batch verification
     * @param  mixed        $callback Callback method (closure)
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate($data, $validate, $message = [], $batch = false, $callback = null)
    {
        if (is_array($validate)) {
            $v = Loader::validate();
            $v->rule($validate);
        } else {
            // Support scene
            if (strpos($validate, '.')) {
                list($validate, $scene) = explode('.', $validate);
            }

            $v = Loader::validate($validate);

            !empty($scene) && $v->scene($scene);
        }

        // Bulk verification
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        // Set error message
        if (is_array($message)) {
            $v->message($message);
        }

        // Use callback verification
        if ($callback && is_callable($callback)) {
            call_user_func_array($callback, [$v, &$data]);
        }

        if (!$v->check($data)) {
            if ($this->failException) {
                throw new ValidateException($v->getError());
            }

            return $v->getError();
        }

        return true;
    }
}
