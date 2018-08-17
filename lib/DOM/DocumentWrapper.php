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
        $documentCharset = $this->charsetFromHTML($markup);
        $addDocumentCharset = false;
        if ($documentCharset) {
            $charset = $documentCharset;
            $markup = $this->charsetFixHTML($markup);
        } else if ($requestedCharset) {
           $charset = $requestedCharset;
        }
        if (!$charset) {
            $charset = Query::$defaultCharset;
        }
        // HTTP 1.1 says that the default charset is ISO-8859-1
        // @see http://www.w3.org/International/O-HTTP-charset
        if (!$documentCharset) {
            $documentCharset = 'ISO-8859-1';
            $addDocumentCharset = true;
        }
        // Should be careful here, still need 'magic encoding detection' since lots of pages have other 'default encoding'
        // Worse, some pages can have mixed encodings... we'll try not to worry about that
        $requestedCharset = strtoupper($requestedCharset);
        $documentCharset = strtoupper($documentCharset);
        Query::debug("DOC: $documentCharset REQ: $requestedCharset");
        if ($requestedCharset && $documentCharset && $requestedCharset !== $documentCharset) {
            Query::debug("CHARSET CONVERT");
            // Document Encoding Conversion
            // http://code.google.com/p/phpquery/issues/detail?id=86
            if (function_exists('mb_detect_encoding')) {
                $possibleCharsets = array($documentCharset, $requestedCharset, 'AUTO');
                $docEncoding = mb_detect_encoding($markup, implode(', ', $possibleCharsets));
                if (!$docEncoding) {
                    $docEncoding = $documentCharset; // ok trust the document
                }
                Query::debug("DETECTED '$docEncoding'");
                // Detected does not match what document says...
                if ($docEncoding !== $documentCharset) {
                    // Tricky..
                }
                if ($docEncoding !== $requestedCharset) {
                    Query::debug("CONVERT $docEncoding => $requestedCharset");
                    $markup = mb_convert_encoding($markup, $requestedCharset, $docEncoding);
                    $markup = $this->charsetAppendToHTML($markup, $requestedCharset);
                    $charset = $requestedCharset;
                }
            } else {
                Query::debug("TODO: charset conversion without mbstring...");
            }
        }
        $return = false;
        if ($this->isDocumentFragment) {
            Query::debug("Full markup load (HTML), DocumentFragment detected, using charset '$charset'");
            $return = $this->documentFragmentLoadMarkup($this, $charset, $markup);
        } else {
            if ($addDocumentCharset) {
                Query::debug("Full markup load (HTML), appending charset: '$charset'");
                $markup = $this->charsetAppendToHTML($markup, $charset);
            }
            Query::debug("Full markup load (HTML), documentCreate('$charset')");
            $this->documentCreate($charset);
            $return = Query::$debug === 2 ? $this->document->loadHTML($markup) : @$this->document->loadHTML($markup);
            if ($return) {
                $this->root = $this->document;
            }
        }
        if ($return && ! $this->contentType) {
            $this->contentType = 'text/html';
        }
        return $return;
    }

    /**
     * loadMarkupXML
     */
    protected function loadMarkupXML($markup, $requestedCharset = null)
    {
        if (Query::$debug) {
            Query::debug('Full markup load (XML): '.substr($markup, 0, 250));
        }
        $this->loadMarkupReset();
        $this->isXML = true;
        // check agains XHTML in contentType or markup
        $isContentTypeXHTML = $this->isXHTML();
        $isMarkupXHTML = $this->isXHTML($markup);
        if ($isContentTypeXHTML || $isMarkupXHTML) {
            self::debug('Full markup load (XML), XHTML detected');
            $this->isXHTML = true;
        }
        // determine document fragment
        if (!isset($this->isDocumentFragment)) {
            $this->isDocumentFragment = $this->isXHTML ? self::isDocumentFragmentXHTML($markup) : self::isDocumentFragmentXML($markup);
        }
        // this charset will be used
        $charset = null;
        // charset from XML declaration @var string
        $documentCharset = $this->charsetFromXML($markup);
        if (!$documentCharset) {
            if ($this->isXHTML) {
                // this is XHTML, try to get charset from content-type meta herader
                $documentCharset = $this->charsetFromHTML($markup);
                if ($documentCharset) {
                    phpQuery::debug("Full markup load (XML), appending XHTML charset '$documentCharset'");
                    $this->charsetAppendToXML($markup, $documentCharset);
                    $charset = $documentCharset;
                }
            }
            if (! $documentCharset) {
                // if still no document charset...
                $charset = $requestedCharset;
            }
        } else if ($requestedCharset) {
            $charset = $requestedCharset;
        }
        if (! $charset) {
            $charset = phpQuery::$defaultCharset;
        }
        if ($requestedCharset && $documentCharset && $requestedCharset != $documentCharset) {
            // TODO place for charset conversion
            // $charset = $requestedCharset;
        }
        $return = false;
        if ($this->isDocumentFragment) {
            Query::debug("Full markup load (XML), DocumentFragment detected, using charset '$charset'");
            $return = $this->documentFragmentLoadMarkup($this, $charset, $markup);
        } else {
            // FIXME ???
            if ($isContentTypeXHTML && ! $isMarkupXHTML)
            if (! $documentCharset) {
                phpQuery::debug("Full markup load (XML), appending charset '$charset'");
                $markup = $this->charsetAppendToXML($markup, $charset);
            }
            // see http://pl2.php.net/manual/en/book.dom.php#78929
            // LIBXML_DTDLOAD (>= PHP 5.1)
            // does XML ctalogues works with LIBXML_NONET
            // $this->document->resolveExternals = true;
            // TODO test LIBXML_COMPACT for performance improvement
            // create document
            $this->documentCreate($charset);
            if (phpversion() < 5.1) {
                $this->document->resolveExternals = true;
                $return = phpQuery::$debug === 2
                    ? $this->document->loadXML($markup)
                    : @$this->document->loadXML($markup);
            } else {
                /** @link http://pl2.php.net/manual/en/libxml.constants.php */
                $libxmlStatic = phpQuery::$debug === 2
                    ? LIBXML_DTDLOAD|LIBXML_DTDATTR|LIBXML_NONET
                    : LIBXML_DTDLOAD|LIBXML_DTDATTR|LIBXML_NONET|LIBXML_NOWARNING|LIBXML_NOERROR;
                $return = $this->document->loadXML($markup, $libxmlStatic);
                /*if (! $return) {
                    $return = $this->document->loadHTML($markup);
                }*/
            }
            if ($return) {
                $this->root = $this->document;
            }
        }
        if ($return) {
            if (!$this->contentType) {
                if ($this->isXHTML) {
                    $this->contentType = 'application/xhtml+xml';
                } else {
                    $this->contentType = 'text/xml';
                }
            }
            return $return;
        } else {
            throw new Exception("Error loading XML markup");
        }
    }

    /**
     * isXHTML
     */
    protected function isXHTML($markup = null)
    {
        if (!isset($markup)) {
            return strpos($this->contentType, 'xhtml') !== false;
        }
        // XXX ok ?
        return strpos($markup, "<!DOCTYPE html") !== false;
        // return stripos($doctype, 'xhtml') !== false;
        // $doctype = isset($dom->doctype) && is_object($dom->doctype) ? $dom->doctype->publicId : self::$defaultDoctype;
    }

    /**
     * isXML
     */
    protected function isXML($markup)
    {
        // return strpos($markup, '<?xml') !== false && stripos($markup, 'xhtml') === false;
        return strpos(substr($markup, 0, 100), '<'.'?xml') !== false;
    }

    /**
     * contentTypeToArray
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
     *
     * @param $markup
     * @return array contentType, charset
     */
    protected function contentTypeFromHTML($markup) {
        $matches = array();
        // find meta tag
        preg_match('@<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i', $markup, $matches);
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
     * charsetFromHTML
     */
    protected function charsetFromHTML($markup)
    {
        $contentType = $this->contentTypeFromHTML($markup);
        return $contentType[1];
    }

    /**
     * charsetFromXML
     */
    protected function charsetFromXML($markup)
    {
        $marches;
        // find declaration
        preg_match('@<'.'?xml[^>]+encoding\\s*=\\s*(["|\'])(.*?)\\1@i', $markup, $matches);
        return isset($matches[2]) ? strtolower($marches[2]) : null;
    }

    /**
     * Repositions meta[type=charset] at the start of head. Bypasses DOMDocument bug.
     *
     * @link http://code.google.com/p/phpquery/issues/detail?id=80
     * @param $html
     */
    protected function charsetFixHTML($markup) {
        $matches = array();
        // find meta tag
        preg_match('@\s*<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i', $markup, $matches, PREG_OFFSET_CAPTURE);
        if (!isset($matches[0])) {
            return;
        }
        $metaContentType = $matches[0][0];
        $markup = substr($markup, 0, $matches[0][1]) . substr($markup, $matches[0][1] + strlen($metaContentType));
        $headStart = stripos($markup, '<head>');
        $markup = substr($markup, 0, $headStart + 6) . $metaContentType . substr($markup, $headStart + 6);
        return $markup;
    }

    /**
     * charsetAppendToHTML
     */
    protected function charsetAppendToHTML($html, $charset, $xhtml = false) {
        // remove existing meta[type=content-type]
        $html = preg_replace('@\s*<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i', '', $html);
        $meta = '<meta http-equiv="Content-Type" content="text/html;charset=' . $charset . '" ' . ($xhtml ? '/' : '') . '>';
        if (strpos($html, '<head') === false) {
            if (strpos($hltml, '<html') === false) {
                return $meta.$html;
            } else {
                return preg_replace('@<html(.*?)(?(?<!\?)>)@s', "<html\\1><head>{$meta}</head>", $html);
            }
        } else {
            return preg_replace('@<head(.*?)(?(?<!\?)>)@s', '<head\\1>' . $meta, $html);
        }
    }

    /**
     * charsetAppendToXML
     */
    protected function charsetAppendToXML($markup, $charset) {
        $declaration = '<'.'?xml version="1.0" encoding="'.$charset.'"?'.'>';
        return $declaration.$markup;
    }
}
