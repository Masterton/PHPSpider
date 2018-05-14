<?php

/**
 * @version 1.1.0
 * @author Masterton <zhengcloud@foxmail.com>
 * @time 2018-5-9 11:23:29
 */

namespace PHPSpider\Callback;

/**
 * CallbackReference
 */
class CallbackReference extends Callback implements ICallbackNamed
{
    protected $reference;

    public function __construct(&$reference, $name = null)
    {
    	$this->reference = &$reference;
    	$this->callback = array($this, 'callback');
    }

    public function callback()
    {
    	return $this->reference;
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
