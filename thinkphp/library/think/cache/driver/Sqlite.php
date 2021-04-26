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

namespace think\cache\driver;

use think\cache\Driver;

/**
 * Sqlite cache driver
 * @author    liu21st <liu21st@gmail.com>
 */
class Sqlite extends Driver
{
    protected $options = [
        'db'         => ':memory:',
        'table'      => 'sharedmemory',
        'prefix'     => '',
        'expire'     => 0,
        'persistent' => false,
    ];

    /**
     * Constructor
     * @param array $options Cache parameter
     * @throws \BadFunctionCallException
     * @access public
     */
    public function __construct($options = [])
    {
        if (!extension_loaded('sqlite')) {
            throw new \BadFunctionCallException('not support: sqlite');
        }
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        $func          = $this->options['persistent'] ? 'sqlite_popen' : 'sqlite_open';
        $this->handler = $func($this->options['db']);
    }

    /**
     * Get the actual cache ID
     * @access public
     * @param string $name Cache name
     * @return string
     */
    protected function getCacheKey($name)
    {
        return $this->options['prefix'] . sqlite_escape_string($name);
    }

    /**
     * Judgment cache
     * @access public
     * @param string $name Cache variable name
     * @return bool
     */
    public function has($name)
    {
        $name   = $this->getCacheKey($name);
        $sql    = 'SELECT value FROM ' . $this->options['table'] . ' WHERE var=\'' . $name . '\' AND (expire=0 OR expire >' . $_SERVER['REQUEST_TIME'] . ') LIMIT 1';
        $result = sqlite_query($this->handler, $sql);
        return sqlite_num_rows($result);
    }

    /**
     * Read cache
     * @access public
     * @param string $name Cache variable name
     * @param mixed  $default Defaults
     * @return mixed
     */
    public function get($name, $default = false)
    {
        $name   = $this->getCacheKey($name);
        $sql    = 'SELECT value FROM ' . $this->options['table'] . ' WHERE var=\'' . $name . '\' AND (expire=0 OR expire >' . $_SERVER['REQUEST_TIME'] . ') LIMIT 1';
        $result = sqlite_query($this->handler, $sql);
        if (sqlite_num_rows($result)) {
            $content = sqlite_fetch_single($result);
            if (function_exists('gzcompress')) {
                //Enable data compression
                $content = gzuncompress($content);
            }
            return unserialize($content);
        }
        return $default;
    }

    /**
     * Write cache
     * @access public
     * @param string            $name Cache variable name
     * @param mixed             $value  Storing data
     * @param integer|\DateTime $expire  Effective time (seconds)
     * @return boolean
     */
    public function set($name, $value, $expire = null)
    {
        $name  = $this->getCacheKey($name);
        $value = sqlite_escape_string(serialize($value));
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        if ($expire instanceof \DateTime) {
            $expire = $expire->getTimestamp();
        } else {
            $expire = (0 == $expire) ? 0 : (time() + $expire); //A cache validity period of 0 means permanent cache
        }
        if (function_exists('gzcompress')) {
            //data compression
            $value = gzcompress($value, 3);
        }
        if ($this->tag) {
            $tag       = $this->tag;
            $this->tag = null;
        } else {
            $tag = '';
        }
        $sql = 'REPLACE INTO ' . $this->options['table'] . ' (var, value, expire, tag) VALUES (\'' . $name . '\', \'' . $value . '\', \'' . $expire . '\', \'' . $tag . '\')';
        if (sqlite_query($this->handler, $sql)) {
            return true;
        }
        return false;
    }

    /**
     * Self-incrementing cache (for numeric value cache)
     * @access public
     * @param string    $name Cache variable name
     * @param int       $step Step size
     * @return false|int
     */
    public function inc($name, $step = 1)
    {
        if ($this->has($name)) {
            $value = $this->get($name) + $step;
        } else {
            $value = $step;
        }
        return $this->set($name, $value, 0) ? $value : false;
    }

    /**
     * Self-decreasing cache (for numeric value cache)
     * @access public
     * @param string    $name Cache variable name
     * @param int       $step Step size
     * @return false|int
     */
    public function dec($name, $step = 1)
    {
        if ($this->has($name)) {
            $value = $this->get($name) - $step;
        } else {
            $value = -$step;
        }
        return $this->set($name, $value, 0) ? $value : false;
    }

    /**
     * Delete cache
     * @access public
     * @param string $name Cache variable name
     * @return boolean
     */
    public function rm($name)
    {
        $name = $this->getCacheKey($name);
        $sql  = 'DELETE FROM ' . $this->options['table'] . ' WHERE var=\'' . $name . '\'';
        sqlite_query($this->handler, $sql);
        return true;
    }

    /**
     * clear cache
     * @access public
     * @param string $tag Label name
     * @return boolean
     */
    public function clear($tag = null)
    {
        if ($tag) {
            $name = sqlite_escape_string($tag);
            $sql  = 'DELETE FROM ' . $this->options['table'] . ' WHERE tag=\'' . $name . '\'';
            sqlite_query($this->handler, $sql);
            return true;
        }
        $sql = 'DELETE FROM ' . $this->options['table'];
        sqlite_query($this->handler, $sql);
        return true;
    }
}
