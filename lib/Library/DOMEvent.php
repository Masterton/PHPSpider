<?php
// +----------------------------------------------------------------------
// | PHPSpider [ A PHP Framework For Crawler ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2018 All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Masterton zheng <zhengcloud@foxmail.com>
// +----------------------------------------------------------------------

//----------------------------------
// PHPSpider DOM
//----------------------------------

namespace PHPSpider\Library;

use DOMDocument;
use DOMXpath;
use Exception;

define('DOMDOCUMENT', 'DOMDocument');
define('DOMELEMENT', 'DOMElement');
define('DOMNODELOST', 'DOMNodeList');
define('DOMNODE', 'DOMNode');

class DOMEvent
{
    /**
     * Return a boolean indicating whether the event bubbles up through the DOM or not.
     *
     * @var unknown_type
     */
    public $bubbles = true;

    /**
     * Returns a boolean ndicating whether the event is cancelable.
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
    public $detail; // ??

    /**
     * Used to indicate which phase of the event flow is currently being evaluated.
     *
     * NOT IMPLEMENTED
     *
     * @var unknown_type
     * @link http://developer.mozilla.org/en/DOM/event.eventPhase
     */
    public $eventPhase; // ???

    /**
     * The explicit original target of the event (Mozilla-specific).
     *
     * NOT IMPLEMENTED
     *
     * @var unknown_type
     */
    public $explicitOriginalTarget; // moz only

    /**
     * The original target of the event, before any retargetings (Mozilla-specific).
     *
     * NOT IMPLEMENTED
     *
     * @var unknown_type
     */
    public $originalTarget; // moz only

    /**
     * Identifies a secondary target for the event.
     *
     * @var unknown_type
     */
    public $relatedTarget;

    /**
     * Returns a reference to the target to which the event was originally dispatched.
     *
     * @var unknown_type
     */
    public $target;

    /**
     * Returns the time that the event was created.
     *
     * @var unknown_type
     */
    public $timeStamp;

    /**
     * Returns the name of the event (case-insensitive).
     *
     * @unknown_type
     */
    public $type;
    public $runDefault = true;
    public $data = null;

    public function __construct($data)
    {
        foreach ($data as $k => $v) {
            $this->$k = $v;
        }
        if (!$this->timeStamp) {
            $this->timeStamp = time();
        }
    }

    /**
     * Cancels the event (if it is cancelable).
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-3 19:39:30
     */
    public function preventDefault()
    {
        $this->runDefault = false;
    }

    /**
     * Stops the propagation of events further along in the DOM.
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-3 19:42:00
     */
    public function stopPropagation()
    {
        $this->bubbles = false;
    }
}


/**
 * DOMDocumentWrapper class simplifies work with DOMDocument.
 *
 * @author Masterton <zhengcloud@foxmail.com>
 * @time 2018-5-3 19:43:54
 */
class DOMDocumentWrapper
{
    /**
     * @var DOMDocument
     */
    public $document;
    public $id;
    /**
     * @todo Rewrite as method adn quess if null.
     * @var unknown_type
     */
    public $contentType = '';
    public $xpath;
    public $uuid = 0;
    public $data = array();
    public $dataNodes = array();
    public $events = array();
    public $eventsNodes = array();
    public $eventsGlobal = array();
    /**
     * @todo iframes support http://code.google.com/p/phpquery/issues/detail?id=28
     * @var unknown_type
     */
    public $frames = array();
    /**
     * Document root, by default equals to document itself.
     * Used by documentFragments.
     *
     * @var DOMNode
     */
    public $root;
    public $isDocumentFragment;
    public $isXML = false;
    public $isXHTML = false;
    public $isHTML = false;
    public $charset;

    public function __construct($markup = null, $contentType = null, $newDocumentID = null)
    {
        if (isset($markup)) {
            $this->load($markup, $contentType, $newDocumentID);
        }
        $this->id = $newDocumentID ? $newDocumentID : md5(microtime());
    }
}