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

use think\exception\ClassNotFoundException;

class Validate
{
    // Instance
    protected static $instance;

    // Custom authentication type
    protected static $type = [];

    // Authentication type alias
    protected $alias = [
        '>' => 'gt', '>=' => 'egt', '<' => 'lt', '<=' => 'elt', '=' => 'eq', 'same' => 'eq',
    ];

    // Currently validated rules
    protected $rule = [];

    // Verification message
    protected $message = [];
    // Verification field description
    protected $field = [];

    // Default prompt message for validation rules
    protected static $typeMsg = [
        'require'     => ':attribute require',
        'number'      => ':attribute must be numeric',
        'integer'     => ':attribute must be integer',
        'float'       => ':attribute must be float',
        'boolean'     => ':attribute must be bool',
        'email'       => ':attribute not a valid email address',
        'array'       => ':attribute must be a array',
        'accepted'    => ':attribute must be yes,on or 1',
        'date'        => ':attribute not a valid datetime',
        'file'        => ':attribute not a valid file',
        'image'       => ':attribute not a valid image',
        'alpha'       => ':attribute must be alpha',
        'alphaNum'    => ':attribute must be alpha-numeric',
        'alphaDash'   => ':attribute must be alpha-numeric, dash, underscore',
        'activeUrl'   => ':attribute not a valid domain or ip',
        'chs'         => ':attribute must be chinese',
        'chsAlpha'    => ':attribute must be chinese or alpha',
        'chsAlphaNum' => ':attribute must be chinese,alpha-numeric',
        'chsDash'     => ':attribute must be chinese,alpha-numeric,underscore, dash',
        'url'         => ':attribute not a valid url',
        'ip'          => ':attribute not a valid ip',
        'dateFormat'  => ':attribute must be dateFormat of :rule',
        'in'          => ':attribute must be in :rule',
        'notIn'       => ':attribute be notin :rule',
        'between'     => ':attribute must between :1 - :2',
        'notBetween'  => ':attribute not between :1 - :2',
        'length'      => 'size of :attribute must be :rule',
        'max'         => 'max size of :attribute must be :rule',
        'min'         => 'min size of :attribute must be :rule',
        'after'       => ':attribute cannot be less than :rule',
        'before'      => ':attribute cannot exceed :rule',
        'expire'      => ':attribute not within :rule',
        'allowIp'     => 'access IP is not allowed',
        'denyIp'      => 'access IP denied',
        'confirm'     => ':attribute out of accord with :2',
        'different'   => ':attribute cannot be same with :2',
        'egt'         => ':attribute must greater than or equal :rule',
        'gt'          => ':attribute must greater than :rule',
        'elt'         => ':attribute must less than or equal :rule',
        'lt'          => ':attribute must less than :rule',
        'eq'          => ':attribute must equal :rule',
        'unique'      => ':attribute has exists',
        'regex'       => ':attribute not conform to the rules',
        'method'      => 'invalid Request method',
        'token'       => 'invalid token',
        'fileSize'    => 'filesize not match',
        'fileExt'     => 'extensions to upload is not allowed',
        'fileMime'    => 'mimetype to upload is not allowed',
    ];

    // Current verification scenario
    protected $currentScene = null;

    // Regular expression regex = ['zip'=>'\d{6}',...]
    protected $regex = [];

    // Verify the scene scene = ['edit'=>'name1,name2,...']
    protected $scene = [];

    // Verification failure error message
    protected $error = [];

    // Bulk verification
    protected $batch = false;

    /**
     * Constructor
     * @access public
     * @param array $rules Validation rules
     * @param array $message Verification message
     * @param array $field Verification field description information
     */
    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->rule    = array_merge($this->rule, $rules);
        $this->message = array_merge($this->message, $message);
        $this->field   = array_merge($this->field, $field);
    }

    /**
     * Instantiation verification
     * @access public
     * @param array     $rules Validation rules
     * @param array     $message Verification message
     * @param array     $field Verification field description information
     * @return Validate
     */
    public static function make($rules = [], $message = [], $field = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($rules, $message, $field);
        }
        return self::$instance;
    }

    /**
     * Add field validation rules
     * @access protected
     * @param string|array  $name  Field name or rule array
     * @param mixed         $rule  Validation rules
     * @return Validate
     */
    public function rule($name, $rule = '')
    {
        if (is_array($name)) {
            $this->rule = array_merge($this->rule, $name);
        } else {
            $this->rule[$name] = $rule;
        }
        return $this;
    }

    /**
     * Registration verification (type) rules
     * @access public
     * @param string    $type  Validation rule type
     * @param mixed     $callback callback method (or closure)
     * @return void
     */
    public static function extend($type, $callback = null)
    {
        if (is_array($type)) {
            self::$type = array_merge(self::$type, $type);
        } else {
            self::$type[$type] = $callback;
        }
    }

    /**
     * Set the default prompt message for validation rules
     * @access protected
     * @param string|array  $type  Validation rule type name or array
     * @param string        $msg  Verification message
     * @return void
     */
    public static function setTypeMsg($type, $msg = null)
    {
        if (is_array($type)) {
            self::$typeMsg = array_merge(self::$typeMsg, $type);
        } else {
            self::$typeMsg[$type] = $msg;
        }
    }

    /**
     * Set reminder message
     * @access public
     * @param string|array  $name  Field Name
     * @param string        $message Prompt information
     * @return Validate
     */
    public function message($name, $message = '')
    {
        if (is_array($name)) {
            $this->message = array_merge($this->message, $name);
        } else {
            $this->message[$name] = $message;
        }
        return $this;
    }

    /**
     * Set up verification scenarios
     * @access public
     * @param string|array  $name  Scene name or scene setting array
     * @param mixed         $fields Field to be validated
     * @return Validate
     */
    public function scene($name, $fields = null)
    {
        if (is_array($name)) {
            $this->scene = array_merge($this->scene, $name);
        }if (is_null($fields)) {
            // Set the current scene
            $this->currentScene = $name;
        } else {
            // Set up verification scenarios
            $this->scene[$name] = $fields;
        }
        return $this;
    }

    /**
     * Determine whether there is a verification scenario
     * @access public
     * @param string $name Scene name
     * @return bool
     */
    public function hasScene($name)
    {
        return isset($this->scene[$name]);
    }

    /**
     * Set up bulk verification
     * @access public
     * @param bool $batch  Whether batch verification
     * @return Validate
     */
    public function batch($batch = true)
    {
        $this->batch = $batch;
        return $this;
    }

    /**
     * Automatic data verification
     * @access public
     * @param array     $data  data
     * @param mixed     $rules  Validation rules
     * @param string    $scene Verification scenario
     * @return bool
     */
    public function check($data, $rules = [], $scene = '')
    {
        $this->error = [];

        if (empty($rules)) {
            // Read validation rules
            $rules = $this->rule;
        }

        // Analysis and validation rules
        $scene = $this->getScene($scene);
        if (is_array($scene)) {
            // Processing scene validation field
            $change = [];
            $array  = [];
            foreach ($scene as $k => $val) {
                if (is_numeric($k)) {
                    $array[] = $val;
                } else {
                    $array[]    = $k;
                    $change[$k] = $val;
                }
            }
        }

        foreach ($rules as $key => $item) {
            // field => rule1|rule2... field=>['rule1','rule2',...]
            if (is_numeric($key)) {
                // [field,rule1|rule2,msg1|msg2]
                $key  = $item[0];
                $rule = $item[1];
                if (isset($item[2])) {
                    $msg = is_string($item[2]) ? explode('|', $item[2]) : $item[2];
                } else {
                    $msg = [];
                }
            } else {
                $rule = $item;
                $msg  = [];
            }
            if (strpos($key, '|')) {
                // Field|Description Used to specify the attribute name
                list($key, $title) = explode('|', $key);
            } else {
                $title = isset($this->field[$key]) ? $this->field[$key] : $key;
            }

            // Scene detection
            if (!empty($scene)) {
                if ($scene instanceof \Closure && !call_user_func_array($scene, [$key, $data])) {
                    continue;
                } elseif (is_array($scene)) {
                    if (!in_array($key, $array)) {
                        continue;
                    } elseif (isset($change[$key])) {
                        // Overload a validation rule
                        $rule = $change[$key];
                    }
                }
            }

            // Get data Support two-dimensional array
            $value = $this->getDataValue($data, $key);

            // Field validation
            if ($rule instanceof \Closure) {
                // Anonymous function verification supports passing in two data, the current field and all fields
                $result = call_user_func_array($rule, [$value, $data]);
            } else {
                $result = $this->checkItem($key, $value, $rule, $data, $title, $msg);
            }

            if (true !== $result) {
                // If it doesn't return true, it means the verification failed
                if (!empty($this->batch)) {
                    // Bulk verification
                    if (is_array($result)) {
                        $this->error = array_merge($this->error, $result);
                    } else {
                        $this->error[$key] = $result;
                    }
                } else {
                    $this->error = $result;
                    return false;
                }
            }
        }
        return !empty($this->error) ? false : true;
    }

    /**
     * Validate data according to validation rules
     * @access protected
     * @param  mixed     $value Field value
     * @param  mixed     $rules Validation rules
     * @return bool
     */
    protected function checkRule($value, $rules)
    {
        if ($rules instanceof \Closure) {
            return call_user_func_array($rules, [$value]);
        } elseif (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        foreach ($rules as $key => $rule) {
            if ($rule instanceof \Closure) {
                $result = call_user_func_array($rule, [$value]);
            } else {
                // Determine the verification type
                list($type, $rule) = $this->getValidateType($key, $rule);

                $callback = isset(self::$type[$type]) ? self::$type[$type] : [$this, $type];

                $result = call_user_func_array($callback, [$value, $rule]);
            }

            if (true !== $result) {
                return $result;
            }
        }

        return true;
    }

    /**
     * Validate single field rules
     * @access protected
     * @param string    $field  Field name
     * @param mixed     $value  Field value
     * @param mixed     $rules  Validation rules
     * @param array     $data  data
     * @param string    $title  Field description
     * @param array     $msg  Prompt information
     * @return mixed
     */
    protected function checkItem($field, $value, $rules, $data, $title = '', $msg = [])
    {
        // Support multi-rule verification require|in:a,b,c|... or ['require','in'=>'a,b,c',...]
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }
        $i = 0;
        foreach ($rules as $key => $rule) {
            if ($rule instanceof \Closure) {
                $result = call_user_func_array($rule, [$value, $data]);
                $info   = is_numeric($key) ? '' : $key;
            } else {
                // Support multi-rule verification
                list($type, $rule, $info) = $this->getValidateType($key, $rule);

                // If it is not require, it will be validated if there is data
                if (0 === strpos($info, 'require') || (!is_null($value) && '' !== $value)) {
                    // Verification type
                    $callback = isset(self::$type[$type]) ? self::$type[$type] : [$this, $type];
                    // verify the data
                    $result = call_user_func_array($callback, [$value, $rule, $data, $field, $title]);
                } else {
                    $result = true;
                }
            }

            if (false === $result) {
                // Verification failed, error message returned
                if (isset($msg[$i])) {
                    $message = $msg[$i];
                    if (is_string($message) && strpos($message, '{%') === 0) {
                        $message = Lang::get(substr($message, 2, -1));
                    }
                } else {
                    $message = $this->getRuleMsg($field, $title, $info, $rule);
                }
                return $message;
            } elseif (true !== $result) {
                // Return custom error message
                if (is_string($result) && false !== strpos($result, ':')) {
                    $result = str_replace([':attribute', ':rule'], [$title, (string) $rule], $result);
                }
                return $result;
            }
            $i++;
        }
        return $result;
    }

    /**
     * Get the current verification type and rules
     * @access public
     * @param  mixed     $key
     * @param  mixed     $rule
     * @return array
     */
    protected function getValidateType($key, $rule)
    {
        // Determine the verification type
        if (!is_numeric($key)) {
            return [$key, $rule, $key];
        }

        if (strpos($rule, ':')) {
            list($type, $rule) = explode(':', $rule, 2);
            if (isset($this->alias[$type])) {
                // Judge alias
                $type = $this->alias[$type];
            }
            $info = $type;
        } elseif (method_exists($this, $rule)) {
            $type = $rule;
            $info = $rule;
            $rule = '';
        } else {
            $type = 'is';
            $info = $rule;
        }

        return [$type, $rule, $info];
    }

    /**
     * Verify that it is consistent with the value of a field
     * @access protected
     * @param mixed     $value Field value
     * @param mixed     $rule  Validation rules
     * @param array     $data  data
     * @param string    $field Field name
     * @return bool
     */
    protected function confirm($value, $rule, $data, $field = '')
    {
        if ('' == $rule) {
            if (strpos($field, '_confirm')) {
                $rule = strstr($field, '_confirm', true);
            } else {
                $rule = $field . '_confirm';
            }
        }
        return $this->getDataValue($data, $rule) === $value;
    }

    /**
     * Verify whether it is different from the value of a field
     * @access protected
     * @param mixed $value Field value
     * @param mixed $rule  Validation rules
     * @param array $data  data
     * @return bool
     */
    protected function different($value, $rule, $data)
    {
        return $this->getDataValue($data, $rule) != $value;
    }

    /**
     * Verify that it is greater than or equal to a certain value
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @param array     $data  data
     * @return bool
     */
    protected function egt($value, $rule, $data)
    {
        $val = $this->getDataValue($data, $rule);
        return !is_null($val) && $value >= $val;
    }

    /**
     * Verify that it is greater than a certain value
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @param array     $data  data
     * @return bool
     */
    protected function gt($value, $rule, $data)
    {
        $val = $this->getDataValue($data, $rule);
        return !is_null($val) && $value > $val;
    }

    /**
     * Verify that it is less than or equal to a certain value
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @param array     $data  data
     * @return bool
     */
    protected function elt($value, $rule, $data)
    {
        $val = $this->getDataValue($data, $rule);
        return !is_null($val) && $value <= $val;
    }

    /**
     * Verify that it is less than a certain value
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @param array     $data  data
     * @return bool
     */
    protected function lt($value, $rule, $data)
    {
        $val = $this->getDataValue($data, $rule);
        return !is_null($val) && $value < $val;
    }

    /**
     * Verify that it is equal to a certain value
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function eq($value, $rule)
    {
        return $value == $rule;
    }

    /**
     * Verify that the field value is in a valid format
     * @access protected
     * @param mixed     $value  Field value
     * @param string    $rule  Validation rules
     * @param array     $data  verify the data
     * @return bool
     */
    protected function is($value, $rule, $data = [])
    {
        switch ($rule) {
            case 'require':
                $result = !empty($value) || '0' == $value;
                break;
            case 'accepted':
                // accept
                $result = in_array($value, ['1', 'on', 'yes']);
                break;
            case 'date':
                // Is it a valid date
                $result = false !== strtotime($value);
                break;
            case 'alpha':
                // Only allow letters
                $result = $this->regex($value, '/^[A-Za-z]+$/');
                break;
            case 'alphaNum':
                // Only allow letters and numbers
                $result = $this->regex($value, '/^[A-Za-z0-9]+$/');
                break;
            case 'alphaDash':
                // Only letters, numbers and underscores are allowed
                $result = $this->regex($value, '/^[A-Za-z0-9\-\_]+$/');
                break;
            case 'chs':
                // Only allow Chinese characters
                $result = $this->regex($value, '/^[\x{4e00}-\x{9fa5}]+$/u');
                break;
            case 'chsAlpha':
                // Only Chinese characters and letters are allowed
                $result = $this->regex($value, '/^[\x{4e00}-\x{9fa5}a-zA-Z]+$/u');
                break;
            case 'chsAlphaNum':
                // Only Chinese characters, letters and numbers are allowed
                $result = $this->regex($value, '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/u');
                break;
            case 'chsDash':
                // Only Chinese characters, letters, numbers and underscores are allowed_ and dashes-
                $result = $this->regex($value, '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_\-]+$/u');
                break;
            case 'activeUrl':
                //a valid URL
                $result = checkdnsrr($value);
                break;
            case 'ip':
                //an IP address
                $result = $this->filter($value, [FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6]);
                break;
            case 'url':
                //URL address
                $result = $this->filter($value, FILTER_VALIDATE_URL);
                break;
            case 'float':
                //float
                $result = $this->filter($value, FILTER_VALIDATE_FLOAT);
                break;
            case 'number':
                $result = is_numeric($value);
                break;
            case 'integer':
                //integer
                $result = $this->filter($value, FILTER_VALIDATE_INT);
                break;
            case 'email':
                //an email address
                $result = $this->filter($value, FILTER_VALIDATE_EMAIL);
                break;
            case 'boolean':
                // Boolean
                $result = in_array($value, [true, false, 0, 1, '0', '1'], true);
                break;
            case 'array':
                // array
                $result = is_array($value);
                break;
            case 'file':
                $result = $value instanceof File;
                break;
            case 'image':
                $result = $value instanceof File && in_array($this->getImageType($value->getRealPath()), [1, 2, 3, 6]);
                break;
            case 'token':
                $result = $this->token($value, '__token__', $data);
                break;
            default:
                if (isset(self::$type[$rule])) {
                    // Validation rules for registration
                    $result = call_user_func_array(self::$type[$rule], [$value]);
                } else {
                    // Regular verification
                    $result = $this->regex($value, $rule);
                }
        }
        return $result;
    }

    // Determine the image type
    protected function getImageType($image)
    {
        if (function_exists('exif_imagetype')) {
            return exif_imagetype($image);
        } else {
            try {
                $info = getimagesize($image);
                return $info ? $info[2] : false;
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * Verify whether it is a qualified domain name or IP. Support A, MX, NS, SOA, PTR, CNAME, AAAA, A6, SRV, NAPTR, TXT or ANY type
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function activeUrl($value, $rule)
    {
        if (!in_array($rule, ['A', 'MX', 'NS', 'SOA', 'PTR', 'CNAME', 'AAAA', 'A6', 'SRV', 'NAPTR', 'TXT', 'ANY'])) {
            $rule = 'MX';
        }
        return checkdnsrr($value, $rule);
    }

    /**
     * Verify that the IP is valid
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules ipv4 ipv6
     * @return bool
     */
    protected function ip($value, $rule)
    {
        if (!in_array($rule, ['ipv4', 'ipv6'])) {
            $rule = 'ipv4';
        }
        return $this->filter($value, [FILTER_VALIDATE_IP, 'ipv6' == $rule ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4]);
    }

    /**
     * Verify upload file suffix
     * @access protected
     * @param mixed     $file  upload files
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function fileExt($file, $rule)
    {
        if (is_array($file)) {
            foreach ($file as $item) {
                if (!($item instanceof File) || !$item->checkExt($rule)) {
                    return false;
                }
            }
            return true;
        } elseif ($file instanceof File) {
            return $file->checkExt($rule);
        } else {
            return false;
        }
    }

    /**
     * Verify upload file type
     * @access protected
     * @param mixed     $file  upload files
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function fileMime($file, $rule)
    {
        if (is_array($file)) {
            foreach ($file as $item) {
                if (!($item instanceof File) || !$item->checkMime($rule)) {
                    return false;
                }
            }
            return true;
        } elseif ($file instanceof File) {
            return $file->checkMime($rule);
        } else {
            return false;
        }
    }

    /**
     * Verify upload file size
     * @access protected
     * @param mixed     $file  upload files
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function fileSize($file, $rule)
    {
        if (is_array($file)) {
            foreach ($file as $item) {
                if (!($item instanceof File) || !$item->checkSize($rule)) {
                    return false;
                }
            }
            return true;
        } elseif ($file instanceof File) {
            return $file->checkSize($rule);
        } else {
            return false;
        }
    }

    /**
     * Verify the width, height and type of the picture
     * @access protected
     * @param mixed     $file  upload files
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function image($file, $rule)
    {
        if (!($file instanceof File)) {
            return false;
        }
        if ($rule) {
            $rule                        = explode(',', $rule);
            list($width, $height, $type) = getimagesize($file->getRealPath());
            if (isset($rule[2])) {
                $imageType = strtolower($rule[2]);
                if ('jpeg' == $imageType) {
                    $imageType = 'jpg';
                }
                if (image_type_to_extension($type, false) != $imageType) {
                    return false;
                }
            }

            list($w, $h) = $rule;
            return $w == $width && $h == $height;
        } else {
            return in_array($this->getImageType($file->getRealPath()), [1, 2, 3, 6]);
        }
    }

    /**
     * Verification request type
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function method($value, $rule)
    {
        $method = Request::instance()->method();
        return strtoupper($rule) == $method;
    }

    /**
     * Verify that the time and date conform to the specified format
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function dateFormat($value, $rule)
    {
        $info = date_parse_from_format($rule, $value);
        return 0 == $info['warning_count'] && 0 == $info['error_count'];
    }

    /**
     * Verify that it is unique
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules Format: data table, field name, exclude ID, primary key name
     * @param array     $data  data
     * @param string    $field  Verify field name
     * @return bool
     */
    protected function unique($value, $rule, $data, $field)
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        if (false !== strpos($rule[0], '\\')) {
            // Specify the model class
            $db = new $rule[0];
        } else {
            try {
                $db = Loader::model($rule[0]);
            } catch (ClassNotFoundException $e) {
                $db = Db::name($rule[0]);
            }
        }
        $key = isset($rule[1]) ? $rule[1] : $field;

        if (strpos($key, '^')) {
            // Support multiple field validation
            $fields = explode('^', $key);
            foreach ($fields as $key) {
                $map[$key] = $data[$key];
            }
        } elseif (strpos($key, '=')) {
            parse_str($key, $map);
        } else {
            $map[$key] = $data[$field];
        }

        $pk = isset($rule[3]) ? $rule[3] : $db->getPk();
        if (is_string($pk)) {
            if (isset($rule[2])) {
                $map[$pk] = ['neq', $rule[2]];
            } elseif (isset($data[$pk])) {
                $map[$pk] = ['neq', $data[$pk]];
            }
        }
        if ($db->where($map)->field($pk)->find()) {
            return false;
        }
        return true;
    }

    /**
     * Use behavioral verification
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @param array     $data  data
     * @return mixed
     */
    protected function behavior($value, $rule, $data)
    {
        return Hook::exec($rule, '', $data);
    }

    /**
     * Use filter_var to verify
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function filter($value, $rule)
    {
        if (is_string($rule) && strpos($rule, ',')) {
            list($rule, $param) = explode(',', $rule);
        } elseif (is_array($rule)) {
            $param = isset($rule[1]) ? $rule[1] : null;
            $rule  = $rule[0];
        } else {
            $param = null;
        }
        return false !== filter_var($value, is_int($rule) ? $rule : filter_id($rule), $param);
    }

    /**
     * Must be when verifying that a field is equal to a certain value
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @param array     $data  data
     * @return bool
     */
    protected function requireIf($value, $rule, $data)
    {
        list($field, $val) = explode(',', $rule);
        if ($this->getDataValue($data, $field) == $val) {
            return !empty($value) || '0' == $value;
        } else {
            return true;
        }
    }

    /**
     * Verify whether a field is required through the callback method
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @param array     $data  data
     * @return bool
     */
    protected function requireCallback($value, $rule, $data)
    {
        $result = call_user_func_array($rule, [$value, $data]);
        if ($result) {
            return !empty($value) || '0' == $value;
        } else {
            return true;
        }
    }

    /**
     * Required to verify that a field has a value
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @param array     $data  data
     * @return bool
     */
    protected function requireWith($value, $rule, $data)
    {
        $val = $this->getDataValue($data, $rule);
        if (!empty($val)) {
            return !empty($value) || '0' == $value;
        } else {
            return true;
        }
    }

    /**
     * Verify that it is within range
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function in($value, $rule)
    {
        return in_array($value, is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * Verify that it is not in a certain range
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function notIn($value, $rule)
    {
        return !in_array($value, is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * between verification data
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function between($value, $rule)
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        list($min, $max) = $rule;
        return $value >= $min && $value <= $max;
    }

    /**
     * Use notbetween to verify data
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function notBetween($value, $rule)
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        list($min, $max) = $rule;
        return $value < $min || $value > $max;
    }

    /**
     * Verify data length
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function length($value, $rule)
    {
        if (is_array($value)) {
            $length = count($value);
        } elseif ($value instanceof File) {
            $length = $value->getSize();
        } else {
            $length = mb_strlen((string) $value);
        }

        if (strpos($rule, ',')) {
            // Length interval
            list($min, $max) = explode(',', $rule);
            return $length >= $min && $length <= $max;
        } else {
            // Specify length
            return $length == $rule;
        }
    }

    /**
     * Maximum length of verification data
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function max($value, $rule)
    {
        if (is_array($value)) {
            $length = count($value);
        } elseif ($value instanceof File) {
            $length = $value->getSize();
        } else {
            $length = mb_strlen((string) $value);
        }
        return $length <= $rule;
    }

    /**
     * Minimum length of verification data
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function min($value, $rule)
    {
        if (is_array($value)) {
            $length = count($value);
        } elseif ($value instanceof File) {
            $length = $value->getSize();
        } else {
            $length = mb_strlen((string) $value);
        }
        return $length >= $rule;
    }

    /**
     * Verification date
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function after($value, $rule)
    {
        return strtotime($value) >= strtotime($rule);
    }

    /**
     * Verification date
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function before($value, $rule)
    {
        return strtotime($value) <= strtotime($rule);
    }

    /**
     * Verification validity period
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @return bool
     */
    protected function expire($value, $rule)
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        list($start, $end) = $rule;
        if (!is_numeric($start)) {
            $start = strtotime($start);
        }

        if (!is_numeric($end)) {
            $end = strtotime($end);
        }
        return $_SERVER['REQUEST_TIME'] >= $start && $_SERVER['REQUEST_TIME'] <= $end;
    }

    /**
     * Verify IP permission
     * @access protected
     * @param string    $value  Field value
     * @param mixed     $rule  Validation rules
     * @return mixed
     */
    protected function allowIp($value, $rule)
    {
        return in_array($_SERVER['REMOTE_ADDR'], is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * Verify IP is disabled
     * @access protected
     * @param string    $value  Field value
     * @param mixed     $rule  Validation rules
     * @return mixed
     */
    protected function denyIp($value, $rule)
    {
        return !in_array($_SERVER['REMOTE_ADDR'], is_array($rule) ? $rule : explode(',', $rule));
    }

    /**
     * Use regular verification data
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules Regular rules or predefined regular names
     * @return mixed
     */
    protected function regex($value, $rule)
    {
        if (isset($this->regex[$rule])) {
            $rule = $this->regex[$rule];
        }
        if (0 !== strpos($rule, '/') && !preg_match('/\/[imsU]{0,4}$/', $rule)) {
            $rule = '/^' . $rule . '$/';
        }
        return is_scalar($value) && 1 === preg_match($rule, (string) $value);
    }

    /**
     * Validate form token
     * @access protected
     * @param mixed     $value  Field value
     * @param mixed     $rule  Validation rules
     * @param array     $data  data
     * @return bool
     */
    protected function token($value, $rule, $data)
    {
        $rule = !empty($rule) ? $rule : '__token__';
        if (!isset($data[$rule]) || !Session::has($rule)) {
            // Invalid token data
            return false;
        }

        // Token verification
        if (isset($data[$rule]) && Session::get($rule) === $data[$rule]) {
            // Prevent duplicate submissions
            Session::delete($rule); // Verify that the session is destroyed
            return true;
        }
        // Turn on TOKEN reset
        Session::delete($rule);
        return false;
    }

    // Get error information
    public function getError()
    {
        return $this->error;
    }

    /**
     * Get data value
     * @access protected
     * @param array $data data
     * @param string $key Data identification support two-dimensional
     * @return mixed
     */
    protected function getDataValue($data, $key)
    {
        if (is_numeric($key)) {
            $value = $key;
        } elseif (strpos($key, '.')) {
            // Support two-dimensional array verification
            list($name1, $name2) = explode('.', $key);
            $value               = isset($data[$name1][$name2]) ? $data[$name1][$name2] : null;
        } else {
            $value = isset($data[$key]) ? $data[$key] : null;
        }
        return $value;
    }

    /**
     * Get the error message of the validation rule
     * @access protected
     * @param string    $attribute  Field English name
     * @param string    $title  Field description name
     * @param string    $type  Validation rule name
     * @param mixed     $rule  Validation rule data
     * @return string
     */
    protected function getRuleMsg($attribute, $title, $type, $rule)
    {
        if (isset($this->message[$attribute . '.' . $type])) {
            $msg = $this->message[$attribute . '.' . $type];
        } elseif (isset($this->message[$attribute][$type])) {
            $msg = $this->message[$attribute][$type];
        } elseif (isset($this->message[$attribute])) {
            $msg = $this->message[$attribute];
        } elseif (isset(self::$typeMsg[$type])) {
            $msg = self::$typeMsg[$type];
        } elseif (0 === strpos($type, 'require')) {
            $msg = self::$typeMsg['require'];
        } else {
            $msg = $title . Lang::get('not conform to the rules');
        }

        if (is_string($msg) && 0 === strpos($msg, '{%')) {
            $msg = Lang::get(substr($msg, 2, -1));
        } elseif (Lang::has($msg)) {
            $msg = Lang::get($msg);
        }

        if (is_string($msg) && is_scalar($rule) && false !== strpos($msg, ':')) {
            // Variable substitution
            if (is_string($rule) && strpos($rule, ',')) {
                $array = array_pad(explode(',', $rule), 3, '');
            } else {
                $array = array_pad([], 3, '');
            }
            $msg = str_replace(
                [':attribute', ':rule', ':1', ':2', ':3'],
                [$title, (string) $rule, $array[0], $array[1], $array[2]],
                $msg);
        }
        return $msg;
    }

    /**
     * Scenarios for obtaining data verification
     * @access protected
     * @param string $scene  Verification scenario
     * @return array
     */
    protected function getScene($scene = '')
    {
        if (empty($scene)) {
            // Read the specified scene
            $scene = $this->currentScene;
        }

        if (!empty($scene) && isset($this->scene[$scene])) {
            // If the verification applicable scenario is set
            $scene = $this->scene[$scene];
            if (is_string($scene)) {
                $scene = explode(',', $scene);
            }
        } else {
            $scene = [];
        }
        return $scene;
    }

    public static function __callStatic($method, $params)
    {
        $class = self::make();
        if (method_exists($class, $method)) {
            return call_user_func_array([$class, $method], $params);
        } else {
            throw new \BadMethodCallException('method not exists:' . __CLASS__ . '->' . $method);
        }
    }
}
