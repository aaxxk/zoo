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

namespace think\cache;

/**
 * Cache basic class
 */
abstract class Driver
{
    protected $handler = null;
    protected $options = [];
    protected $tag;

    /**
     * Determine whether the cache exists
     * @access public
     * @param string $name Cache variable name
     * @return bool
     */
    abstract public function has($name);

    /**
     * Read cache
     * @access public
     * @param string $name Cache variable name
     * @param mixed  $default Defaults
     * @return mixed
     */
    abstract public function get($name, $default = false);

    /**
     * Write cache
     * @access public
     * @param string    $name Cache variable name
     * @param mixed     $value  Storing data
     * @param int       $expire  Effective time 0 is permanent
     * @return boolean
     */
    abstract public function set($name, $value, $expire = null);

    /**
     * Self-incrementing cache (for numeric value cache)
     * @access public
     * @param string    $name Cache variable name
     * @param int       $step Step size
     * @return false|int
     */
    abstract public function inc($name, $step = 1);

    /**
     * Self-decreasing cache (for numeric value cache)
     * @access public
     * @param string    $name Cache variable name
     * @param int       $step Step size
     * @return false|int
     */
    abstract public function dec($name, $step = 1);

    /**
     * Delete cache
     * @access public
     * @param string $name Cache variable name
     * @return boolean
     */
    abstract public function rm($name);

    /**
     * clear cache
     * @access public
     * @param string $tag Label name
     * @return boolean
     */
    abstract public function clear($tag = null);

    /**
     * Get the actual cache ID
     * @access public
     * @param string $name Cache name
     * @return string
     */
    protected function getCacheKey($name)
    {
        return $this->options['prefix'] . $name;
    }

    /**
     * Read cache and delete
     * @access public
     * @param string $name Cache variable name
     * @return mixed
     */
    public function pull($name)
    {
        $result = $this->get($name, false);
        if ($result) {
            $this->rm($name);
            return $result;
        } else {
            return;
        }
    }

    /**
     * If it does not exist, write to the cache
     * @access public
     * @param string    $name Cache variable name
     * @param mixed     $value  Storing data
     * @param int       $expire  Effective time 0 is permanent
     * @return mixed
     */
    public function remember($name, $value, $expire = null)
    {
        if (!$this->has($name)) {
            $time = time();
            while ($time + 5 > time() && $this->has($name . '_lock')) {
                // Wait if there is a lock
                usleep(200000);
            }

            try {
                // locking
                $this->set($name . '_lock', true);
                if ($value instanceof \Closure) {
                    $value = call_user_func($value);
                }
                $this->set($name, $value, $expire);
                // Unlock
                $this->rm($name . '_lock');
            } catch (\Exception $e) {
                // Unlock
                $this->rm($name . '_lock');
                throw $e;
            } catch (\throwable $e) {
                $this->rm($name . '_lock');
                throw $e;
            }
        } else {
            $value = $this->get($name);
        }
        return $value;
    }

    /**
     * Cache tag
     * @access public
     * @param string        $name Label name
     * @param string|array  $keys Cache ID
     * @param bool          $overlay Whether to cover
     * @return $this
     */
    public function tag($name, $keys = null, $overlay = false)
    {
        if (is_null($name)) {

        } elseif (is_null($keys)) {
            $this->tag = $name;
        } else {
            $key = 'tag_' . md5($name);
            if (is_string($keys)) {
                $keys = explode(',', $keys);
            }
            $keys = array_map([$this, 'getCacheKey'], $keys);
            if ($overlay) {
                $value = $keys;
            } else {
                $value = array_unique(array_merge($this->getTagItem($name), $keys));
            }
            $this->set($key, implode(',', $value), 0);
        }
        return $this;
    }

    /**
     * Update label
     * @access public
     * @param string $name Cache ID
     * @return void
     */
    protected function setTagItem($name)
    {
        if ($this->tag) {
            $key       = 'tag_' . md5($this->tag);
            $this->tag = null;
            if ($this->has($key)) {
                $value   = explode(',', $this->get($key));
                $value[] = $name;
                $value   = implode(',', array_unique($value));
            } else {
                $value = $name;
            }
            $this->set($key, $value, 0);
        }
    }

    /**
     * Get the cache ID contained in the tag
     * @access public
     * @param string $tag Cache tag
     * @return array
     */
    protected function getTagItem($tag)
    {
        $key   = 'tag_' . md5($tag);
        $value = $this->get($key);
        if ($value) {
            return array_filter(explode(',', $value));
        } else {
            return [];
        }
    }

    /**
     * Return the handle object, perform other advanced methods
     *
     * @access public
     * @return object
     */
    public function handler()
    {
        return $this->handler;
    }
}
