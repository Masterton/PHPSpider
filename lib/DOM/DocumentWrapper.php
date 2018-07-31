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

    /**
     * load
     */
    public function load($markup, $contentType = null, $newDocumentID = null)
    {
    	// Query::$documents[$id] = $this;
    	$this->contentType = strtolower($contentType);
    	if ($markup instanceof DOMDOCUMENT) {
    		$this->document = $markup;
    		$this->root = $this->document;
    		$this->charset = $this->document->encoding;
    		// TODO isDocumentFragment
    	} else {
    		$loaded = $this->loadMarkup($markup);
    	}
    	if ($loaded) {
    		// $this->document->formatOutput = true;
    		$this->document->preserveWhiteSpace = true;
    		$this->xpath = new DOMXpath($this->document);
    		$this->afterMarkupLoad();
    		return true;
    		// remember last loaded document
    		// return Query::selectDocument($id);
    	}
    	return false;
    }

    /**
     * afterMarkupLoad
     */
    protected function afterMarkupLoad()
    {
    	if ($this->isXHTML) {
    		$this->xpath->registerNamespace("html", "http://www.w3.org/1999/xhtml");
    	}
    }

    /**
     * loadMarkup
     */
    protected function loadMarkup($markup)
    {
    	$loaded = false;
    	if ($this->contentType) {
    		self::debug("Load markup for content type {$this->contentType}");
    		// content determined by contentType
    		list($contentType, $charset) = $this->contentTypeToArray($this->contentType);
    		switch ($contentType) {
    			case 'text/html':
    				Query::debug("Loading HTML, content type '{$this->contentType}'");
    				$loaded = $this->loadMarkupHTML($markup, $charset);
    				break;
    		}
    	}
    }

    /**
     * loadMarkupReset
     */
    protected function loadMarkupReset()
    {
    	$this->isXML = $this->isXHTML = $this->isHTML = false;
    }
}
