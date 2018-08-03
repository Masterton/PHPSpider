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
    			case 'text/html':
    			case 'application/xhtml+xml':
    				Query::debug("Loading XML, content type '{$this->contentType}'");
    				$loaded = $this->loadMarkupXML($markup, $charset);
    				break;
    			default:
    				// for feeds or anything that sometimes doesn't use text/xml
    				if (strpos('xml', $this->contentType) !== false) {
    					Query::debug("Loading XML, content type '{$this->contentType}'");
    					$loaded = $this->loadMarkupXML($markup, $charset);
    				} else {
    					Query::debug("Could not determine document type from content type '{$this->contentType}'");
    				}
    		}
    	} else {
    		// content type autodetection
    		if ($this->isXML($markup)) {
    			Query::debug("Loading XML, isXML() == true");
    			if (!$loaded && $this->isXHTML) {
    				Query::debug('Loading as XML failed, trying to load as HTML, isXHTML == true');
    				$loaded = $this->loadMarkupHTML($markup);
    			}
    		} else {
    			Query::debug("Loading HTML, isXML() == false");
    			$loaded = $this->loadMarkupHTML($markup);
    		}
    	}
    	return $loaded;
    }

    /**
     * loadMarkupReset
     */
    protected function loadMarkupReset()
    {
    	$this->isXML = $this->isXHTML = $this->isHTML = false;
    }

    /**
     * documentCreate
     */
    protected function documentCreate($charset, $version = '1.0')
    {
    	if (!$version) {
    		$version = '1.0';
    	}
    	$this->document = new DOMDocument($version, $charset);
    	$this->charset = $this->document->encoding;
    	// $this->document->encoding = $charset;
    	$this->document->formatOutput = true;
    	$this->document->preserveWhiteSpace = true;
    }

    /**
     * loadMarkupHTML
     */
    protected function loadMarkupHTML($markup, $requestedCharset = null)
    {
    	if (Query::$debug) {
    		Query::debug("Full markup load (HTML): " . substr($markup, 0, 250));
    	}
    	$this->loadMarkupReset();
    	$this->isHTML = true;
    	if (!isset($this->isDocumentFragment)) {
    		$this->isDocumentFragment = self::isDocumentFragmentHTML($markup);
    	}
    	$charset = null;
    }
}
