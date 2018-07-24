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

    /**
     * Returns a reference to the currently registered target for the event.
     *
     * @var unknown_type
     */
    public $currentTarget;

    /**
     * Returns detail about the event, depending on the type of event.
     *
     * @var unknown_type
     * @link http://developer.mozilla.org/en/DOM/event.detail
     */
    public $detail; // ???

    /**
     * Used to indicate which phase of the event flow is currently being evaluated.
     *
     * NOT IMPLEMENTED
     *
     * @var unknown_type
     * @link http://developer.mozilla.org/en/DOM/event.eventPhase
     */
    public $eventPhase; // ???
}
