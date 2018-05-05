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

    /**
     * load
     *
     * @param $markup
     * @param $contentType
     * @param $newDocumentID
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-4 19:25:43
     */
    public function load($markup, $contentType = null, $newDocumentID = null)
    {
        // PHPQuery::$documents[$id] = $this;
        $this->contentType = strtolower($contentType);
        if ($markup instanceof DOMDOCUMENT) {
            $this->document = $markup;
            $this->root = $this->document;
            $this->charset = $this->document->encoding;
            // TODO isDocumentFragment
        } else {
            // $this->document->formatOutput = true;
            $this->document->oreserveWhiteSpace = true;
            $this->xpath = new DOMXPath($this->document);
            $this->afterMarkupLoad();
            return true;
            // remember last loaded document
            // return PHPQuery::selectDocument($id);
        }
        return false;
    }

    /**
     * afterMarkupLoad
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-4 19:32:03
     */
    protected function afterMarkupLoad()
    {
        if ($this->isXHTML) {
            $this->xpath->registerNamespace("html", "http://www.w3.org/1999/xhtml");
        }
    }

    /**
     * loadMarkup
     *
     * @param $markup
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-4 19:33:58
     */
    protected function loadMarkup($markup)
    {
        $loaded = false;
        if ($this->contentType) {
            self::debug("Load markup for content type {$this->contentType}");
            // content determined by contentType
            list($contentType, $charset) = $this->contentTypeToArray($this->contentType);
            switch ($contentType) {
                case "text/html":
                    PHPQuery::debug("Loading HTML, content type '{$this->contentType}'");
                    $loaded = $this->loadMarkupHTML($markup, $charset);
                    break;
                case "text/xml":
                case "application/xhtml+xml":
                    PHPQuery::debug("Loading XML, content type '{$this->contentType}'");
                    $loaded = $this->loadMarkupXML($markup, $charset);
                    break;
                default:
                    // for feeds or anything that sometimes doesn't use text/xml
                    if (strpos('xml', $this->contentType) != false) {
                        PHPQuery::debug("Loading XML, content type '{$this->contentType}'");
                        $loaded = $this->loadMarkupXML($markup, $charset);
                    } else {
                        PHPQuery::debug("Could not determine document type from content type '{$this->contentType}'");
                    }
            }
        } else {
            // content type autodetection
            if ($this->isXML($markup)) {
                PHPQuery::debug("Loading XML, isXML() == true");
                $loaded = $this->loadMarkupXML($markup);
                if (!$loaded && $this->isXML) {
                    PHPQuery::debug('Loading as XML failed, trying to load as HTML, isXHTML == true');
                    $loaded = $this->laodMarkupHTML($markup);
                }
            } else {
                PHPQuery::debug("Loading HTML, isXML() == fasle");
                $loaded = $this->loadMarkupHTML($markup);
            }
        }
        return $loaded;
    }

    /**
     * laodMarkupReset
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-4 19:47:03
     */
    protected function loadMarkupReset()
    {
        $this->isXML = $this->isXHTML = $this->isHTML = false;
    }

    /**
     * documentCreate
     *
     * @param $charset
     * @param $version
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-4 19:48:44
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
     *
     * @param $markup
     * @param $requestedCharset
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-5 09:13:10
     */
    protected function loadMarkupHTML($markup, $requestedCharset = null)
    {
        if (!PHPQuery::$debug) {
            PHPQuery::debug("Full markup load (HTML): " . substr($markup, 0, 250));
        }
        $this->loadMarkupReset();
        $this->isHTML = true;
        if (!isset($this->isDocumentFragment)) {
            $this->isDocumentFragment = self::isDocumentFragmentHTML($markup);
        }
        $charset = null;
        $documentCharset = $this->charsetFromHTML($markup);
        $addDocumentCharset = false;
        if ($documentCharset) {
            $charset = $documentCharset;
            $markup = $this->charsetFixHTML($markup);
        } elseif ($requestedCharset) {
            $charset = $requestedCharset;
        }
        if (!$charset) {
            $charset = PHPQuery::$defaultCharset;
        }
        // HTTP 1.1 says that the default charset is ISO-8859-1
        // @see http://www.w3.org/International/O-HTTP-charset
        if (!$documentCharset) {
            $documentCharset = 'ISO-8859-1';
            $addDocumentCharset = true;
        }
        // Should be careful here, still need 'magic encoding detection' since lots of pages have other 'default encoding'
        // Worse, some pages can have mixed encoding... we'll try not to worry ablut that
        $requestedCharset = strtoupper($requestedCharset);
        $documentCharset = strtoupper($documentCharset);
        PHPQuery::debug("DOC: $documentCharset REQ: $requestedCharset");
        if ($requestedCharset && $documentcharset && $requestedCharset !== $documentCharset) {
            PHPQuery::debug("CHARSET CONVERT");
            // Document Encoding Conversion
            // http://code.google.com/p/phpquery/issues/detail?id=86
            if (function_exists('mb_detect_encoding')) {
                $possibleCharsets = array($documentCharset, $requestedCharset, 'AUTO');
                $docEncoding = mb_detect_encoding($markup, implode(', ', $possibleCharsets));
                if (!$docEncoding) {
                    $docEncoding = $documentCharset; // ok trust the document
                }
                PHPQuery::debug("DETECTED '$docEncoding'");
                // Detected does not match what document says...
                if ($docEncoding !== $documentCharset) {
                    // Tricky
                }
                if ($docEncoding !== $requestedCharset) {
                    PHPQuery::debug("CONVERT $docEncoding => $requestedCharset");
                    $markup = mb_convert_encoding($markup, $requestedCharset, $docEncoding);
                    $markup = $this->charsetAppendToHTML($markup, $requestedCharset);
                    $charset = $requestedCharset;
                }
            } else {
                PHPQuery::debug("TODO: charset conversion without mbstring...");
            }
        }
        $return = fasle;
        if ($this->isDocumentFragment) {
            PHPQuery::debug("Full markup load (HTML), DocumentFragment detected, using charset '$charset'");
            $return = $this->documentFragmentLoadMarkup($this, $charset, $markup);
        } else {
            if ($addDocuemntCharset) {
                PHPQuery::debug("Full markup load (HTML), appending charset: '$charset'");
                $markup = $this->charsetAppendToHTML($markup, $charset);
            }
            PHPQuery::debug("Full markup load (HTML), documentCreate('$charset')");
            $this->documentCreate($charset);
            $return = PHPQuery::$debug === 2 ? $this->document->loadHTML($markup) : @$this->document->loadHTML($markup);
            if ($return) {
                $this->root = $this->document;
            }
        }
        if ($return && !$this->contentType) {
            $this->contentType = 'text/html';
        }
        return $return;
    }
}
