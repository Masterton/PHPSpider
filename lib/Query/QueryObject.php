<?php

/**
 * @version 1.1.0
 * @author Masterton <zhengcloud@foxmail.com>
 * @time 2018-5-9 11:23:29
 */

namespace PHPSpider\Lib\Query;

/**
 * Class representing Query objects.
 *
 * @author Masterton <zhengcloud@foxmail.com>
 * @package Query
 * @method QueryObject clone() clone()
 * @method QueryObject empty() empty()
 * @method QueryObject next() next($selector = null)
 * @method QueryObject prev() prev($selector = null)
 * @property Int $length
 */
class QueryObject implements \Iterator, \Countable, \ArrayAccess
{
    public $documentID = null;

    /**
     * DOMDocument class.
     *
     * @var DOMDocument
     */
    public $document = null;
    public $charset = null;

    /**
     *
     * @var DOMDocumentWrapper
     */
    public $documentWrapper = null;

    /**
     * XPath interface
     *
     * @var DOMXPath
     */
    public $xpath = null;

    /**
     * Stack of selected elements.
     * @TODO refactor to ->nodes
     *
     * @var array
     */
    public $elements = array();

    /**
     *
     * @access private
     */
    protected $elementsBackup = array();

    /**
     *
     * @access private
     */
    protected $previous = null;

    /**
     * @access private
     * @TODO deprecate
     */
    protected $root = array();

    /**
     * Indicated if document is just a fragment (no <html> tag).
     *
     * Event document is realy a full document, so even documentFragments can
     * be queried against <html>, but getDocument(id)->htmlOuter() will return
     * only contents of <body>.
     *
     * @var bool
     */
    public $documentFragment = true;

    /**
     * Iterator interface helper
     *
     * @access private
     */
    protected $elementsInterator = array();

    /**
     * Iterator interface helper
     *
     * @access private
     */
    protected $valid = false;

    /**
     * Iterator interface helper
     *
     * @access private
     */
    protected $current = null;

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function __construct($documentID)
    {
        /*if ($documentID instanceof self) {
           var_dump($documentID->getDocumentID());
        }*/
        $id = $documentID instanceof self ? $documentID->getDocumentID() : $documentID;
        // var_dump($id);
        if (!isset(Query::$documents[$id])) {
            // var_dump(Query::$documents);
            throw new Exception("Document with ID '{$id}' isn't loaded. Use Query::newDocument(\$html) or Query::newDocumentFile(\$file) first.");
        }
        $this->documentID = $id;
        $this->documentWrapper = &Query::$documents[$id];
        $this->document = &$this->documentWrapper->document;
        $this->xpath = &$this->documentWrapper->xpath;
        $this->charset = &$this->documentWrapper->charset;
        $this->documentFragment = &$this->documentWrapper->isDocumentFragment;
        // TODO check $this->DOM->documentElement;
        // $this->root = $this->DOM->documentElement;
        $this->root = &$this->documentWrapper->root;
        // $this->toRoot();
        $this->elements = array($this->root);
    }

    /**
     *
     * @access private
     * @param $attr
     * @return unknown_type
     */
    public function __get($attr)
    {
        switch ($attr) {
            // FIXME doesn't work at all ?
            case 'length':
                return $this->size();
                break;

            default:
                return $this->attr;
        }
    }

    /**
     * Saves actual object to $var by reference.
     * Useful when need to break chain.
     * @param QueryObject $var
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function toReference(&$var)
    {
        return $var = $this;
    }

    public function documentFragment($state = null)
    {
        if ($state) {
            Query::$documents[$this->getDocumentID()]['documentFragment'] = $state;
            return $this;
        }
        return $this->docuemntFragment;
    }

    /**
     * @access private
     * @TODO documentWrapper
     */
    protected function isRoot($node)
    {
        // return $node instanceof DOMDOCUMENT || $node->tagName == 'html';
        return $node instanceof DOMDOCUMENT || ($node instanceof DOMDOCUMENT && $node->tagName == 'html') || $this->root->isSameNode($node);
    }

    /**
     * @access private
     */
    protected function stackIsRoot()
    {
        return $this->size() == 1 && $this->isRoot($this->elements[0]);
    }

    /**
     * Enter description here...
     * NON JQUERY METHOD
     *
     * Watch out, it doesn't creates new instance, can be reverted with end().
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function toRoot()
    {
        $this->elements = array($this->root);
        return $this;
        // return $this->newInstance(array($this->root));
    }

    /**
     * Saves object's DocumentID to $var by reference.
     * <code>
     * $myDocumentID;
     * Query::newDocument('<div/>');
     *     ->getDocumentIDRef($myDocumentID)
     *     ->find('div')->...
     * <code>
     *
     * @param unknown_type $domId
     * @param Query::newDocument
     * @param Query::newDocumentFile
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function getDocumentIDRef(&$documentID)
    {
        $documentID = $this->getDocumentID();
        return $this;
    }

    /**
     * Returns object with stack set to document root.
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function getDocument()
    {
        return Query::getDocument($this->getDocumentID());
    }

    /**
     *
     * @return DOMDocument
     */
    public function getDOMDocument()
    {
        return $this->document;
    }

    /**
     * Get object's Document ID.
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function getDocumentID()
    {
        return $this->documentID;
    }

    /**
     * Unloads whole document from memory.
     * CAUTION! None further operations will be possible on this document.
     * All objects refering to it will be useless.
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function uploadDocument()
    {
        Query::unloadDocuments($this->getDocumentID());
    }

    public function isHTML()
    {
        return $this->documentWrapper->isHTML;
    }

    public function isXHTML()
    {
        return $this->documentWrapper->isXHTML;
    }

    public function isXML()
    {
        return $this->documentWrapper->isXML;
    }

    /**
     * Enter description here...
     *
     * @link http://docs.jquery.com/Ajax/serialize
     * @return string
     */
    public function serialize()
    {
        return Query::param($this->serializeArray());
    }

    /**
     * Enter description here...
     *
     * @link http://docs.jquery.com/Ajax/serializeArray
     * @return array
     */
    public function serializeArray($submit = null)
    {
        $source = $this->filter('form, input, select, textarea')
            ->find('input, select, textarea')
            ->andSelf()
            ->not('form');
        $return = array();
        // $source->dumpDie();
        foreach ($source as $input) {
            $input = Query::pq($input);
            if ($input->is('[disabled]')) {
                continue;
            }
            if (!$input->is(['name'])) {
                continue;
            }
            if ($input->is('[type=checkbox]') && !$input->is('[checked]')) {
                continue;
            }
            // jquery diff
            if ($submit && $input->is('[type=submit]')) {
                if ($submit instanceof DOMELEMENT && !$input->elements[0]->isSameNode($submit)) {
                    continue;
                } elseif (is_string($submit) && $input->attr('name') != $submit) {
                    continue;
                }
                $return[] = array(
                    'name' => $input->attr('name'),
                    'value' => $input->val(),
                );
            }
            return $return;
        }
    }

    /**
     * @access private
     */
    protected function debug($in)
    {
        if (!Query::$debug) {
            return;
        }
        print_r("<pre>");
        print_r($in);
        // file debug
        // file_put_contents(dirname(__FILE__).'/phpQuery.log', print_r($in, true)."\n", FILE_APPEND);
        // quite handy debug trace
        /*if (is_array($in)) {
            print_r(array_slice(debug_backtrace(), 3));
        }*/
        print_r("</pre>\n");
    }

    /**
     * @access private
     */
    protected function isRegexp($pattern)
    {
        return in_array(
            $pattern[mb_strlen($pattern)-1],
            array('^', '*', '$')
        );
    }

    /**
     * Determines if $char is really a char.
     *
     * @param string $char
     * @return bool
     * @todo rewrite me to charcode range ! ;)
     * @access private
     */
    protected function isChar($char)
    {
        return extension_loaded('mbstring') && Query::$mbstringSupport ? mb_eregi('\w', $char) : preg_match('@\w@', $char);
    }

    /**
     * @access private
     */
    protected function parseSelector($query)
    {
        // clean spaces
        // TODO include this inside parsing ?
        $query = trim(preg_replace('@\s+@', ' ', preg_replace('@\s*(>|\\+|~)\s*@', '\\1', $query)));
        $queries = array(array());
        if (!$query) {
            return $queries;
        }
        $return = &$queries[0];
        $specialChars = array('>', ' ');
        // $specialCharsMapping = array('/' => '>');
        $specialCharsMapping = array();
        $strlen = mb_strlen($query);
        $classChars = array('.', '-');
        $pseudoChars = array('-');
        $tagChars = array('*', '|', '-');
        // split multibyte string
        // http://code.google.com/p/phpquery/issues/detail?id=76
        $_query = array();
        for ($i = 0; $i < $strlen; $i++) {
            $_query[] = mb_substr($query, $i, 1);
        }
        $query = $_query;
        // it works, but i dont like it...
        $i = 0;
        while ($i < $strlen) {
            $c = $query[$i];
            $tem = '';
            // TAG
            if ($this->isChar($c) || in_array($c, $tagChars)) {
                while (isset($query[$i]) && ($this->isChar($query[$i]) || in_array($query[$i], $tagChars))) {
                    $tem .= $query[$i];
                    $i++;
                }
                $return[] = $tem;
            } elseif ($c == '#') { // IDs
                $i++;
                while (isset($query[$i]) && ($this->isChar($query[$i]) || $query[$i] == '-')) {
                    $tem .= $query[$i];
                    $i++;
                }
                $return[] = '#' . $tem;
            } elseif (in_array($c, $specialChars)) { // SPECIAL CHARS
                $return[] = $c;
                $i++;
            // } elseif ($c.$query[$i+1] == '//') { // MAPPED SPECIAL MULTICHARS
            //     $return[] = ' ';
            //     $i = $i+2;
            } elseif (isset($specialCharsMapping[$c])) { // MAPPED SPECIAL CHARS
                $return[] = $specialCharsMapping[$c];
                $i++;
            } elseif ($c == ',') { // COMMA
                $queries[] = array();
                $return = &$queries[count($queries)-1];
                $i++;
                while (isset($query[$i]) && $query[$i] == ' ') {
                    $i++;
                }
            } elseif ($c == '.') { // CLASSES
                while (isset($query[$i]) && ($this->isChar($query[$i]) || in_array($query[$i], $classChars))) {
                    $tem .= $query[$i];
                    $i++;
                }
                $return[] = $tem;
            } elseif ($c == '~') { // ~ General Sibling Selector
                $spaceAllowed = true;
                $tem .= $query[$i++];
                while (isset($query[$i]) && ($this->isChar($query[$i]) || in_array($query[$i], $classChars) || $query[$i] == '*' || ($query[$i] == ' ' && $spaceAllowed))) {
                    if ($query[$i] != ' ') {
                        $spaceAllowed = false;
                    }
                    $tem .= $query[$i];
                    $i++;
                }
                $return[] = $tem;
            } elseif ($c == '+') { // + Adjacent sibling selectors
                $spaceAllowed = true;
                $tem .= $query[$i++];
                while (isset($query[$i]) && ($this->isChar($query[$i]) || in_array($query[$i], $classChars) || $query[$i] == '*' || ($spaceAllowed && $query[$i] == ' '))) {
                    if ($query[$i] != ' ') {
                        $spaceAllowed = false;
                    }
                    $tem .= $query[$i];
                    $i++;
                }
                $return[] = $tem;
            } elseif ($c == '[') { // ATTRS
                $stack = 1;
                $tem .= $c;
                while (isset($query[++$i])) {
                    $tem .= $query[$i];
                    if ($query[$i] == '[') {
                        $stack++;
                    } elseif ($query[$i] == ']') {
                        $stack--;
                        if (!$stack) {
                            break;
                        }
                    }
                }
                $return[] = $tem;
                $i++;
            } elseif ($c == ':') { // PSEUDO CLASSES
                $stack = 1;
                $tem .= $query[$i++];
                while (isset($query[$i]) && ($this->isChar($query[$i]) || in_array($query[$i], $pseudoChars))) {
                    $tem .= $query[$i];
                    $i++;
                }
                // with arguments?
                if (isset($query[$i]) && $query[$i] == '(') {
                    $tem = .= $query[$i];
                    $stack = 1;
                    while (isset($query[++$i])) {
                        $tem .= $query[$i];
                        if ($query[$i] == '(') {
                            $stack++;
                        } elseif ($query[$i] == ')') {
                            $stack--;
                            if (!$stack) {
                                break;
                            }
                        }
                    }
                    $return[] = $tem;
                    $i++;
                } else {
                    $return[] = $tem;
                }
            } else {
                $i++;
            }
        }
        foreach ($queries as $k => $q) {
            if (isset($q[0])) {
                if (isset($q[0][0]) && $q[0][0] == ':') {
                    array_unshift($queries[$k], '*');
                }
                if ($q[0] != '>') {
                    array_unshift($queries[$k], ' ');
                }
            }
        }
        return $queries;
    }

    /**
     * Return matched DOM nodes.
     *
     * @param int $index
     * @return array|DOMElement Single DOMElement or array of DOMElement.
     */
    public function get($index = null, $callback1 = null, $callback2 = null, $callback3 = null)
    {
        $return = isset($index) ? (isset($this->elements[$index]) ? $this->elements[$index] : null) : $this->elements;
        // pass thou callbacks
        $args = func_get_args();
        $args = array_slice($args, 1);
        foreach ($args as $callback) {
            if (is_array($return)) {
                foreach ($return as $k => $v) {
                    $return[$k] = Query::callbackRun($callback, array($v));
                }
            } else {
                $return = Query::callbackRun($callback, array($return));
            }
        }
        return $return;
    }

    /**
     * Return matched DOM nodes.
     * jQuery difference.
     *
     * @param int $index
     * @return array|string Returns string if $index != null
     * @todo implement callbacks
     * @todo return only arrays ?
     * @todo maybe other name...
     */
    public function getString($index = null, $callback1 = null, $callback2 = null, $callback3 = null)
    {
        if ($index) {
            $return = $this->eq($index)->text();
        } else {
            $return = array();
            for ($i = 0; $i < $this->size(); $i++) {
                $return[] = $this->eq($i)->text();
            }
        }
        // pass thou callbacks
        $args = func_get_args();
        $args = array_slice($args, 1);
        foreach ($args as $callback) {
            $return = Query::callbackRun($callback, array($return));
        }
        return $return;
    }

    /**
     * Return matched DOM nodes.
     * jQuery difference.
     *
     * @param int $index
     * @return array|string Returns string if $index != null
     * @todo implement callbacks
     * @todo return only arrays ?
     * @todo maybe other name...
     */
    public function getStrings($index = null, $callback1 = null, $callback2 = null, $callback3 = null)
    {
        if ($index) {
            $return $this->eq($index)->text();
        } else {
            $return = array();
            for ($i = 0; $i < $this->size(); $i++) {
                $return[] = $this->eq($i)->text();
            }
            // pass thou callbacks
            $args = func_get_args();
            $args = array_slice($args, 1);
        }
        foreach ($args as $callback) {
            if (is_array($return)) {
                foreach ($return as $k => $v) {
                    $return[$k] = Query::callbackRun($callback, array($v));
                }
            } else {
                $return Query::callbackRun($callback, array($return));
            }
        }
        return $return;
    }

    /**
     * Returns new instance of actual class.
     *
     * @param array $newStack Optional. Will replace old stack with new and move old one to history.
     */
    public function newInstance($newStack = null)
    {
        $class = get_class($this);
        // support inheritance by passing old object to overloaded constructor
        $new = $class != 'phpQuery' ? new $class($this, $this->getDocumentID()) : new QueryObject($this->getDocumentID());
        $new->previous = $this;
        if (is_null($newStack)) {
            $new->elements = $this->elements;
            if ($this->elementsBackup) {
                $this->elements = $this->elementsBackup;
            }
        } elseif (is_string($newStack)) {
            $new->elements = Query::pq($newStack, $this->getDocumentID())->stack();
        } else {
            $new->elements = $newStack;
        }
        return $new;
    }

    /**
     * 匹配class
     *
     * In the future, when PHP will support XLS 2.0, then we would do that this way:
     * contains(tokenize(@class, '\s'), "something")
     * @param unknown_type $class
     * @param unknown_type $node
     * @return boolean
     * @access private
     */
    protected function matchClasses($class, $node)
    {
        // multi-class
        if (mb_strpos($class, '.', 1)) {
            $classes = explode('.', substr($class, 1));
            $classesCount = count($classes);
            $nodeClasses = explode(' ', $node->getAttribute('class'));
            $nodeClassesCount = count($nodeClasses);
            if ($classesCount > $nodeClassesCount) {
                return false;
            }
            $diff = count(array_diff($classes, $nodeClasses));
            if (!$diff) {
                return true;
            }
        } else {
            // single-class
            // strip leading dot from class name
            // get classes for element as array
            return in_array(substr($class, 1), explode(' ', $node->getAttribute('class')));
        }
    }

    /**
     * @access private
     */
    protected function runQuery($XQuery, $selector = null, $compare = null)
    {
        if ($compare && !method_exists($this, $compare)) {
            return false;
        }
        $stack = array();
        if (!$this->elements) {
            $this->debug('Stack empty, skipping...');
        }
        // var_dump($this->elements[0]->nodeType);
        // element, document
        foreach ($this->stack(array(1, 9, 13)) as $k => $stackNode) {
            $detachAfter = false;
            // to work on detached nodes we need temporary place them somewhere
            // thats because context xpath queries sucks ;]
            $testNode = $stackNode;
            while ($testNode) {
                if (!$testNode->parentNode && !$this->isRoot($testNode)) {
                    $this->root->appendChild($testNode);
                    $detachAfter = $testNode;
                    break;
                }
                $testNode = isset($testNode->parentNode) ? $testNode->parentNode : null;
            }
            // XXX tem ?
            $xpath = $this->documentWrapper->isXHTML ? $this->getNodeXpath($stackNode, 'html') : $this->getNodeXpath($stackNode);
            // FIXME pseudoclasses-only query, support XML
            $query = $XQuery == '//' && $xpath == '/html[1]' ? '//*' : $xpath . $XQuery;
            $this->debug("XPATH: {$query}");
            // run query, get elements
            $nodes = $this->xpath->query($query);
            $this->debug("QUERY FETCHED");
            if (!$nodes->length) {
                $this->debug("Nothing found");
            }
            $debug = array();
            foreach ($nodes as $node) {
                $matched = false;
                if ($compare) {
                    Query::$debug ? $this->debug("Found: " . $this->whois($node) . ", comparing with {$compare}()") : null;
                    $phpQueryDebug = Query::$debug;
                    Query::$debug = false;
                    // TDOD ??? use Query::callbackRun()
                    if (call_user_func_array(array($this, $compare), array($selector, $node))) {
                        $matched = true;
                    }
                    Query::$debug = $phpQueryDebug;
                } else {
                    $matched = true;
                }
                if ($matched) {
                    if (Query::$debug) {
                        $debug[] = $this->whois($node);
                    }
                    $stack[] = $node;
                }
            }
            if (Query::$debug) {
                $this->debug("Matched " . count($debug) . ": " . implode(', ', $debug));
            }
            if ($detachAfter) {
                $this->root->removerChild();
            }
        }
        $this->elements = $stack;
    }
}
