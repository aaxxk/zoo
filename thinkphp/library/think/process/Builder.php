<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2015 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

namespace think\process;

use think\Process;

class Builder
{
    private $arguments;
    private $cwd;
    private $env = null;
    private $input;
    private $timeout        = 60;
    private $options        = [];
    private $inheritEnv     = true;
    private $prefix         = [];
    private $outputDisabled = false;

    /**
     *
     * @param string[] $arguments parameter
     */
    public function __construct(array $arguments = [])
    {
        $this->arguments = $arguments;
    }

    /**
     * Create an instance
     * @param string[] $arguments parameter
     * @return self
     */
    public static function create(array $arguments = [])
    {
        return new static($arguments);
    }

    /**
     * Add a parameter
     * @param string $argument parameter
     * @return self
     */
    public function add($argument)
    {
        $this->arguments[] = $argument;

        return $this;
    }

    /**
     * Add a prefix
     * @param string|array $prefix
     * @return self
     */
    public function setPrefix($prefix)
    {
        $this->prefix = is_array($prefix) ? $prefix : [$prefix];

        return $this;
    }

    /**
     * Setting parameters
     * @param string[] $arguments
     * @return  self
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * Set working directory
     * @param null|string $cwd
     * @return  self
     */
    public function setWorkingDirectory($cwd)
    {
        $this->cwd = $cwd;

        return $this;
    }

    /**
     * Whether to initialize environment variables
     * @param bool $inheritEnv
     * @return self
     */
    public function inheritEnvironmentVariables($inheritEnv = true)
    {
        $this->inheritEnv = $inheritEnv;

        return $this;
    }

    /**
     * Set environment variables
     * @param string      $name
     * @param null|string $value
     * @return self
     */
    public function setEnv($name, $value)
    {
        $this->env[$name] = $value;

        return $this;
    }

    /**
     *  Add environment variables
     * @param array $variables
     * @return self
     */
    public function addEnvironmentVariables(array $variables)
    {
        $this->env = array_replace($this->env, $variables);

        return $this;
    }

    /**
     * Set input
     * @param mixed $input
     * @return self
     */
    public function setInput($input)
    {
        $this->input = Utils::validateInput(sprintf('%s::%s', __CLASS__, __FUNCTION__), $input);

        return $this;
    }

    /**
     * Set timeout
     * @param float|null $timeout
     * @return self
     */
    public function setTimeout($timeout)
    {
        if (null === $timeout) {
            $this->timeout = null;

            return $this;
        }

        $timeout = (float) $timeout;

        if ($timeout < 0) {
            throw new \InvalidArgumentException('The timeout value must be a valid positive integer or float number.');
        }

        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Set proc_open option
     * @param string $name
     * @param string $value
     * @return self
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Prohibit output
     * @return self
     */
    public function disableOutput()
    {
        $this->outputDisabled = true;

        return $this;
    }

    /**
     * Turn on output
     * @return self
     */
    public function enableOutput()
    {
        $this->outputDisabled = false;

        return $this;
    }

    /**
     * Create a Process instance
     * @return Process
     */
    public function getProcess()
    {
        if (0 === count($this->prefix) && 0 === count($this->arguments)) {
            throw new \LogicException('You must add() command arguments before calling getProcess().');
        }

        $options = $this->options;

        $arguments = array_merge($this->prefix, $this->arguments);
        $script    = implode(' ', array_map([__NAMESPACE__ . '\\Utils', 'escapeArgument'], $arguments));

        if ($this->inheritEnv) {
            // include $_ENV for BC purposes
            $env = array_replace($_ENV, $_SERVER, $this->env);
        } else {
            $env = $this->env;
        }

        $process = new Process($script, $this->cwd, $env, $this->input, $this->timeout, $options);

        if ($this->outputDisabled) {
            $process->disableOutput();
        }

        return $process;
    }
}
