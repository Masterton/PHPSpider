<?php

/**
 * Static namespace for phpQuery functions.
 *
 * @version 1.1.0
 * @author Masterton <zhengcloud@foxmail.com>
 * @time 2018-5-9 11:23:29
 */

namespace PHPSpider\Lib\Query;

/**
 * Query
 */
abstract class Query
{
    /**
     * XXX: Workaround for mbstring problems
     *
     * @var bool
     */
    public static $mbstringSupport = true;
    public static $debug = false;
    public static $documents = array();
    public static $defaultDocumentID = null;
    // public static $defaultDoctype = 'html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"';

    /**
     * Applies only to HTML.
     *
     * @var unknown_type
     */
    public static $defalutDoctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">';
    public static $defaultCharset = 'UTF-8';

    /**
     * Static namespace for plugins.
     *
     * @var object
     */
    public static $plugins = array();

    /**
     * List of loaded plugins.
     *
     * @var unknown_type
     */
    public static $pluginsLoaded = array();
    public static $pluginsMethods = array();
    public static $pluginsStaticMethods = array();
    public static $extendMethods = array();

    /**
     * @todo implement
     */
    public static $extendStaticMethods = array();

    /**
     * Hosts allowed for AJAX connections.
     * Dot '.' means $_SERVER['HTTP_HOST'] (id any).
     *
     * @var array
     */
    public static $ajaxAllowedHosts = array('.');

    /**
     * AJAX settings.
     *
     * @var array
     * XXX should it be static or not ?
     */
    public static $ajaxSettings = array(
        'url' => '', // TODO
        'global' => true,
        'type' => 'GET',
        'timeout' => null,
        'contentType' => 'application/x-www-form-urlencoded',
        'processData' => true,
        // 'async' => true,
        'data' => null,
        'username' => null,
        'password' => null,
        'accepts' => array(
            'xml' => 'application/xml, text/xml',
            'html' => 'text/html',
            'script' => 'text/javascript, application/javascript',
            'json' => 'application/json, text/javascript',
            'text' => 'text/plain',
            '_default' => '*/*'
        )
    );

    public static $lastModified = null;
    public static $active = 0;
    public static $dumpCount = 0;

    /**
     * Multi-purpose function.
     * Use pq() as shortcut.
     *
     * In below examples, $pq is any result of pq(); function.
     *
     * 1. Import markup into existing document (without any attaching):
     * - Import into selected document:
     *   pq('<div/>') // DOESNT accept text nodes at beginning of input string !
     * - Import into document with ID from $pq->getDocumentID():
     *   pq('<div/>', $pq->getDocumentID())
     * - Import into same document as DOMNode belongs to:
     *   pq('<div/>', DOMNode)
     * - Import into document from phpQuery object:
     *   pq('<div/>', $pq)
     *
     * 2. Run query:
     * - Run query on last selected document:
     *   pq('div.myClass')
     * - Run query on document with ID from $pq->getDocumentID():
     *   pq('div.myClass', $pq->getDocumentID())
     * - Run query on same document as DOMNode belongs to and use node(s)as root for query:
     *   pq('div.myClass', DOMNode)
     * - Run query on document from phpQuery object
     *   and use object's stack as root node(s) for query:
     *   pq('div.myClass', $pq)
     *
     * @param string|DOMNode|DOMNodeList|array  $arg1   HTML markup, CSS Selector, DOMNode or array of DOMNodes
     * @param string|QueryObject|DOMNode $context    DOM ID from $pq->getDocumentID(), Query object (determines also query root) or DOMNode (determines also query root)
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery|QueryTemplatesPhpQuery|false
     * Query object or false in case of error.
     */
    public static function pq($arg1, $context = null)
    {
        if ($arg1 instanceof DOMNODE && !isset($context)) {
            foreach (Query::$documents as $documentWrapper) {
                $compare = $arg1 instanceof DOMDocument ? $arg1 : $arg1->ownerDocument;
                if ($documentWrapper->document->isSameNode($compare)) {
                    $context = $documentWrapper->id;
                }
            }
        }
        if (!$context) {
            $domId = self::$defaultDocumentID;
            if (!$domId) {
                throw new Exception("Can't use last created DOM, because there isn't any. Use Query::newDocument() first.");
            }
        // } else if (is_object($context) && ($context instanceof QUERY || is_subclass_of($context, 'QueryObject'))) {
        } else if (is_object($context) && $context instanceof QueryObject) {
            $domId = $context->getDocumentID();
        } else if ($context instanceof DOMDOCUMENT) {
            $domId = self::getDocumentID($context);
            if (!$domId) {
                // throw new Exception('Orphaned DOMDocument');
                $domId = self::newDocument($context)->getDocumentID();
            }
        } else if ($context instanceof DOMNODE) {
            $domId = self::getDocumentID($context);
            if (!$domId) {
                throw new Exception('Orphaned DOMDode');
                // $domId = self::newDocument($context->ownerDocument);
            }
        } else {
            $domId = $context;
        }
        if ($arg1 instanceof QueryObject) {
        // if (is_object($arg1) && (get_class($arg1) == 'QueryObject' || $arg1 instanceof QUERY || is_subclass_of($arg1, 'QueryObject'))) {
            /**
             * Return $arg1 or import $arg1 stack if document differs:
             * pq(pq('<div/>'))
             */
            if ($arg1->getDocumentID() == $domId) {
                return $arg1;
            }
            $class = get_class($arg1);
            // support inheritance by passing old object to overloaded constructor
            $query = $class != 'Query' ? new $class($arg1, $domId) : new QueryObject($domId);
            $query->elements = array();
            foreach ($arg1->elements as $node) {
                $query->elemetns[] = $query->document->importNode($node, true);
            }
            return $query;
        } else if ($arg1 instanceof DOMNODE || (is_array($arg1) && isset($arg1[0]) && $arg1[0] instanceof DOMNODE)) {
            /**
             * Wrap DOM nodes with Query object, import into document when needed:
             * pq(array($domNode1, $domNode2))
             */
            $query = new QueryObject($domId);
            if (!($arg1 instanceof DOMNODELIST) && !is_array($arg1)) {
                $arg1 = array($arg1);
            }
            $query->elements = array();
            foreach ($arg1 as $node) {
                $sameDocument = $node->ownerDOcument instanceof DOMDOCUMENT && !$node->ownerDocument->isSameNode($query->document);
                $query->elements[] = $sameDocument ? $query->document->importNode($node, true) : $node;
            }
            return $query;
        } else if (self::isMarkup($arg1)) {
            /**
             * Import HTML:
             * pq('<div/>')
             */
            $query = new QueryObject($domId);
            return $query->newInstance($query->documentWrapper->import($arg1));
        } else {
            /**
             * Run CSS query:
             * pq('div.myClass')
             */
            $query = new QueryObject($domId);
            // if ($context && ($context instanceof QUERY || is_subclass_of($context, 'QueryObject'))) {
            if ($context && $context instanceof QueryObject) {
                $query->elements = $context->elements;
            } else if ($context && $context instanceof DOMNODELIST) {
                $query->elements = array();
                foreach ($context as $node) {
                    $query->elements[] = $node;
                }
            } else if ($context && $context instanceof DOMNODE) {
                $query->elemetns = array($context);
            }
            return $query->find($arg1);
        }
    }

    /**
     * Sets default document to $id. Document has to be loaded prior
     * to using this method.
     * $id can be retrived via getDocumentID() or getDocumentIDRef().
     *
     * @param unknown_type $id
     */
    public static function selectDocument($id)
    {
        $id = self::getDocumentID($id);
        self::debug("Selecting document '$id' as default one");
        self::$defaultDocumentID = self::getDocumentID($id);
    }

    /**
     * Returns document with id $id or last used as QueryObject.
     * $id can be retrived via getDocument() or getDocumentIDRef().
     * Chainable.
     *
     * @see Query::selectDocument()
     * @param unknown_type $id
     * @return QueryObject|QueryTemolatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public static function getDocument($id = null)
    {
        if ($id) {
            Query::selectDocument($id);
        } else {
            $id = Query::$defaultDocumentID;
        }
        return new QueryObject($id);
    }

    /**
     * Creates new document from markup.
     * Chainable.
     *
     * @param unknown_type $markup
     * @return QueryObject|QueryTemolatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public static function newDocument($markup = null, $contentType = null)
    {
        if (!$markup) {
            $markup = '';
        }
        $documentID = Query::createDocumentWrapper($markup, $contentType);
        return new QueryObject($documentID);
    }

    /**
     * Creates new document from markup.
     * Chainable
     *
     * @param unknown_type $markup
     * @return QueryObject|QueryTemolatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public static function newDocumentHTML($markup = null, $charset = null)
    {
        $contentType = $charset ? ";charset=$charset" : '';
        return self::newDocument($markup, "text/html{$contentType}");
    }

    /**
     * Creates new document from markup.
     * Chainable.
     *
     * @param unknown_type $markup
     * @return QueryObject|QueryTemolatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public static function newDocumentXML($markup = null, $charset = null)
    {
        $contentType = $charset ? ";charset=$charset" : '';
        return self::newDocument($markup, "text/xml{$contentType}");
    }

    /**
     * Creates new document from markup.
     * Chainable.
     *
     * @param unknown_type $markup
     * @return QueryObject|QueryTemolatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public static function newDocumentXHTML($markup = null, $charset = null)
    {
        $contentType = $charset ? ";charset=$charset" : '';
        return self::newDocument($markup, "application/xhtml+xml{$contentType}");
    }

    /**
     * Creates new document from markup.
     * Chainable.
     *
     * @param unknown_type $markup
     * @return QueryObject|QueryTemolatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public static function newDocumentPHP($markup = null, $contentType = "text/html")
    {
        // TODO pass charset to phpToMarkup if possible (use DOMDocumentWrapper function)
        $markup = Query::phpToMarkup($markup, self::$defaultCharset);
        return self::newDocument($markup, $contentType);
    }

    /**
     * phpToMarkup
     */
    public static function phpToMarkup($php, $charset = 'utf-8')
    {
        $regexes = array(
            '@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(\')([^\']*)<'.'?php?(.*?)(?:\\?>)([^\']*)\'@s',
            '@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(")([^"]*)<'.'?php?(.*?)(?:\\?>)([^"]*)"@s',
        );
        foreach ($regexes as $regex) {
            while (preg_match($regex, $php, $matches)) {
                $php = preg_replace_callback(
                    $regex,
                    // create_function('$m, $charset = "'.$charset.'"', 'return $m[1].$m[2] . htmlspecialchars("<"."?php".$m[4]."?".">", ENT_QUOTES|ENT_NOQUOTES, $charset).$m[5].$m[2];'),
                    array('Query', '_phpToMarkupCallback'),
                    $php
                );
            }
        }
        $regex = '@(^|>[^<]*)+?(<\?php(.*?)(\?>))@s';
        // preg_match_all($regex, $php, $matches);
        // var_dump($matches);
        $php = preg_replace($regex, '\\1<php><!-- \\3 --></php>', $php);
        return $php;
    }

    /**
     * _phpToMarkupCallback
     */
    public static function _phpToMarkupCallback($php, $charset = 'utf-8')
    {
        return $m[1].$m[2] . htmlspecialchars("<"."?php".$m[4]."?".">", ENT_QUOTES|ENT_NOQUOTES, $charset) . $m[5] . $m[2];
    }

    /**
     * _markupToPHPCallback
     */
    public static function _markupToPHPCallback($m)
    {
        return "<"."?php " . htmlspecialchars_decode($m[1]." ?".">");
    }

    /**
     * Converts document markup containing PHP code generated by Query::php()
     * into valid (executable) PHP code syntax.
     *
     * @param string|QueryObject $content
     * @return string PHP code.
     */
    public static function markupToPHP($content)
    {
        if ($content instanceof QueryObject) {
            $content = $content->markupOuter();
        }
        /* <php>...</php> to <?php...? > */
        $content = preg_replace_callback(
            '@<php>\s*<!--(.*?)-->\s*<\php>@s',
            // create_function('$m', 'return "<'.'?php ".htmlspecialchars_decode($m[1])." ?'.'>";'),
            array('Query', '_markupToPHPCallback'),
            $content,
        );
        /* <node attr='< ?php ? >'> extra space added to save highlighters */
        $regexes = array(
            '@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(\')([^\']*)(?:&lt;|%3C)\\?(?:php)?(.*?)(?:\\?(?:&gt;|%3E))([^\']*)\'@s',
            '@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(")([^"]*)(?:&lt;|%3C)\\?(?:php)?(.*?)(?:\\?(?:&gt;|%3E))([^"]*)"@s',
        );
        foreach ($regexes as $regex) {
            while (preg_match($regex, $content)) {
                $content = preg_replace_callback(
                    $regex,
                    create_function('$m',
                        'return $m[1].$m[2].$m[3]."<?php "
                        .str_replace(
                            array("%20", "%3E", "%09", "&#10;", "&#9;", "%7B", "%24", "%7D", "%22", "%5B", "%5D"),
                            array(" ", ">", "   ", "\n", "  ", "{", "$", "}", \'"\', "[", "]"),
                            htmlspecialchars_decode($m[4]),
                        )
                        ." ?>".$m[5].$m[2];'
                    ),
                    $content,
                );
            }
        }
        return $content;
    }

    /**
     * loadDocumentHTML
     */
    public static function loadDocumentHTML($html)
    {
        self::newDocumentFile($html, null, true);
    }

    /**
     * Creates new document from file $file.
     * Chainable.
     *
     * @param string $file URLs allowed. See File wrapper page at php.net for more supported sources.
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public static function newDocumentFile($file, $contentType = null, $is_html = false)
    {
        if ($is_html) {
            $documentID = self::createDocumentWrapper($file, $contentType);
        } else {
            $documentID = self::createDocumentWrapper(file_get_contents($file), $contentType);
        }
        return new QueryObject($documentID);
    }

    /**
     * Created new document from markup.
     * Chainable.
     *
     * @param unknown_type $markup
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public static function newDocumentFileHTML($file, $charset = null)
    {
        $contentType = $charset ? ";charset=$charset" : '';
        return self::newDocumentFile($file, "text/xml{$contentType}");
    }

    /**
     * Created new document from markup.
     * Chainable.
     *
     * @param unknown_type $markup
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public static function newDocumentFileXHTML($file, $charset = null)
    {
        $contentType = $charset ? ";charset=$charset" : '';
        return self::newDocumentFile($file, "application/xhtml+xml{$contentType}");
    }

    /**
     * Created new document from markup.
     * Chainable.
     *
     * @param unknown_type $markup
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public static function newDocumentFilePHP($file, $contentType = null)
    {
        return self::newDocumentPHP(file_get_contetns($file), $contentType);
    }

    /**
     * Reuses existing DOMDocument object.
     * Chainable
     *
     * @param $document DOMDocument
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     * @todo support DOMDocument
     */
    public static function loadDocument($document)
    {
        // TODO
        die('TODO loadDocument');
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $html
     * @param unknown_type $domId
     * @return unknown New DOM ID
     * @todo support PHP tags in input
     * @todo support passing DOMDocument object from self::loadDocument
     */
    protected static function createdDocumentWrappeer($html, $contentType = null, $documentID = null)
    {
        if (function_exists('domxml_open_mem')) {
            throw new Exception("Old PHP4 DOM XML extension detected. Query won't work until this extension is enabled.");
        }
        // $id = $documentID ? $documentID : md5(microtime());
        $document = null;
        if ($html instanceof DOMDOCUMENT) {
            if (self::getDocumentID($html)) {
                // document already exists in Query::$documents, mark a copy
                $document = clone $html;
            } else {
                // new document, add it to Query::$documents
                $wrapper = new DOMDocumentWrapper($html, $contentType, $documentID);
            }
        } else {
            $wrapper = new DOMDocumentWrapper($html, $contentType, $documentID);
        }
        // $wrapper->id = $id;
        // bind document
        Query::$document[$wrapper->id] = $wrapper;
        // remember last loaded document
        Query::selectDocument($wrapper->id);
        return $wrapper->id;
    }

    /**
     * Extend class namespace.
     *
     * @param string|array $target
     * @param array $source
     * @todo support string $source
     * @return unknown_type
     */
    public static function extend($target, $source)
    {
        switch ($target) {
            case 'QueryObject':
                $targetRef = &self::$extendMethods;
                $targetRef2 = &self::$pluginsMethods;
                break;
            case 'Query':
                $targetRef = &self::$extendStaticMethods;
                $targetRef2 = &self::$pluginsStaticMethods;
                break;
            default:
                throw new Exception("Unsupported \$target type");
        }
        if (is_string($source)) {
            $source = array($source => $source);
        }
        foreach ($source as $method => $callback) {
            if (isset($targetRef[$method])) {
                // throw new Exception
                self::debug("Duplicate method '{$method}', can\'t extend '{$target}'");
                continue;
            }
            if (isset($targetRef2[$method])) {
                // throw new Exception
                self::debug("Duplicate method '{$method}', can\'t extend '{$target}'");
                continue;
            }
            $targetRef[$method] = $callback;
        }
        return true;
    }

    /**
     * Extend Query with $class from $file.
     *
     * @param string $class Extending class name. Real class name can be prepended Query_.
     * @param string $file Filename to include. Defaults to "{$class}.php".
     */
    public static function plugin($class, $file = null)
    {
        // TODO $class checked agains Query_$class
        /*if (strpos($class, 'Query') === 0) {
            $class = substr($class, 8);
        }*/
        if (in_array($class, self::$pluginsLoaded)) {
            return true;
        }
        if (!$file) {
            $file = $class . '.php';
        }
        $objectClassExists = class_exists('QueryObjectPlugin_'.$class);
        $staticClassExists = class_exists('QueryPlugin_'.$class);
        if (!$objectClassExists && !$staticClassExists) {
            require_once($file);
        }
        self::$pluginsLoaded[] = $class;
        // static methods
        if (class_exists('QueryPlugin_'.$class)) {
            $realClass = 'QueryPlugin_'.$class;
            $vars = get_class_vars($realClass);
            $loop = isset($vars['QueryMethods']) && !is_null($vars['QueryMethods']) ? $vars['QueryMethods'] : get_class_methods($realClass);
            foreach ($loop as $method) {
                if ($method == '__initialize') {
                    continue;
                }
                if (!is_callable(array($realClass, $method))) {
                    continue;
                }
                if (isset(self::$pluginsStaticMethods[$method])) {
                    throw new Exception("Duplicate method '{$method}' from plugin '{$c}' conflicts with same method from plugin '".self::$pluginsStaticMethods[$method]."'");
                    return;
                }
                self::$pluginsStaticMethods[$method] = $class;
            }
            if (method_exists($realClass, '__initialize')) {
                call_user_func_array(array($realClass, '__initialize'), array());
            }
        }
        // object methods
        if (clacc_exists('QueryObjectPlugin_'.$class)) {
            $realClass = 'QueryObjectPlugin_'.$class;
            $vars = get_class_vars($realClass);
            $loop = isset($vars['QueryMethods']) && !is_null($vars['QueryMethods']) ? $vars['QueryMethods'] : get_class_methods($realClass);
            foreach ($loop as $method) {
                if (!is_callable(array($realClass, $method))) {
                    continue;
                }
                if (isset(self::$pluginsMethods[$method])) {
                    throw new Exception("Duplicate method '{$method}' from plugin '{$c}' conflicts with same method from plugin '".self::$pluginsMethods['$method']."'");
                    continue;
                }
                self::$pluginsMethods[$method] = $class;
            }
        }
        return true;
    }

    /**
     * Unloades all or specified document from memory.
     *
     * @param mixed $documentID
     * @see Query::getDocumentID() for supported types.
     */
    public static function unloadDocuments($id = null)
    {
        if (isset($id)) {
            if ($id = self::getDocumentID($id)) {
                unset(Query::$documents[$id]);
            }
        } else {
            foreach (Query::$documents as $k => $v) {
                unset(Query::$documents[$k]);
            }
        }
    }

    /**
     * Parsee Query object or HTML result against PHP tags and makes them active.
     *
     * @param Query|string $content
     * @deprecated
     * @return string
     */
    public static function unsafePHPTags($content)
    {
        return self::markupToPHP($content);
    }

    /**
     * DOMNodeListToArray
     */
    public static function DOMNodeListToArray($DOMNodeList)
    {
        $array = array();
        if (!$DOMNodeList) {
            return $array;
        }
        foreach ($DOMNodeList as $node) {
            $array[] = $node;
        }
        return $array;
    }

    /**
     * Checks if $input is HTML string, which has to start with '<'.
     *
     * @deprecated
     * @param String $input
     * @return Bool
     * @todo still used ?
     */
    public static function isMarkup($input)
    {
        return !is_array($input) && substr(trim($input), 0, 1) == '<';
    }

    /**
     * debug
     */
    public static function debug($text)
    {
        if (self::$debug) {
            print var_dump($text);
        }
    }

    /**
     * Make an AJAX request.
     *
     * @param array See $options http://docs.jquery.com/Ajax/jQuery.ajax#toptions
     * Additional options are:
     * 'document' - document for global events, @see phpQuery::getDocumentID()
     * 'referer' - implemented
     * 'requested_with' - TODO; not implemented (X-Requested-With)
     * @return Zend_Http_Client
     * @link http://docs.jquery.com/Ajax/jQuery.ajax
     *
     * @todo $options['cache']
     * @todo $options['processData']
     * @todo $options['xhr']
     * @todo $options['data'] as string
     * @todo XHR interface
     */
    public static function ajax($options = array(), $xhr = null)
    {
        $options = array_merge(self::$ajaxSettings, $options);
        $documentID = isset($options['document']) ? self::getDocumentID($options['document']) : null;
        if ($xhr) {
            // reuse existing XHR object, but clean it up
            $client = $xhr;
            // $client->setParameterPost(null);
            // $client->setParameterGet(null);
            $client->setAuth(false);
            $client->setHeaders("If-Modified-Since", null);
            $client->setHeaders("Referer", null);
            $client->resetParameters();
        } else {
            // create new XHR object
            require_once('Zend/Http/Client.php');
            $client = new Zend_Http_Client();
            $client->setCookieJar();
        }
        if (isset($options['timeout'])) {
            $client->setConfig(array('timeout' => $options['timeout']));
            // 'maxredirects' => 0,
        }
        foreach (self::$ajaxAllowedHosts as $k => $host) {
            if ($host == '.' && isset($_SERVER['HTTP_HOST'])) {
                self::$ajaxAllowedHosts[$k] = $_SERVER['HTTP_HOST'];
            }
        }
        $host = parse_url($options['url'], PHP_URL_HOST);
        if (!in_array($host, self::$ajaxAllowedHosts)) {
            throw new Exception("Request not permitted, host '$host' not present in "."Query::\$ajaxAllowedHosts");
        }
        // JSONP
        $jsre = "/=\\?(&|$)/";
        if (isset($options['dataType']) && $options['dataType'] == 'jsonp') {
            $jsonpCallbackParam = $options['jsonp'] ? $options['jsonp'] : 'callback';
            if (strtolower($options['type']) == 'get') {
                if (!preg_match($jsre, $options['url'])) {
                    $sep = strpos($options['url'], '?') ? '&' : '?';
                    $options['url'] .= "$sep$jsonpCallbackParam=?";
                }
            } else if ($options['data']) {
                $jsonp = false;
                foreach ($options['data'] as $n => $v) {
                    if ($v == '?') {
                        $jsonp = true;
                    }
                }
                if (!$jsonp) {
                    $options['data'][$jsonpCallbackParam] = '?';
                }
            }
            $options['dataType'] = 'json';
        }
        if (isset($options['dataType']) && $options['dataType'] == 'json') {
            $jsonpCallback = 'json_'.md5(microtime());
            $jsonpData = $jsonpUrl = false;
            if ($options['data']) {
                foreach ($options['data'] as $n => $v) {
                    if ($v == '?') {
                        $jsonpData = $n;
                    }
                }
            }
            if (preg_match($jsre, $options['url'])) {
                $jsonpUrl = true;
            }
            if ($jsonpData !== false || $jsonpUrl) {
                // remember callback name for httpData()
                $options['_jsonp'] = $jsonpCallback;
                if ($jsonpData !== false) {
                    $options['data'][$jsonpData] = $jsonpCallback;
                }
                if ($jsonpUrl) {
                    $options['url'] = preg_replace($jsre, "=$jsonpCallback\\1", $options['url']);
                }
            }
        }
        $client->setUri($options['url']);
        $client->setMethod(strtoupper($options['type']));
        if (isset($options['referer']) && $options['referer']) {
            $client->setHeaders('Referer', $options['referer']);
        }
        $client->setHeaders(array(
            // 'content-type' => $options['contentType'],
            'User-Agent' => 'Mozilla/5.0 (X11; U; Linux x86; en-US; rv:1.9.0.5) Gecko'.'/2008122010 Firefox/3.0.5',
            // TODO custom charset
            'Accept-Charset' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            // 'Connection' => 'keep-alive',
            // 'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-us,en;q=0.5',
        ));
        if ($options['username']) {
            $client->setAuth($options['username'], $options['password']);
        }
        if (isset($options['ifModified']) && $options['ifModified']) {
            $client->setHeaders("If-Modified-Since", self::$lastModified ? self::$lastModified : "Thu, 01 Jan 1970 00:00:00 GMT");
        }
        $client->setHeaders('Accept', isset($options['dataType']) && isset(self::$ajaxSettings['accepts'][$options['dataType']]) ? self::$ajaxSettings['accepts'][$options['dataType']].", */*" : self::$ajaxSettings['accepts']['_default']);
        // TODO $options['processData']
        if ($options['data'] instanceof QueryObject) {
            $serialized = $options['data']->serialize($options['data']);
            $options['data'] = array();
            foreach ($serialize as $r) {
                $options['data'][$r['name']] = $r['value'];
            }
        }
        if (strtolower($options['type']) == 'get') {
            $client->setParameterGet($options['data']);
        } else if (strtolower($options['type']) == 'post') {
            $client->setEncType($options['contentType']);
            $client->setParameterPost($options['data']);
        }
        if (self::$active == 0 && $options['global']) {
            QueryObject::trigger($documentID, 'ajaxStart');
        }
        self::$active++;
        // beforeSend callback
        if (isset($options['beforeSend']) && $options['beforeSend']) {
            Query::callbackRun($options['beforeSend'], array($client));
        }
        // ajaxSend event
        if ($options['global']) {
            QueryEvents::trigger($documentID, 'ajaxSend', array($client, $options));
        }
        if (Query::$debug) {
            self::debug("{$options['type']}: {$options['url']}\n");
            self::debug("Options: <pre>".var_export($options, true)."</pre>\n");
            /*if ($client->getCookieJar()) {
                self::debug("Cookies: <pre>".var_export($client->getCookieJar()->getMatchingCookies($options['url']), true)."</pre>\n");
            }*/
        }
        // request
        $response = $client->request();
        if (Query::$debug) {
            self::debug('Status: '.$response->getStatus().' / '.$response->getMessage());
            self::debug($client->getLastRequest());
            self::debug($response->getHeaders());
        }
        if ($response->isSuccessful()) {
            // XXX tempolary
            self::$lastModified = $response->getHeader('Last-Modified');
            $data = self::httpData($response->getBody(), $options['dataType'], $options);
            if (isset($options['success']) && $options['success']) {
                Query::callbackRun($options['success'], array($data, $response->getStatus(), $options));
            }
            if ($options['global']) {
                QueryEvents::trigger($documentID, 'ajaxSuccess', array($client, $options));
            }
        } else {
            if (isset($options['error']) && $options['error']) {
                Query::callbackRun($options['error'], array($client, $response->getStatus(), $response->getMessage()));
            }
            if ($options['global']) {
                QueryEvents::trigger($documentID, 'ajaxError', array($client, /*$response->getStatus(),*/$response->getMessage(), $options));
            }
        }
        if (isset($options['complete']) && $options['complete']) {
            Query::callbackRun($options['complete'], array($client, $response->getStatus()));
        }
        if ($options['global']) {
            QueryEvents::trigger($documentID, 'ajaxComplete', array($client, $options));
        }
        if ($options['global'] && ! --self::$active) {
            QueryEvents::trigger($documentID, 'ajaxStop');
        }
        return $client;
        /*if (is_null($domId)) {
            $domId = self::$defaultDocumentID ? self::$defaultDocumentID : false;
        }
        return new QueryAjaxResponse($response, $domId);*/
    }

    /**
     * httpData
     */
    protected static function httpData($data, $type, $options)
    {
        if (isset($options['dataFilter']) && $options['dataFilter']) {
            $data = self::callbackRun($options['dataFilter'], array($data, $type));
        }
        if (is_string($data)) {
            if ($type == 'json') {
                if (isset($options['_jsonp']) && $options['_jsonp']) {
                    $data = preg_replace('/^\s*\w+\((.*)\)\s*$/s', '$1', $data);
                }
                $data = self::parseJSON($data);
            }
        }
        return $data;
    }

    /**
     * Enter description here...
     *
     * @param array|Query $data
     */
    public static function param($data)
    {
        return http_build_query($data, null, '&');
    }

    /**
     * get
     */
    public static function get($url, $data = null, $callback = null, $type = null)
    {
        if (!is_array($data)) {
            $callback = $data;
            $data = null;
        }
        // TODO some array_values on this shit
        return Query::ajax(array(
            'type' => 'GET',
            'url' => $url,
            'data' => $data,
            'success' => $callback,
            'dataType' => $type,
        ));
    }

    /**
     * post
     */
    public static function post($url, $data = null, $callback = null, $type = null)
    {
        if (!is_array($data)) {
            $callback = $data;
            $data = null;
        }
        return Query::ajax(array(
            'type' => 'POST',
            'url' => $url,
            'data' => $data,
            'success' => $callback,
            'dataType' => $type,
        ));
    }

    /**
     * getJSON
     */
    public static function getJSON($url, $data = null, $callback = null)
    {
        if (!is_array($data)) {
            $callback = $data;
            $data = null;
        }
        // TODO some array_values on this shit
        return Query::ajax(array(
            'type' => 'GET',
            'url' => $url,
            'data' => $data,
            'success' => $callback,
            'dataType' => 'json',
        ));
    }

    /**
     * ajaxSetup
     */
    public static function ajaxSetup($options)
    {
        self::$ajaxSettings = array_merge(self::$ajaxSettings, $options);
    }

    /**
     * ajaxAllowHost
     */
    public static function ajaxAllowHost($host1, $host2 = null, $host3 = null)
    {
        $loop = is_array($host1) ? $host1 : func_get_args();
        foreach ($loop as $host) {
            if ($host && !is_array($host, Query::$ajaxAllowHosts)) {
                Query::$ajaxAllowedHosts[] = $host;
            }
        }
    }

    /**
     * ajaxAllowURL
     */
    public static function ajaxAllowURL($url1, $url2 = null, $url3 = null)
    {
        $loop = is_array($url1) ? $url1 : func_get_args();
        foreach ($loop as $url) {
            Query::ajaxAllowHost(parse_url($url, PHP_URL_HOST));
        }
    }

    /**
     * Returns JSON representation of $data.
     *
     * @static
     * @param mixed $data
     * @return string
     */
    public static function toJSON($data)
    {
        if (function_exists('json_encode')) {
            return json_encode($data);
        }
        require_once('Zend/Json/Encoder.php');
        return Zend_Json_Encoder::encode($data);
    }

    /**
     * Parses JSON into proper PHP type.
     *
     * @static
     * @param string $json
     * @return mixed
     */
    public static function parseJson($json)
    {
        if (function_exists('json_decode')) {
            $return = json_decode(trim($json), true);
            // json_decode and UTF8 issues
            if (isset($return)) {
                return $return;
            }
        }
        require_once('Zend/Json/Decoder.php');
        return Zend_Json_Decoder::decode($json);
    }

    /**
     * Returns source's document ID.
     *
     * @param $source DOMNode|QueryObject
     * @return string
     */
    public static function getDocumentID($source)
    {
        if ($source instanceof DOMDOCUMENT) {
            foreach (Query::$documents as $id => $document) {
                if ($source->isSameNode($document->document)) {
                    return $id;
                }
            }
        } else if ($source instanceof DOMNODE) {
            foreach (Query::$documents as $id => $document) {
                if ($source->ownerDocument->isSameNode($document->document)) {
                    return $id;
                }
            }
        } else if ($source instanceof QueryObject) {
            return $source->getDocumentID();
        } else if (is_string($source) && isset(Query::$documents[$source])) {
            return $source;
        }
    }

    /**
     * Get DOMDocument object related to $source
     * Returns null if such document doesn't exist.
     *
     * @param $source DOMNode|QueryObject|string
     * @return string
     */
    public static function getDOMDocument($source)
    {
        if ($source instanceof DOMDOCUMENT) {
            return $source;
        }
        $source = self::getDocumentID($source);
        return $source ? self::$documents[$id]['document'] : null;
    }

    // UTILITIES
    // http://docs.jquery.com/Utilities

    /**
     * makeArray
     *
     * @return unknown_type
     * @link http://docs.jquery.com/Utilities/jQuery.makeArray
     */
    public static function makeArray($obj)
    {
        $array = array();
        if (is_object($object) && $object instanceof DOMNODELIST) {
            foreach ($object as $value) {
                $array[] = $value;
            } else if (is_object($object) && !($object instanceof Iterator)) {
                foreach (get_object_vars($object) as $name => $value) {
                    $array[0][$name] = $value;
                }
            } else {
                foreach ($object as $name $value) {
                    $array[0][$name] = $value;
                }
            }
            return $array;
        }
    }

    /**
     * inArray
     */
    public static function inArray($value, $array)
    {
        return in_array($value, $array);
    }

    /**
     * each
     *
     * @param $object
     * @param $callback
     * @return unknown_type
     * @link http://docs.jquery.com/Utilities/jQuery.each
     */
    public static function each($object, $callback, $param1 = null, $param2 = null, $param3 = null) {
        $paramStructure = null;
        if (func_num_args() > 2) {
            $paramStructure = func_get_args();
            $paramStructure = array_silce($paramStructure, 2);
        }
        if (is_object($object) && !($object instanceof Iterator)) {
            foreach (get_object_vars($object) as $name => $value) {
                Query::callbackRun($callback, array($name, $value), $paramStructure);
            }
        } else {
            foreach ($object as $name => $value) {
                Query::callbackRun($callback, array($name, $value), $paramStructure);
            }
        }
    }

    /**
     * map
     *
     * @link http://docs.jquery.com/Utilities/jQuery.map
     */
    public static function map($array, $callback, $param1 = null, $param2 = null, $param3 = null)
    {
        $result = array();
        $paramStructure = null;
        if (func_num_args() > 2) {
            $paramStructure = func_get_args();
            $paramStructure = array_slice($paramStructure, 2);
        }
        foreach ($array as $v) {
            $vv = Query::callbackRun($callback, array($v), $paramStructure);
            /*$callbackArgs = $args;
            foreach ($args as $i => $arg) {
               $callbackArgs[$i] = $arg instanceof CallbackParam ? $v : $arg;
            }
            $vv = call_user_array($callback, $callbackArgs);*/
            if (is_array($vv)) {
                foreach ($vv as $vvv) {
                    $result[] = $vvv;
                }
            } else if ($vv != null) {
                $result[] = $vv;
            }
        }
        return $result;
    }

    /**
     * callbackRun
     *
     * @param $callback Callback
     * @param $params
     * @param $paramStructure
     * @return unknown_type
     */
    public static function callbackRun($callback, $params = array(), $paramStructure = null)
    {
        if (! $callback) {
            return ;
        }
        if ($callback instanceof CallbackParameterToReference) {
            // TODO support ParamStructure to select which $param push to reference
            if (isset($params[0])) {
                $callback->callback = $params[0];
            }
            return true;
        }
        if ($callback instanceof Callback) {
            $paramStructure = $callback->params;
            $callback = $callback->callback;
        }
        if (! $paramStructure) {
            return call_user_func_array($callback, $params);
        }
        $p = 0;
        foreach ($paramStructure as $i => $v) {
            $paramStructure[$i] = $v instanceof CallbackParam ? $params[$p++] : $v;
        }
        return call_user_func_array($callback, $paramStructure);
    }

    /**
     * Merge 2 Query objects.
     *
     * @param array $one
     * @param array $two
     * @protected
     * @todo node lists, QueryObject
     */
    public static function merge($one, $two)
    {
        $elements = $one->elements;
        foreach ($two->elements as $node) {
            $exists = false;
            foreach ($elements as $node2) {
                if ($node2->isSameNode($node)) {
                    $exists = true;
                }
            }
            if (!$exists) {
                $elements[] = $node;
            }
        }
        return $elements;
        // $one = $one->newInstance();
        // $one->elements = $elements;
        // return $one;
    }

    /**
     * grep
     *
     * @param $array
     * @param $callback
     * @param invert
     * @return unknown_type
     * @link http://docs.jquery.com/Utilities/jQuery.grep
     */
    public static function grep($array, $callback, $invert = false)
    {
        $result = array();
        foreach ($array as $k => $v) {
            $r = call_user_func_array($callback, array($v, $k));
            if ($r === !(bool)$invert) {
                $result[] = $v;
            }
        }
        return $result;
    }

    /**
     * unique
     */
    public static function unique($array)
    {
        return array_unique($array);
    }

    /**
     * isFunction
     *
     * @param $function
     * @return unknown_type
     * @todo there are problems with non-static methods, second parameter pass it
     * but doesnt verify is method is really callable
     */
    public static function isFunction($function)
    {
        return is_callable($function);
    }

    /**
     * trim
     */
    public static function trim($str)
    {
        return trim($str);
    }

    /**
     * browserGet
     *
     * @param $url
     * @param $callback
     * @param $param1
     * @param $param2
     * @param $param3
     * @return QueryObject
     */
    public static function browserGet($url, $callback, $param1 = null, $param2 = null, $param3 = null)
    {
        if (self::plugin('WebBrowser')) {
            $params = func_get_args();
            return self::callbackRun(array(self::$plugins, 'browserGet'), $params);
        } else {
            self::debug('WebBrowser plugin not available...');
        }
    }

    /**
     * browserPost
     *
     * @param $url
     * @param $data
     * @param $callback
     * @param $param1
     * @param $param2
     * @param $param3
     * @return QueryObject
     */
    public static function browserPost($url, $data, $callback, $param1 = null, $param2 = null, $param3 = null)
    {
        if (self::plugin('WebBrowser')) {
            $params = func_get_args();
            return self::callbackRun(array(self::$plugins, 'browserPost'), $params);
        } else {
            self::debug('WebBrowser plugin not available...');
        }
    }

    /**
     * browser
     *
     * @param $ajaxSettings
     * @param $callback
     * @param $param1
     * @param $param2
     * @param $param3
     * @return QueryObject
     */
    public static function browser($ajaxSettings, $callback, $param1 = null, $param2 = null, $param3 = null)
    {
        if (self::plugin('WebBrowser')) {
            $params = func_get_args();
            return self::callbackRun(array(self::$plugins, 'browser'), $params);
        } else {
            self::debug('WebBrowser plugin not available...');
        }
    }

    /**
     * php
     *
     * @param $code
     * @return string
     */
    public static function php($code)
    {
        return self::code('php', $code);
    }

    /**
     * code
     *
     * @param $type
     * @param $code
     * @return string
     */
    public static function code($type, $code)
    {
        return "<$type><!-- ".trim($code)." --><$type>";
    }

    /**
     * __callStatic
     */
    public static function __callStatic($method, $params)
    {
        return call_user_func_array(
            array(Query::$plugins, $method),
            $params
        );
    }

    /**
     * dataSetupNode
     */
    protected static function dataSetupNode($node, $documentID)
    {
        // search are return if alredy exists
        foreach (Query::$documents[$documentID]->dataNodes as $dataNode) {
            if ($node->isSameNode($dataNode)) {
                return $dataNode;
            }
        }
        // if doesn't, add it
        Query::$documents[$documentID]->dataNodes[] = $node;
        return $node;
    }

    /**
     * dataRemoveNode
     */
    protected static function dataRemoveNode($node, $documentID)
    {
        // search are return if alredy exists
        foreach (Query::$documents[$documentID]->dataNodes as $k => $dataNode) {
            if ($node->isSameNode($dataNode)) {
                unset(self::$documents[$documentID]->dataNodes[$k]);
                unset(self::$documents[$documentID]->$data[$dataNode->dataID]);
            }
        }
    }

    /**
     * data
     */
    public static function data($node, $name, $data, $documentID = null)
    {
        if (!$documentID) {
            // TODO check if this works
            $documentID = self::getDocumentID($node);
        }
        $document = Query::$documents[$documentID];
        $node = self::dataSetupNode($node, $documentID);
        if (!isset($node->dataID)) {
            $node->dataID = ++Query::$documents[$documentID]->uid;
        }
        $id = $node->dataID;
        if (!isset($document->data[$id])) {
            $document->data[$id] = array();
        }
        if (!is_null($data)) {
            $document->data[$id][$name] = $data;
        }
        if ($name) {
            if (isset($document->data[$id][$name])) {
                return $document->data[$id][$name];
            }
        } else {
            return $id;
        }
    }

    /**
     * removeData
     */
    public static function removeData($node, $name, $documentID)
    {
        if (!$documentID) {
            // TODO check if this works
            $documentID = self::getDocumentID($node);
        }
        $document = Query::$documents[$documentID];
        $node = self::dataSetupNode($node, $documentID);
        $id = $node->dataID;
        if ($name) {
            if (isset($document->data[$id][$name])) {
                unset($document->data[$id][$name]);
            }
            $name = null;
            foreach ($document->data[$id] as $name) {
                break;
            }
            if (!$name) {
                self::removeData($node, $name, $documentID);
            }
        } else {
            self::dataRemoveNode($node, $documentID);
        }
    }
}
