<?php

/**
 * @version 1.1.0
 * @author Masterton <zhengcloud@foxmail.com>
 * @time 2018-5-9 11:23:29
 */

namespace PHPSpider\Callback;

/**
 * CallbackReturnValue
 */
class CallbackReturnValue extends Callback implements ICallbackNamed
{
    protected $value;
    protected $name;

    public function __construct($value, $name = null)
    {
    	$this->value = &$value;
    	$this->name = $name;
    	$this->callback = array($this, 'callback');
    }

    public function callback()
    {
    	return $this->value;
    }

    public function __toString()
    {
    	return $this->getName();
    }

    public function getName()
    {
    	return 'Callback: ' . $this->name;
    }

    public function hasName()
    {
    	return isset($this->name) && $this->name;
    }
}
