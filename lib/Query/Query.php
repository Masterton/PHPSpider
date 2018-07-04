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
            if (!($arg1 instanceof DOMNODELIST && !is_array($arg1)) {
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
}
