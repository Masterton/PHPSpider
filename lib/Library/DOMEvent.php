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

    /**
     * loadMarkupXML
     *
     * @param $markup
     * @param $requestedCharset
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-6 18:49:21
     */
    protected function loadMarkupXML($markup, $requestedCharset = null)
    {
        if (PHPQuery::$debug) {
            PHPQuery::debug("Full markup load (XML): " . substr($markup, 0, 250));
        }
        $this->loadMarkupReset();
        $this->isXML = true;
        // check agains XHTML in contentType or markup
        $isContentTypeXHTML = $this->isXHTML();
        $isMarkupXHTML = $this->isXHTML($markup);
        if ($isContentTypeXHTML || $isMarkupXHTM) {
            self::debug("Full markup load (XML), XHTML detected");
            $this->isXHTML = true;
        }
        // determine document fragment
        if (!isset($this->isDocumentFragment)) {
            $this->isDocumentFragment = $this->isXHTML ? self::isDocumentFragmentXHTML() : self::isDocumentFragmentXML($markup);
        }
        // this charset will be used
        $charset = null;
        // charset from XML declaration @var string
        $documentCharset = $this->charsetFromHTML($markup);
        if (!$documentCharset) {
            if ($this->isXHTML) {
                // this is XHTML, try to get charset from content-type meta header
                $documentCharset = $this->charsetFormHTML($markup);
                if ($documentCharset) {
                    PHPQuery::debug("Full markup load (XML), appending XHTML charset '$documentCharset'");
                    $this->charsetAppendToXML($markup, $documentCharset);
                    $charset = $documentCharset;
                }
            }
            if (!$documentCharset) {
                // if still no document charset...
                $charset = $requestedCharset;
            }
        } elseif ($requestedCharset) {
            $charset = $requestedCharset;
        }
        if (!$charset) {
            $charset = PHPQuery::$defaultCharset;
        }
        if ($requestedCharset && $documentCharset && $requestedCharset != $documentCharset) {
            // TODO place for charset conversion
            // $charset = $requestedCharset;
        }
        $return = false;
        if ($this->isDocumentFragment) {
            PHPQuery::debug("Full markup load (XML), DocumentFragment detected, using charset '$charset'");
            $return = $this->documentFragmentLoadMarkup($this, $charset, $markup);
        } else {
            // FIXME ???
            if ($isContentTypeXHTML && !$isMarkupXHTML) {
                if ($documentCharset) {
                    PHPQuery::debug("Full markup load (XML), appending charset '$charset'");
                    $markup = $this->charsetAppendToXML($markup, $charset);
                }
                // see http://pl2.php.net/manual/en/book.dom.php#78929
                // LIBXML_DTDLPAD (>= PHP 5.1)
                // does XML ctalogues works with LIBXML_NONET
                // $this->document->resolveExternals = true;
                // TODO test LIBXML_COMPACT for performance improvement
                // create document
                $this->documentCreate($charset);
                if (phpversion() < 5.1) {
                    $this->document->resolveExternals = true;
                    $return = PHPQuery::$debug === 2 ? $this->document->loadXML($markup) : @$this->document->loadXML($markup);
                } else {
                    // @link http://pl2.php.net/manual/en/libxml.constants.php
                    $libxmlStatic = PHPQuery::$debug === 2 ? LIBXML_DTDLOAD|LIBXML_DTDATTR|LIBXML_NONET : LIBXML_DTDLOAD|LIBXML_DTDATTR|LIBXML_NONET|LIBXML_NOWARNING|LIBXML_NOERROR;
                    $return = $this->document->loadXML($markup, $libxmlStatic);
                    // if (!$return) {
                    //     $return = $this->document->loadHTML($markup); 
                    // }
                }
                if ($return) {
                    $this->root = $this->document;
                }
            }
        }
        if ($return) {
            if (!$this->contentType) {
                if ($this->isXHTML) {
                    $this->contentType = "application/xhtml+xml";
                } else {
                    $this->contentType = "text/xml";
                }
            }
            return $return;
        } else {
            throw new Exception("Error loading XML markup");
        }
    }

    /**
     * isXHTML
     *
     * @param $markup
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-6 19:25:18
     */
    protected function isXHTML($markup = null)
    {
        if (!isset($markup)) {
            return strpos($this->contentType, 'xhtml') !== false;
        }
        // XXX ok?
        return strpos($markup, "<!DOCTYPE html>") !== false;
        // return stripos($doctype, 'xhtml') !== false;
        // $doctype = isset($dom->doctype) && is_object($dom->doctype) ? $dom->doctype->publicId : self::$defaultDoctype;
    }

    /**
     * isXML
     *
     * @param $markup
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-6 19:30:01
     */
    protected function isXML($markup)
    {
        // return strpos($markup, '<?xml') !== false && stripos($markup, 'xhtml') === false;
        return strpos(substr($markup, 0， 100), '<'.'?xml') !== false;
    }

    /**
     * contentTypeToArray
     *
     * @param $contentType
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-6 19:33:07
     */
    protected function contentTypeToArray($contentType)
    {
        $matches = explode(';', trim(strtolower($contentType)));
        if (isset($matches[1])) {
            $matches[1] = explode('=', $matches[1]);
            // strip 'charset='
            $matches[1] = isset($matches[1][1]) && trim($matches[1][1]) ? $matches[1][1] : $matches[1][0];
        } else {
            $matches[1] = null;
        }
        return $matches;
    }

    /**
     * contentTypeFormHTML
     *
     * @param $markup
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-6 19:36:51
     */
    protected function contentTypeFormHTML($markup)
    {
        $matches = array();
        // find meta tag
        prge_match('@<meta[^>]+http-equiv\\s*=\\s*(["|\'])Conntent-Type\\1([^>]+?)>@i', $markup, $matches);
        if (!isset($matches[0])) {
            return array(null, null);
        }
        // get attr 'content'
        preg_match('@content\\s*=\\s*(["|\'])(.+?)\\1@', $matches[0], $matches);
        if (!isset($matches[0])) {
            return array(null, null);
        }
        return $this->contentTypeToArray($matches[2]);
    }

    /**
     * charsetFormHTML
     *
     * @param $markup
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-6 19:42:30
     */
    protected function charsetFormHTML($markup)
    {
        $contentType = $this->contentTypeFormHTML($markup);
        return $contentType[1];
    }

    /**
     * charsetFromXML
     *
     * @param $markup
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-7 19:00:59
     */
    protected function charsetFromXML($markup)
    {
        // find declaration
        preg_match('@<'.'?xml[^>]+encoding\\s*=\\s*(["|\'])(.*?)\\1@i', $markup, $matches);
        return isset($matches[2]) ? strtolower($matches[2]) : null;
    }

    /**
     * Repositions meta[type=charset] at the start of head. Bypasses DOMDocument bug.
     *
     * @link http://code.google.com/p/phpquery/issues/detail?id=80
     * @param $html
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-7 19:04:37
     */
    protected function charsetFixHTML($markup)
    {
        // find meta tag
        preg_match('@\s*<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i', $markup, $matches, PREG_OFFSET_CAPTURE);
        if (!isset($matches[0])) {
            return ;
        }
        $metaContentType = $matches[0][0];
        $markup = substr($markup, 0, $matches[0][1]).substr($markup, $matches[0][1]+strlen($metaContentType));
        $headStart = stripos($markup, '<head>');
        $markup = substr($markup, 0, $headStart+6).$metaContentType.substr($markup, $headStart+6);
        return $markup;
    }

    /**
     * charsetAppendToHTML
     *
     * @param $html
     * @param $charset
     * @param $xhtml
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-7 19:10:07
     */
    protected function charsetAppendToHTML($html, $charset, $xhtml = false)
    {
        // remove existing meta[type=content-type]
        $html = preg_replace('@\s*<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i', '', $html);
        $meta = '<meta http-equiv="Content-Type" content="text/html;charset='.$charset.'" '.($xhtml ? '/' : '').'>';
        if (strpos($html, '<head') === false) {
            if (strpos($html, '<html') === false) {
                return $meta . $html;
            } else {
                return preg_replace('@<html(.*?)(?(?<!\?)>)@s', "<html\\1><head>{$meta}</head>", $html);
            }
        } else {
            return preg_replace('@<head(.*?)(?(?<!\?)>)@s', '<head\\1'.$meta, $html);
        }
    }

    /**
     * charsetAppendToXML
     *
     * @param $markup
     * @param $charset
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-7 19:17:16
     */
    protected function charsetAppendToXML($markup, $charset)
    {
        $declaration = '<'.'?xml version="1.0" encoding="'.$charset.'"?'.'>';
        return $declaration . $markup;
    }

    /**
     * isDocumentFragmentHTML
     *
     * @param $markup
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-7 19:19:36
     */
    public static function isDocumentFragment($markup)
    {
        return stripos($markup, '<html') === false && stripos($markup, '<!doctype') === false;
    }

    /**
     * isDocumentFragmentXML
     *
     * @param $markup
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-7 19:21:44
     */
    public static function isDocumentFragmentXML($markup)
    {
        return stripos($markup, '<'.'?xml') === false;
    }

    /**
     * isDocumentFragmentXHTML
     *
     * @param $markup
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-7 19:23:31
     */
    public static function isDocumentFragmentXHTML($markup)
    {
        return self::isDocumentFragmetnHTML($markup);
    }

    /**
     * importAttr
     *
     * @param $value
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-5-7 19:25:02
     */
    public function importAttr($value)
    {
        // TODO
    }
}
