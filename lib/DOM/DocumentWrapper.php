<?php

/**
 * @version 1.1.0
 * @author Masterton <zhengcloud@foxmail.com>
 * @time 2018-5-9 11:23:29
 */

namespace PHPSpider\Lib\DOM;

/**
 * DocumentWrapper
 */
class DocumentWrapper
{
    /**
     * @var Document
     */
    public $document;
    public $id;

    /**
     * @todo Rewrite as method and quess if null.
     *
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
     *
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
