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
    protected function charsetFixHTML($markup)
    {
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
    protected function charsetAppendToHTML($html, $charset, $xhtml = false)
    {
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
    protected function charsetAppendToXML($markup, $charset)
    {
        $declaration = '<'.'?xml version="1.0" encoding="'.$charset.'"?'.'>';
        return $declaration.$markup;
    }

    /**
     * isDocumentFragmentHTML
     */
    public static function isDocumentFragmentHTML($markup)
    {
        return stripos($markup, '<html') === false && stripos($markup, '<!doctype') === false;
    }

    /**
     * isDocumentFragmentXML
     */
    public static function isDocumentFragmentXML($markup)
    {
        return stripos($markup, '<'.'?xml') === false;
    }

    /**
     * isDocumentFragmentXHTML
     */
    public static function isDocumentFragmentXHTML($markup)
    {
        return self::isDocumentFragmentHTML($markup);
    }

    /**
     * importAttr
     */
    public function importAttr($value)
    {
        // TODO
    }

    /**
     * import
     *
     * @param $source
     * @param $target
     * @param $sourceCharset
     * @return array Array of imported nodes.
     */
    public function import($source, $sourceCharset = null)
    {
        // TODO charset conversions
        $return = array();
        if ($source instanceof DOMNODE && !($source instanceof DOMNODELIST)) {
            $source = array($source);
        }
        /*if (is_array($source)) {
            foreach($source as $node) {
                if (is_string($node)) {
                    // string markup
                    $fake = $this->documentFragmentCreate($node, $sourceCharset);
                    if ($fake === false) {
                        throw new Exception("Error loading documentFragment markup");
                    } else {
                        $return = array_merge($return, $this->import($fake->root->childNodes));
                    }
                } else {
                    $return[] = $this->document->importNode($node, true);
                }
            }
            return $return;
        } else {
            // string markup
            $fake = $this->documentFragmentCreate($source, $sourceCharset);
            if ($fake === false) {
                throw new Exception("Error loading documentFragment markup");
            } else {
                return $this->import($fake->root->childNodes);
            }
        }*/
        if (is_array($source) || $source instanceof DOMNODELIST) {
            // dom nodes
            self::debug('Importing nodes to document');
            foreach($source as $node) {
                $return[] = $this->document->importNode($node, true);
            }
        } else {
            // string markup
            $fake = $this->documentFragmentCreate($source, $sourceCharset);
            if ($fake === false) {
                throw new Exception("Error loading documentFragment markup");
            } else {
                return $this->import($fake->root->childNodes);
            }
        }
        return $return;
    }

    /**
     * Creates new document fragment.
     *
     * @param $source
     * @return DOMDocumentWrapper
     */
    protected function documentFragmentCreate($source, $charset = null)
    {
        $fake = new DOMDocumentWrapper();
        $fake->contentType = $this->contentType;
        $fake->isXML = $this->isXML;
        $fake->isHTML = $this->isHTML;
        $fake->isXHTML = $this->isXHTML;
        $fake->root = $fake->document;
        if (! $charset) {
            $charset = $this->charset;
        }
        // $fake->documentCreate($this->charset);
        if ($source instanceof DOMNODE && !($source instanceof DOMNODELIST)) {
            $source = array($source);
        }
        if (is_array($source) || $source instanceof DOMNODELIST) {
            // dom nodes
            // load fake document
            if (! $this->documentFragmentLoadMarkup($fake, $charset)) {
                return false;
            }
            $nodes = $fake->import($source);
            foreach($nodes as $node) {
                $fake->root->appendChild($node);
            }
        } else {
            // string markup
            $this->documentFragmentLoadMarkup($fake, $charset, $source);
        }
        return $fake;
    }

    /**
     *
     * @param $document DOMDocumentWrapper
     * @param $markup
     * @return $document
     */
    private function documentFragmentLoadMarkup($fragment, $charset, $markup = null)
    {
        // TODO error handling
        // TODO copy doctype
        // tempolary turn off
        $fragment->isDocumentFragment = false;
        if ($fragment->isXML) {
            if ($fragment->isXHTML) {
                // add FAKE element to set default namespace
                $fragment->loadMarkupXML('<?xml version="1.0" encoding="'.$charset.'"?>'
                    .'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" '
                    .'"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
                    .'<fake xmlns="http://www.w3.org/1999/xhtml">'.$markup.'</fake>');
                $fragment->root = $fragment->document->firstChild->nextSibling;
            } else {
                $fragment->loadMarkupXML('<?xml version="1.0" encoding="'.$charset.'"?><fake>'.$markup.'</fake>');
                $fragment->root = $fragment->document->firstChild;
            }
        } else {
            $markup2 = phpQuery::$defaultDoctype.'<html><head><meta http-equiv="Content-Type" content="text/html;charset='
                .$charset.'"></head>';
            $noBody = strpos($markup, '<body') === false;
            if ($noBody) {
                $markup2 .= '<body>';
            }
            $markup2 .= $markup;
            if ($noBody) {
                $markup2 .= '</body>';
            }
            $markup2 .= '</html>';
            $fragment->loadMarkupHTML($markup2);
            // TODO resolv body tag merging issue
            $fragment->root = $noBody
                ? $fragment->document->firstChild->nextSibling->firstChild->nextSibling
                : $fragment->document->firstChild->nextSibling->firstChild->nextSibling;
        }
        if (! $fragment->root) {
            return false;
        }
        $fragment->isDocumentFragment = true;
        return true;
    }

    /**
     * documentFragmentToMarkup
     */
    protected function documentFragmentToMarkup($fragment)
    {
        phpQuery::debug('documentFragmentToMarkup');
        $tmp = $fragment->isDocumentFragment;
        $fragment->isDocumentFragment = false;
        $markup = $fragment->markup();
        if ($fragment->isXML) {
            $markup = substr($markup, 0, strrpos($markup, '</fake>'));
            if ($fragment->isXHTML) {
                $markup = substr($markup, strpos($markup, '<fake')+43);
            } else {
                $markup = substr($markup, strpos($markup, '<fake>')+6);
            }
        } else {
            $markup = substr($markup, strpos($markup, '<body>')+6);
            $markup = substr($markup, 0, strrpos($markup, '</body>'));
        }
        $fragment->isDocumentFragment = $tmp;
        if (phpQuery::$debug) {
            phpQuery::debug('documentFragmentToMarkup: '.substr($markup, 0, 150));
        }
        return $markup;
    }

    /**
     * Return document markup, starting with optional $nodes as root.
     *
     * @param $nodes    DOMNode|DOMNodeList
     * @return string
     */
    public function markup($nodes = null, $innerMarkup = false)
    {
        if (isset($nodes) && count($nodes) == 1 && $nodes[0] instanceof DOMDOCUMENT) {
            $nodes = null;
        }
        if (isset($nodes)) {
            $markup = '';
            if (!is_array($nodes) && !($nodes instanceof DOMNODELIST) ) {
                $nodes = array($nodes);
            }
            if ($this->isDocumentFragment && ! $innerMarkup) {
                foreach($nodes as $i => $node) {
                    if ($node->isSameNode($this->root)) {
                    //  var_dump($node);
                        $nodes = array_slice($nodes, 0, $i)
                            + phpQuery::DOMNodeListToArray($node->childNodes)
                            + array_slice($nodes, $i+1);
                    }
                }
            }
            if ($this->isXML && ! $innerMarkup) {
                self::debug("Getting outerXML with charset '{$this->charset}'");
                // we need outerXML, so we can benefit from
                // $node param support in saveXML()
                foreach($nodes as $node) {
                    $markup .= $this->document->saveXML($node);
                }
            } else {
                $loop = array();
                if ($innerMarkup) {
                    foreach($nodes as $node) {
                        if ($node->childNodes) {
                            foreach($node->childNodes as $child)
                                $loop[] = $child;
                        } else {
                            $loop[] = $node;
                        }
                    }
                } else {
                    $loop = $nodes;
                }
                self::debug("Getting markup, moving selected nodes (".count($loop).") to new DocumentFragment");
                $fake = $this->documentFragmentCreate($loop);
                $markup = $this->documentFragmentToMarkup($fake);
            }
            if ($this->isXHTML) {
                self::debug("Fixing XHTML");
                $markup = self::markupFixXHTML($markup);
            }
            self::debug("Markup: ".substr($markup, 0, 250));
            return $markup;
        } else {
            if ($this->isDocumentFragment) {
                // documentFragment, html only...
                self::debug("Getting markup, DocumentFragment detected");
                // return $this->markup(
                //         $this->document->getElementsByTagName('body')->item(0)
                //     $this->document->root, true
                // );
                $markup = $this->documentFragmentToMarkup($this);
                // no need for markupFixXHTML, as it's done thought markup($nodes) method
                return $markup;
            } else {
                self::debug("Getting markup (".($this->isXML?'XML':'HTML')."), final with charset '{$this->charset}'");
                $markup = $this->isXML
                    ? $this->document->saveXML()
                    : $this->document->saveHTML();
                if ($this->isXHTML) {
                    self::debug("Fixing XHTML");
                    $markup = self::markupFixXHTML($markup);
                }
                self::debug("Markup: ".substr($markup, 0, 250));
                return $markup;
            }
        }
    }

    /**
     * markupFixXHTML
     */
    protected static function markupFixXHTML($markup)
    {
        $markup = self::expandEmptyTag('script', $markup);
        $markup = self::expandEmptyTag('select', $markup);
        $markup = self::expandEmptyTag('textarea', $markup);
        return $markup;
    }

    /**
     * debug
     */
    public static function debug($text) {
        phpQuery::debug($text);
    }

    /**
     * expandEmptyTag
     *
     * @param $tag
     * @param $xml
     * @return unknown_type
     * @author mjaque at ilkebenson dot com
     * @link http://php.net/manual/en/domdocument.savehtml.php#81256
     */
    public static function expandEmptyTag($tag, $xml)
    {
        $indice = 0;
        while ($indice< strlen($xml)) {
            $pos = strpos($xml, "<$tag ", $indice);
            if ($pos) {
                $posCierre = strpos($xml, ">", $pos);
                if ($xml[$posCierre-1] == "/") {
                    $xml = substr_replace($xml, "></$tag>", $posCierre-1, 2);
                }
                $indice = $posCierre;
            } else {
                break;
            }
        }
        return $xml;
    }
}
