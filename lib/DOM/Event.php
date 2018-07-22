<?php

/**
 * @version 1.1.0
 * @author Masterton <zhengcloud@foxmail.com>
 * @time 2018-5-9 11:23:29
 */

namespace PHPSpider\Lib\DOM;

/**
 * Event
 */
class Event
{
    /**
     * Returns a boolean indicating whether the event bubbles up through the DOM or not.
     *
     * @var unknown_type
     */
    public $bubbles = true;

    /**
     * Returns a boolean indicating whether the event is cancelable.
     *
     * @var unknown_type
     */
    public $cancelable = true;
}
