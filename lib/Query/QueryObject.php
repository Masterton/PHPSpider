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

    /**
     * Enter description here...
     *
     * css to xpath
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function find($selectors, $context = null, $noHistory = false)
    {
        if (!$noHistory) {
            // backup last stack /for end()/
            $this->elementsBackup = $this->elements;
        }
        // allow to define context
        // TODO combine code below with Query::pq() context guessing code as generic function
        if ($context) {
            if (!is_array($context) && $context instanceof DOMELEMENT) {
                $this->elements = array($contents);
            } elseif (is_array($context)) {
                $this->elements = array();
                foreach ($context as $c) {
                    if ($c instanceof DOMELEMENT) {
                        $this->elements[] = $c;
                    }
                }
            } elseif ($context instanceof self) {
                $this->elements = $context->elements;
            }
        }
        $queries = $this->parseSelector($selectors);
        $this->debug(array('FIND', $selectors, $queries));
        $XQuery = '';
        // remember stack state because of multi-queries
        $oldStack = $this->elements;
        // here we will be keeping found elements
        $stack = array();
        foreach ($queries as $selectors) {
            $this->elements = $oldStack;
            $delimiterBefore = false;
            foreach ($selector as $s) {
                // TAG
                $isTag = extension_loaded('mbstring') && Query::$mbstringSupport ? mb_ereg_match('^[\w|\||-]+$', $s) || $s == '*' : preg_match('@^[\w|\||-]+$@', $s) || $s == '*';
                if ($isTag) {
                    if ($this->isXML()) {
                        // namespace support
                        if (mb_strpos($s, '|') !== false) {
                            $ns = $tag = null;
                            list($ns, $tag) = explode('|', $s);
                            $XQuery .= "$ns:$tag";
                        } elseif ($s == '*') {
                            $XQuery .= "*";
                        } else {
                            $XQuery .= "*[local-name()='$s']";
                        }
                    } else {
                        $XQuery .= $s;
                    }
                } elseif ($s[0] == '#') { // ID
                    if ($delimiterBefore) {
                        $XQuery .= '*';
                    }
                    $XQuery .= "[@id='" . substr($s, 1) . "']";
                } elseif ($s[0] == '[') { // ATTRIBUTES
                    if ($delimiterBefore) {
                        $XQuery .= '*';
                    }
                    // strip side brackets
                    $attr = trim($s, '][');
                    $execute = false;
                    // attr with specifed value
                    if (mb_strpos($s, '=')) {
                        $value = null;
                        list($attr, $value) = explode('=', $attr);
                        $value = trim($value, "'\"");
                        if ($this->isRegexp($attr)) {
                            // cut regexp character
                            $attr = substr($attr, 0, -1);
                            $execute = true;
                            $XQuery .= "[@{$attr}]";
                        } else {
                            $XQuery .= "[@{$attr}='{$value}']";
                        }
                    } else { // attr without specified value
                        $XQuery .= "[@{$attr}]";
                    }
                    if ($execute) {
                        $this->runQuery($XQuery, $s, 'is');
                        $XQuery = '';
                        if (!$this->length()) {
                            break;
                        }
                    }
                } elseif ($s[0] == '.') { // CLASSES
                    // TODO use return $this->find("./self::*[contains(concat(\" \",@class,\" \"), \" $class \")]");
                    // thx wizDom ;)
                    if ($delimiterBefore) {
                        $XQuery .= '*';
                    }
                    $XQuery .= '[@class]';
                    $this->runQuery($XQuery, $s, 'matchClasses');
                    $XQuery = '';
                    if (!$this->length()) {
                        break;
                    }
                } elseif ($s[0] == '~') { // ~ General Sibling Selector
                    $this->runQuery($XQuery);
                    $XQuery = '';
                    $this->elements = $this->siblings(substr($s, 1))->elements;
                    if (!$this->length()) {
                        break;
                    }
                } elseif ($s[0] == '+') { // + Adjacent silbing selectors
                    // TODO /following-sibling::
                    $this->runQuery($XQuery);
                    $XQuery = '';
                    $subSelector = substr($s, 1);
                    $subElements = $this->elements;
                    $this->elements = array();
                    foreach ($subElements as $node) {
                        // search first DOMElement sibling
                        $test = $node->nextSibling;
                        while ($test && ($test instanceof DOMELEMENT)) {
                            $test = $test->nextSibling;
                        }
                        if ($test && $this->is($subSelector, $test)) {
                            $this->elements[] = $test;
                        }
                    }
                    if (!$this->length()) {
                        break;
                    }
                } elseif ($s[0] == ':') { // PSEUDO CLASSES
                    // TODO optimization for :first :last
                    if ($XQuery) {
                        $this->runQuery($XQuery);
                        $XQuery = '';
                    }
                    if (!$this->length()) {
                        break;
                    }
                    $this->pseudoClasses($s);
                    if (!$this->length()) {
                        break;
                    }
                } elseif ($s == '>') { // DIRECT DESCENDANDS
                    $XQuery .= '/';
                    $delimiterBefore = 2;
                } elseif ($s == ' ') { // ALL DESCENDANDS
                    $XQuery .= '//';
                    $delimiterBefore = 4;
                } else { // ERRORS
                    Query::debug("Unrecognized token '$s'");
                }
                $delimiterBefore = $delimiterBefore === 2;
            }
            // run query if any
            if ($XQuery && $XQuery != '//') {
                $this->runQuery($XQuery);
                $XQuery = '';
            }
            foreach ($this->elements as $node) {
                if (!$this->elementsContainsNode($node, $stack)) {
                    $stack[] = $node;
                }
            }
        }
        $this->elements = $stack;
        return $this->newInstance();
    }

    /**
     * @todo create API for classes with pseudoselectors
     * @access private
     */
    protected function pseudoClasses($class)
    {
        // TODO clean args parsing ?
        $class = ltrim($class, ':');
        $haveArgs = mb_string($class, '(');
        if ($haveArgs !== false) {
            $args = substr($class, $haveArgs+1, -1);
            $class = substr($class, 0, $haveArgs);
        }
        switch ($class) {
            case 'even':
            case 'odd':
                $stack = array();
                foreach ($this->elements as $i => $node) {
                    if ($class == 'even' && ($i%2) == 0) {
                        $stack[] = $node;
                    } elseif ($class == 'odd' && $i%2) {
                        $stack[] = $node;
                    }
                }
                $this->elements = $stack;
                break;
            case 'eq':
                $k = intval($args);
                $this->elements = isset($this->elements[$k]) ? array($this->elements[$k]) : array();
                break;
            case 'gt':
                $this->elements = array_slice($this->elements, $args+1);
                break;
            case 'lt':
                $this->elements = array_slice($this->elements, 0, $args+1);
                break;
            case 'first':
                if (isset($this->elements[0])) {
                    $this->elements = array($this->elements[0]);
                }
                break;
            case 'last':
                if ($this->elements) {
                    $this->elements = array($this->elements[count($this->elements)-1]);
                }
                break;
            /*case 'parent':
                $stack = array();
                foreach ($this->elements as $node) {
                    if ($node->childNodes ->length) {
                        $stack[] = $node;
                    }
                }
                $this->elements = $stack;
                break;*/
            case 'contains':
                $text = trim($args, "\"'");
                $stack = array();
                foreach ($this->elements as $node) {
                    if (mb_string($node->textContent, $text) === false) {
                        continue;
                    }
                    $stack[] = $node;
                }
                $this->elements = $stack;
                break;
            case 'not':
                $selector = self::unQuote($args);
                $this->elements = $this->not($selector)->stack();
                break;
            case 'slice':
                // TODO jQuery difference ?
                $args = explode(',', str_replace(', ', ',', trim($args, "\"'")));
                $start = $args[0];
                $end = isset($args[1]) ? $args[1] : null;
                if ($end > 0) {
                    $end = $end - $start;
                }
                $this->elements = array_slice($this->elements, $start, $end);
                break;
            case 'has':
                $selector = trim($args, "\"'");
                $stack = array();
                foreach ($this->stack(1) as $el) {
                    if ($this->find($selector, $el, true)->length) {
                        $stack[] = $el;
                    }
                }
                $this->elements = $stack;
                break;
            case 'submit':
            case 'reset':
                $this->elements = Query::merge(
                    $this->map(
                        array($this, 'is'),
                        "input[type=$class]",
                        new CallbackParam()
                    ),
                    $this->map(
                        array($this, 'is'),
                        "button[type=$class]"),
                        new CallbackParam()
                    )
                );
                break;
                // $stack = array();
                // foreach ($this->elements as $node) {
                //     if ($node->is('input[type=submit]') || $node->is('button[type=submit]')) {
                //         $stack[] = $el;
                //     }
                //     $this->elements = $stack;
                // }
            case 'input':
                $this->elements = $this->map(
                    array($this, 'is'),
                    'input',
                    new CallbackParam()
                );
                break;
            case 'password':
            case 'checkbox':
            case 'radio':
            case 'hidden':
            case 'image':
            case 'file':
                $this->elements = $this->map(
                    array($this, 'is'),
                    "input[type=$class]",
                    new CallbackParam()
                )->elements;
                break;
            case 'parent':
                $this->elements = $this->map(
                    create_function('$node', 'return $node instanceof DOMELEMENT && $node->childNodes->length ? $node : null;')
                )->elements;
                break;
            case 'empty':
                $this->elements = $this->map(
                    create_function('$node', 'return $node instanceof DOMELEMENT && $node-.childNodes->length ? null, $node;')
                )->elements;
                break;
            case 'disabled':
            case 'selected':
            case 'checked':
                $this->elements = $this->map(
                    array($this, 'is'),
                    "[$class]",
                    new CallbackParam()
                )->elements;
                break;
            case 'enabled':
                $this->elements = $this->map(
                    create_function('$node', 'return pq($node)->not(":disabled") ? $node : null;')
                )->elements;
                break;
            case 'header':
                $this->elements = $this->map(
                    create_function('$node', '$isHeader = isset($node->tagName) && in_array($node->tagName, array("h1", "h2", "h3", "h4", "h5", "h6", "h7")); return $isHeader ? $node : null;')
                )->elements;
                /*$this->elements = $this->map(
                    create_function('$node', '$node = pq($node); return $node->is("h1") || $node->is("h2") || $node->is("h3") || $node->is("h4") || $node->is("h5") || $node->is("h6" || $node->is("h7")) ? $node : null;')
                )->elements;*/
                break;
            case 'only-child':
                $this->elements = $this->map(
                    create_function('$node', 'return pq($node)->siblings()->size() == 0 ? $node : null')
                )->elements;
                break;
            case 'first-child':
                $this->elements = $this->map(
                    create_function('$node', 'return pq($node)->prevAll()->size() == 0 ? $node : null;')
                )->elements;
                break;
            case 'last-child':
                $this->elements = $this->map(
                    create_function('$node', 'return pq($node)->nextAll()->size() == 0 ? $node : null;')
                )->elements;
                break;
            case 'nth-child':
                $param = trim($args, "\"'");
                if (!$param) {
                    break;
                }
                // nth-child(n+b) to nth-child(1n+b)
                if ($param{0} == 'n') {
                    $param = '1' . $param;
                }
                // :nth-child(index/even/odd/equation)
                if ($param == 'even' || $param == 'odd') {
                    $mapped = $this->map(
                        create_function('$node, $param', '$index = pq($node)->prevAll()->size()+1; if ($param == "even" && ($index%2) == 0) { return $node; } else if ($param == "odd" && $index%2 == 1) { return $node; } else { return null; }'),
                        new CallbackParam(),
                        $param
                    );
                } elseif (mb_strlen($param) > 1 && $param{1} == 'n') {
                    // an+b
                    $mapped = $this->map(
                        create_function(
                            '$node, $param',
                            '$prevs = pq($node)->prevAll()->size();
                            $index = 1+$prevs;
                            $b = mb_strlen($param) > 3 ? $param{3} : 0;
                            $a = $param{0};
                            if ($b && $param{2} == "-") $b = -$b;
                            if ($a > 0) {
                                return ($index-$b)%$a == 0 ? $node : null;
                                Query::debug($a . "*" . floor($index/$a) . "+$b-1 == " . ($a*floor($index/$a)+$b-1)." ?= $prevs");
                                return $a*floor($index/$a)+$b-1 == $prevs ? $node : null;
                            } else if ($a == 0) {
                                return $index == $b ? $node : null;
                            } else {
                                // negative value
                                return $index <= $b ? $node : null;
                            }
                            if (!$b) {
                                return $index%$a == 0 ? $node : null;
                            } else {
                                return ($index-$b)%#a == 0 ? $node : null;
                            }'
                        ),
                        new CallbackParam(),
                        $param
                    );
                } else {
                    // index
                    $mapped = $this->map(
                        create_function(
                            '$node, $index',
                            '$prevs = pq($node)->prevAll()->size();
                            if ($prevs && $prevs == $index-1) {
                                return $node;
                            } else if (!$prevs && $index == 1) {
                                return $node;
                            } else {
                                return null;
                            }'
                        ),
                        new CallbackParam(),
                        $param
                    );
                }
                $this->elements = $mapped->elements;
                break;
            default:
                $this->debug("Unknown pseudpcall '{$class}', skipping...");
        }
    }

    /**
     * @access private
     */
    protected function __pseudoClassParam($paramsString)
    {
        // TODO
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParams|QueryTemplatesSourceQuery
     */
    public function is($selector, $nodes = null)
    {
        Query::debug(array("Is:", $selector));
        if (!$selector) {
            return false;
        }
        $oldStack = $this->elements;
        $returnArray = false;
        if ($nodes as is_array($nodes)) {
            $this->elements = $nodes;
        } else if ($nodes) {
            $this->elements = array($nodes);
        }
        $this->filter($selector, true);
        $stack = $this->elements;
        $this->elements = $oldStack;
        if ($nodes) {
            return $stack ? $stack : null;
        }
        return (bool)count($stack);
    }

    /**
     * Enter description here...
     * jQuery difference
     *
     * Callback:
     * - $index int
     * - $node DOMNode
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     * @link http://docs.jquery.com/Traversing/filter
     */
    public function filterCallback($callback, $_skipHistory = false)
    {
        if (!$_skipHistory) {
            $this->elementsBackup = $this->elements;
            $this->debug("Filtering by callback");
        }
        $newStack = array();
        foreach ($this->elements as $index => $node) {
            $result = Query::callbackRun($callback, array($index, $node));
            if (is_null($result) || (!is_null($result) && $result)) {
                $newStack[] = $node;
            }
        }
        $this->elements = $newStack;
        return $_skipHistory ? $this : $this->newInstance();
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     * @link http://docs.jquery.com/Traversing/filter
     */
    public function filter($selectors, $_skipHistory = fasle)
    {
        if ($selectors instanceof Callback OR $selectors instanceof Closure) {
            return $this->filterCallback($selectors, $_skipHistory);
        }
        if (!$_skipHistory) {
            $this->elementsBackup = $this->elements;
        }
        $notSimpleSelector = array(' ', '>', '~', '+', '/');
        if (!is_array($selectors)) {
            $selectors = $this->parseSelector($selectors);
        }
        if (!$_skipHistory) {
            $this->debug(array("Filtering:", $selectors));
        }
        $finalStack = array();
        foreach ($selectors as $selector) {
            $stack = array();
            if (!$selector) {
                break;
            }
            // avoid first space or /
            if (in_array($selector[0], $notSimpleSelector)) {
                $selector = array_slice($selector, 1);
            }
            // PER NODE selector chunks
            foreach ($this->stack() as $node) {
                $break = false;
                foreach ($selector as $s) {
                    if (!($node instanceof DOMDOCUMENT)) {
                        // all besides DOMElement
                        if ($s[0] == '[') {
                            $attr = trim($s, '[]');
                            if (mb_strpos($attr, '=')) {
                                list($attr, $val) = explode('=', $attr);
                                if ($attr == 'nodeType' && $node->nodeType !=$val) {
                                    $break = true;
                                }
                            }
                        } else {
                            $break = true;
                        }
                    } else {
                        // DOMElement only
                        // ID
                        if ($s[0] == '#') {
                            if ($node->getAttribute('id') != substr($s, 1)) {
                                $break = true;
                            }
                        } elseif ($s[0] == '.') { // CLASS
                            if (!$this->matchClasses($s, $node)) {
                                $break = true;
                            }
                        } elseif ($s[0] == '[') { // ATTRS
                            // strip side brackets
                            $attr = trim($s, '[]');
                            if (mb_strpos($attr, '=')) {
                                list($attr, $val) = explode('=', $attr);
                                $val = self::unQuote($val);
                                if ($attr == 'nodeType') {
                                    if ($val != $node->nodeType) {
                                        $break = true;
                                    }
                                } elseif ($this->isRegexp($attr)) {
                                    $val = extension_loaded('mbstring') && Query::$mbstringSupport ? quotemeta(trim($val, '"\'')) : preg_quote(trim($val, '"\''), '@');
                                    // switch last character
                                    switch (substr($attr, -1)) {
                                        // quotemeta used insted of preg_quote
                                        // http://code.google.com/p/phpquery/issues/detail?id=76
                                        case '^':
                                            $pattern = '^' . $val;
                                            break;
                                        case '*':
                                            $pattern = '.*' . $val . '.*';
                                            break;
                                        case '$':
                                            $pattern = '.*' . $val . '$';
                                            break;
                                    }
                                    // cut last character
                                    $attr = substr($attr, 0, -1);
                                    $isMatch = extension_loaded('mbstring') && Query::$mbstringSupport ? mb_ereg_match($pattern, $node->getAttribute($attr)) : preg_match("@{$pattern}@", $node->getAttribute($attr));
                                    if (!$isMatch) {
                                        $break = true;
                                    }
                                } elseif ($node->getAttribute($attr) != $val) {
                                    $break = true;
                                }
                            } elseif (!$node->hasAttribute($attr)) {
                                $break = true;
                            }
                        } elseif ($s == ':') { // PSEUDO CLASSES
                            // skip
                        } elseif (trim($s)) { // TAG
                            if ($s != '*') {
                                // TODO namespaces
                                if (isset($node->tagName)) {
                                    if ($node->tagName != $s) {
                                        $break = true;
                                    }
                                } elseif ($s == 'html' && !$this->isRoot($node)) {
                                    $break = true;
                                }
                            }
                        } elseif (in_array($s, $notSimpleSelector)) {
                            $break = true;
                            $this->debug(array('Skipping non simple selector', $selector));
                        }
                    }
                    if ($break) {
                        break;
                    }
                }
                // if element passed all chunks of selector - add it to new stack
                if (!$break) {
                    $stack[] = $node;
                }
            }
            $tmpStack = $this->elements;
            $this->elements = $stack;
            // PER ALL NODES selector chunks
            foreach ($selector as $s) {
                // PSEUDO CLASSES
                if ($s[0] == ':') {
                    $this->pseudoClasses($s);
                }
            }
            foreach ($this->elements as $node) {
                // XXX it should be merged without duplicates
                // but jQuery doesnt do that
                $finalStack[] = $node;
            }
            $this->elements = $tmpStack;
        }
        $this->elements = $finalStack;
        if ($_skipHistory) {
            return $this;
        } else {
            $this->debug("Stack length after filter(): " . count($finalStack));
            return $this->newInstance();
        }
    }

    /**
     *
     * @param $value
     * @return unknown_type
     * @todo implement in all methods using passed parameters
     */
    protected static function unQuote($value)
    {
        return $value[0] == '\'' || $value[0] == '"' ? substr($value, 1, -1) : $value;
    }

    /**
     * Enter description here...
     *
     * @link http://docs.jquery.com/Ajax/load
     * @return Query|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     * @todo Support $selector
     */
    public function load($url, $data = null, $callback = null)
    {
        if ($data && !is_array($data)) {
            $callback = $data;
            $data = null;
        }
        if (mb_strpos($url, ' ') !== false) {
            $matches = null;
            if (extension_loaded('mbstring') && Query::$mbstringSupport) {
                mb_erge('^([^ ]+) (.*)$', $url, $matches);
            } else {
                preg_match('^([^ ]+) (.*)$', $url, $matches);
            }
            $url = $marches[1];
            $selector = $matches[2];
            // FIXME this sucks, pass as callback param
            $this->_loadSelector = $selector;
        }
        $ajax = array(
            'url' => $url,
            'type' => $data ? 'POST' : 'GET',
            'data' => $data,
            'complete' => $callback,
            'success' => array($this, '__loadSuccess')
        );
        Query::ajax($ajax);
        return $this;
    }

    /**
     * @access private
     * @param $html
     * @return unknown_type
     */
    public function __loadSuccess($html)
    {
        if ($this->_loadSelector) {
            $html = Query::newDocument($html)->find($this_loadSelector);
            unset($this->_loadSelector);
        }
        foreach ($this->stack(1) as $node) {
            Query::pq($node, $this->getDocumentID())->markup($html);
        }
    }

    /**
     * Enter description here...
     *
     * @return Query|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     * @todo
     */
    public function css()
    {
        // TODO
        return $this;
    }

    /**
     * @todo
     */
    public function show()
    {
        // TODO
        return $this;
    }

    /**
     * @todo
     */
    public function hide()
    {
        //TODO
        return $this;
    }

    /**
     * Trigger a type of event on every matched element.
     *
     * @param unknown_type $type
     * @param unknown_type $data
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     * @todo support more than event in $type (space-separated)
     */
    public function trigger($type, $data = array())
    {
        foreach ($this->elements as $node) {
            Query::trigger($this->getDocumentID(), $type, $data, $node);
        }
        return $this;
    }

    /**
     * This particular method trigger all bound event handlers on an element (for
     * a specific event type) WITHOUT executing the borwsers default actions.
     *
     * @param unknown_type $type
     * @param unknown_type $data
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     * @todo
     */
    public function triggerHandler($type, $data = array())
    {
        // TODO
    }

    /**
     * Binds a handler to one or more events (like click) for each matched element.
     * Can also bind custom events.
     *
     * @param unknown_type $type
     * @param unknown_type $data Optional
     * @param unknown_type $callback
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     * @todo support '!' (exclusive) events
     * @todo support more than event in $type (space-separated)
     */
    public function bind($type, $data, $callback = null)
    {
        // TODO check if $data is callable, not using is_callable
        if (!isset($callback)) {
            $callback = $data;
            $data = null;
        }
        foreach ($this->elements as $node) {
            QueryEvents::add($this->getDocumentID(), $node, $type, $data, $callback);
        }
        return $this;
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function change($callback = null)
    {
        if ($callback) {
            return $this->bind('change', $callback);
        }
        return $this->trigger('change');
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function submit($callback = null)
    {
        if ($callback = null) {
            return $this->bind('submit', $callback);
        }
        return $this->trigger('submit');
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function click($callback = null)
    {
        if ($callback) {
            return $this->bind('click', $callback);
        }
        return $this->trigger('click');
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function wrapAllOld($wrapper)
    {
        $wrapper = pq($wrapper)->_close();
        if (!$wrapper->length() || !$this->length()) {
            return $this;
        }
        $wrapper->insertBefore($this->elements[0]);
        $deepest = $wrapper->elements[0];
        while ($deepest->firstChild && $deepest->firstChild instanceof DOMELEMENT) {
            $deepest = $deepest->firstChild;
        }
        pq($deepest)->append($this);
        return $this;
    }

    /**
     * Enter description here...
     *
     * @todo testme...
     * @param String|Query
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function wrapAll($wrapper)
    {
        if (!$this->length()) {
            return $this;
        }
        return Query::pq($wrapper, $this->getDocumentID())
            ->close()
            ->insertBefore($this->get(0))
            ->map(array($this, '___wrapAllCallback'))
            ->append($this);
    }

    /**
     * @param $node
     * @param unknown_type
     * @access private
     */
    public function ___wrapAllCallback($node)
    {
        $deepest = $node;
        while ($deepest->firstChild && $deepest->firstChild instanceof DOMELEMENT) {
            $deepest = $deepest->firstChild;
        }
        return $deepest;
    }

    /**
     * Enter description here...
     * NON JQUERY METHOD
     *
     * @param String|Query
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function wrapAllPHP($codeBefore, $codeAfter)
    {
        return $this->slice(0, 1)->beforePHP($codeBefore)->end()->slice(-1)->afterPHP($codeAfter)->end();
    }

    /**
     * Enter description here...
     *
     * @param String|Query
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function wrap($wrapper)
    {
        foreach ($this->stack() as $node) {
            Query::pq($node, $this->getDocumentID())->wrapAll($wrapper);
        }
        return $this;
    }

    /**
     * Enter description here...
     *
     * @param String|Query
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function wrapPHP($codeBefore, $codeAfter)
    {
        foreach ($this->stack() as $node) {
            Query::pq($node, $this->getDocumentID())->wrapAllPHP($codeBefore, $codeAfter);
        }
        return $this;
    }

    /**
     * Enter description here...
     *
     * @param String|Query
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function wrapInner($wrapper)
    {
        foreach ($this->stack() as $node) {
            Query::pq($node, $this->getDocumentID())->contents()->wrapAll($wrapper);
        }
        return $this;
    }

    /**
     * Enter description here...
     *
     * @param String|Query
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function wrapInnerPHP($codeBefore, $codeAfter) {
        foreach ($this->stack(1) as $node) {
            Query::pq($node, $this->getDocumentID())->contents()->wrapAllPHP($codeBefore, $codeAfter);
        }
        return $this;
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     * @testme Support for text nodes
     */
    public function contents() {
        $stack = array();
        foreach ($this->stack(1) as $el) {
            // FIXME (fixed) http://code.google.com/p/phpquery/issues/detail?id=56
            /*if (!isset($el->childNodes)) {
                continue;
            }*/
            foreach ($el->childNodes as $node) {
                $stack[] = $node;
            }
        }
        return $this->newInstance($stack);
    }

    /**
     * Enter description here...
     *
     * jQuery difference
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function contentsUnwrap() {
        foreach ($this->stack(1) as $node) {
            if (!$node->parentNode) {
                continue;
            }
            $childNodes = array();
            // any modification in DOM tree breaks childNodes iteration, so cache them first
            foreach ($node->childNodes as $chNode) {
                $childNodes[] = $chNode;
            }
            foreach ($childNodes as $chNode) {
                // $node->parentNode->appendChild($chNode);
                $node->parentNode->insertBefore($chNode, $node);
            }
            $node->parentNode->removeChild($node);
        }
        return $this;
    }

    /**
     * Enter description here...
     *
     * jQuery difference.
     */
    public function switchWith($markup) {
        $markup = pq($markup, $this->getDocumentID());
        $content = null;
        foreach ($this->stack(1) as $node) {
            pq($node)->contents()->toReference($content)->end()->replaceWith($markup->close()->append($content));
        }
        return $this;
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function eq($num) {
        $oldStack = $this->elements;
        $this->elementsBackup = $this->elements;
        $this->elements = array();
        if (isset($oldStack[$num])) {
            $this->elements[] = $oldStack[$num];
        }
        return $this->newInstance();
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function size()
    {
        return count($this->elements);
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     * @deprecated Use length as attribute
     */
    function length()
    {
        return $this->size();
    }
    public function count()
    {
        return $this->size();
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     * @todo $level
     */
    public function end($level = 1)
    {
        // $this->elements = array_pop($this->histor);
        // return $this;
        // $this->previous->DOM = $this->DOM;
        // $this->previous->XPath = $this->XPath;
        return $this->previous ? $this->previous : $this;
    }

    /**
     * Enter description here...
     * Normal use ->clone() .
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     * @access private
     */
    public function _close()
    {
        $newStack = array();
        // pr(array('copy...', $this->whois()));
        // $this->dumpHistory('copy');
        $this->elementsBackup = $this->elements;
        foreach ($this->elements as $node) {
            $newStack[] = $node->cloneNode(true);
        }
        $this->elements = $newStack;
        return $this->newInstance();
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function replaceWithPHP($code)
    {
        return $this->replaceWith(Query::php($code));
    }

    /**
     * Enter description here...
     *
     * @param String|Query $content
     * @link http://docs.jquery.com/Manipulation/replaceWith#content
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function replaceWith($content)
    {
        return $this->after($content)->remove();
    }

    /**
     * Enter description here...
     *
     * @param String $selector
     * @return http://docs.jquery.com/Manipulation/replaceWith#content
     * @todo this works ?
     */
    public function replaceAll($selector)
    {
        foreach (Query::pq($selector, $this->getDocumentID()) as $node) {
            Query::pq($node, $this->getDocuemntID())->after($this->_clone())->remove();
        }
        return $this;
    }

    /**
     * Enter description here...
     *
     * @return http://docs.jquery.com/Manipulation/replaceWith#content
     */
    public function remove($selector = null)
    {
        $loop = $selector ? $this->filter($selector)->elements : $this->elements;
        foreach ($loop as $node) {
            if (!$node->parentNode) {
                continue;
            }
            if (isset($node->tagName)) {
                $this->debug("Removing '{$node->tagName}'");
            }
            $node->parentNode->removeChild($node);
            // mutation event
            $event = new DOMEvent(array('target' => $node, 'type' => 'DOMNodeRemoved'));
            QueryEvents::trigger($this->getDocumentID(), $event->type, array($event), $node);
        }
        return $this;
    }

    /**
     * markupEvents
     */
    protected function markupEvents($newMarkup, $oldMarkup, $node)
    {
        if ($node->tagName == 'textarea' && $newMarkup != $oldMarkup) {
            $event = new DOMEvent(array('target' => $node, 'type' => 'change'));
            QueryEvents::trigger($this->getDocumentID(), $event->type, array($event), $node);
        }
    }

    /**
     * jQuery difference
     *
     * @param $markup
     * @return unknown_type
     * @todo trigger change event for textarea
     */
    public function markup($markup = null, $callback1 = null, $callback2 = null, $callback3 = null)
    {
        $args = func_get_args();
        if ($this->documentWrapper->isXML) {
            return call_user_func_array(array($this, 'xml'), $args);
        } else {
            return call_user_func_array(array($this, 'html'), $args);
        }
    }

    /**
     * jQuery difference
     *
     * @param $markup
     * @return unknown_type
     */
    public function markupOther($callback1 = null, $callback2 = null, $callback3 = null)
    {
        $args = func_get_args();
        if ($this->documentWrapper->isXML) {
            return call_user_func_array(array($this, 'xmlOuter'), $args);
        } else {
            return call_user_func_array(array($this, 'htmlOuter'), $args);
        }
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $html
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     * @todo force html result
     */
    public function html($html = null, $callback1 = null, $callback2 = null, $callback3 = null)
    {
        if (isset($html)) {
            // INSERT
            $nodes = $this->documentWrapper->import($html);
            $this->empty();
            foreach ($this->stach(1) as $alreadyAdded => $node) {
                // for now, limit events for textarea
                if (($this->isXHTML() || $this->isHTML()) && $node->tagName == 'textarea') {
                    $oldHtml = pq($node, $this->getDocumentID())->markup();
                }
                foreach ($nodes as $newNode) {
                    $node->appendChild($alreadyAdded ? $newNode->cloneNode(true) : $newNode);
                }
                // for now, limit events for textarea
                if (($this->isXHTML() || $this->isHTML()) && $node->tagName == 'textarea') {
                    $this->markupEvents($html, $oldHtml, $node);
                }
            }
            return $this;
        } else {
            // FETCH
            $return = $this->documentWrapper->markup($this->elements, true);
            $args = func_get_args();
            foreach (array_slice($args, 1) as $callback) {
                $return = Query::callbackRun($callback, array($return));
            }
            return $return;
        }
    }

    /**
     * @todo force xml result
     */
    public function xml($xml = null, $callback1 = null, $callback2 = null, $callback3 = null)
    {
        $args = func_get_args();
        return call_user_func_array(array($this, 'html'), $args);
    }

    /**
     * Enter description here...
     * @todo force html result
     *
     * @return String
     */
    public function htmlOuter($callback1 = null, $callback2 = null, $callback3 = null)
    {
        $markup = $this->documentWrapper->markup($this->elements);
        // pass thou callbacks
        $args = func_get_args();
        foreach ($args as $callback) {
            $markup = Query::callbackRun($callback, array($markup));
        }
        return $markup;
    }

    /**
     * @todo force xml result
     */
    public function xmlOuter($callback1 = null, $callback2 = null, $callback3 = null)
    {
        $args = func_get_args();
        return call_user_func_array(array($this, 'htmlOuter'), $args);
    }

    /**
     * __toString
     */
    public function __toString()
    {
        return $this->markupOuter();
    }

    /**
     * Just like html(), but returns markup with VALID (dangerous) PHP tags.
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     * @todo support returning markup with PHP tags when called without param
     */
    public function php($code = null)
    {
        return $this->markupPHP($code);
    }

    /**
     * Enter description here...
     *
     * @param $code
     * @return unknown_type
     */
    public function markupPHP($code = null)
    {
        return isset($code) ? $this->markup(Query::php($code)) : Query::markupToPHP($this->markup());
    }

    /**
     * Enter description here...
     *
     * @param $code
     * @return unknown_type
     */
    public function markupOuterPHP()
    {
        return Query::markupToPHP($this->markupOuter());
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function children($selector = null)
    {
        $stack = array();
        foreach ($this->stack(1) as $node) {
            // foreach ($node->getElementsByTagName('*') as $newNode) {
            foreach ($node->childNodes as $newNode) {
                if ($newNode->nodeType != 1) {
                    continue;
                }
                if ($selector && !$this->is($selector, $newNode)) {
                    continue;
                }
                if ($this->elementsContainsNode($newNode, $stack)) {
                    continue;
                }
                $stack[] = $newNode;
            }
        }
        $this->elementsBackup = $this->elements;
        $this->elements = $stack;
        return $this->newInstance();
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function ancestors($selector = null)
    {
        return $this->children($selector);
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function append($content)
    {
        return $this->insert($content, __FUNCTION__);
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function appendPHP($content)
    {
        return $this->insert("<php><!-- {$content} --></php>", 'append');
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function appendTo($seletor)
    {
        return $this->insert($seletor, __FUNCTION__);
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function prepend($content)
    {
        return $this->insert($content, __FUNCTION__);
    }

    /**
     * Enter description here...
     *
     * @todo accept many arguments, which are joined, arrays maybe also
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function prependPHP($content)
    {
        return $this->insert("<php><!-- {$content} --></php>", 'prepend');
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function prependTo($selector)
    {
        return $this->insert($selector, __FUNCTION__);
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function before($content)
    {
        return $this->insert($content, __FUNCTION__);
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function beforePHP($content)
    {
        return $this->insert("<php><!-- {$content} --></php>", 'before');
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function insertBefore($selector)
    {
        return $this->insert($selector, __FUNCTION__);
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function after($content)
    {
        return $this->insert($content, __FUNCTION__);
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function afterPHP($content)
    {
        return $this->insert("<php><!-- {$content} --></php>", 'after');
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function insertAfter($seletor)
    {
        return $this->insert($selector, __FUNCTION__);
    }

    /**
     * Internal insert method. Don't use it.
     *
     * @param unknown_type $target
     * @param unknown_type $type
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     * @access private
     */
    public function insert($target, $type)
    {
        $this->debug("Inserting data with '{$type}'");
        $to = false;
        switch ($type) {
            case 'appendTo':
            case 'prependTo':
            case 'insertBefore':
            case 'insertAfter':
                $to = true;
        }
        switch (gettype($target)) {
            case 'string':
                $insertFrom = $insertTo = array();
                if ($to) {
                    // INSERT TO
                    $insertFrom = $this->elements;
                    if (Query::isMarkup($target)) {
                        // $target is new markup, import it
                        $insertTo = $this->documentWrapper->import($target);
                    } else { // insert into selected element
                        // $target is a selector
                        $thisStack = $this->elements;
                        $this->toRoot();
                        $insertTo = $this->find($target)->elements;
                        $this->elements = $thisStack;
                    }
                } else {
                    // INSERT FROM
                    $insertTo = $this->elements;
                    $insertFrom = $this->documentWrapper->import($target);
                }
                break;
            case 'object':
                $insertFrom = $insertTo = array();
                // Query
                if ($target instanceof self) {
                    if ($to) {
                        $insertTo = $target->elements;
                        if ($this->documentFragment && $this->stackIsRoot()) {
                            // get all body children
                            // $loop = $this->find('body > *')->elements;
                            // TODO test it, test it hard...
                            // $loop $this->newInstance($this->root)->find('> *')->elements;
                            $loop = $this->root->childNodes;
                        } else {
                            $loop = $this->elements;
                        }
                        // import nodes if needed
                        $insertFrom = $this->getDocumentID() == $target->getDocumentID() ? $loop : $target->documentWrapper->import($loop);
                    } else {
                        $insertTo = $this->elements;
                        if ($target->documentFragment && $target->stackIsRoot()) {
                            // get all body children
                            // $loop $target->find('body > *')->elements;
                            $loop = $target->root->childNodes;
                        } else {
                            $loop = $target->elements;
                        }
                        // import nodes if needed
                        $insertFrom = $this->getDocumentID() == $target->getDocumentID() ? $loop : $this->documentWrapper->import($loop);
                    }
                } elseif ($target instanceof DOMNODE) {
                    // import node if needed
                    /*if ($target->ownerDocument != $this->DOM) {
                        $target = $this->DOM->importNode($target, true);
                    }*/
                    if ($to) {
                        $insertTo = array($target);
                        if ($this->documentFragment && $this->stackIsRoot()) {
                            // get all body children
                            $loop = $this->root->childNodes;
                            // $loop = $this->find('body > *')->elements;
                        } else {
                            $loop = $this->elements;
                        }
                        foreach ($loop as $fromNode) {
                            // import nodes if needed
                            $insertFrom[] = !$fromNode->ownerDocument->isSameNode($target->ownerDocument) ? $target->ownerDocument->importNode($fromNode, true) : $fromNode;
                        }
                    } else {
                        // import node if needed
                        if (!$target->ownerDocument->isSameNode($this->document)) {
                            $target = $this->document->importNode($target, true);
                        }
                        $insertTo = $this->elements;
                        $insertFrom[] = $target;
                    }
                }
                break;
        }
        Query::debug("From ".count($insertFrom)."; To ".count($insertTo)." nodes");
        foreach ($insertTo as $insertNumber => $toNode) {
            // we need static relative elements in some cases
            switch ($type) {
                case 'prependTo':
                case 'prepend':
                    $firstChild = $toNode->firstChild;
                    break;
                case 'insertAfter':
                case 'after':
                    $nextSibling = $toNode->nextSibling;
                    break;
            }
            foreach ($insertFrom as $fromNode) {
                // close if inserted already before
                $insert = $insertNumber ? $fromNode->cloneNode(true) : $fromNode;
                switch ($type) {
                    case 'appendTo':
                    case 'append':
                        // $toNode->insertBefore($fromNode, $toNode->lastChild->nextSibling);
                        $toNode->appendChild($insert);
                        $eventTarget = $insert;
                        break;
                    case 'prependTo':
                    case 'prepend':
                        $toNode->insertBefore($insert, $firstChild);
                        break;
                    case 'insertBefore':
                    case 'before':
                        if (!$toNode->parentNode) {
                            throw new Exception("No parentNode, can't do {$type}()");
                        } else {
                            $toNode->parentNode->insertBefore($insert, $toNode);
                        }
                        break;
                    case 'insertAfter':
                    case 'after':
                        if (!$toNode->parentNode) {
                            throw new Exception("No parentNode, can't do {$type}()");
                        } else {
                            $toNode->parentNode->insertBefore($insert, $nextSibling);
                        }
                        break;
                }
                // Mutation event
                $event = new DOMEvent(array('target' => $insert, 'type' => 'DOMNodeInserted'));
                QueryEvents::trigger($this->getDocumentID(), $event->type, array($event), $insert);
            }
        }
        return $this;
    }

    /**
     * Enter description here...
     *
     * @return Int
     */
    public function index($subject)
    {
        $index = -1;
        $subject = $subject instanceof QueryObject ? $subject->elements[0] : $subject;
        foreach ($this->newInstance() as $k => $node) {
            if ($node->isSameNode($subject)) {
                $index = $k;
            }
        }
        return $index;
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $start
     * @param ubknown_type $end
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function slice($start, $end = null)
    {
        // $last = count($this->elements) - 1;
        // $end = $end ? min($end, $last) : $last;
        // if ($start < 0) {
        //     $start = $last + $start;
        // }
        // if ($start > $last) {
        //     return array();
        // }
        if ($end > 0) {
            $end = $end - $start;
        }
        return $this->newInstance(array_slice($this->elements, $start, $end));
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function reverse()
    {
        $this->elementsBackup = $this->elements;
        $this->elements = array_reverse($this->elements);
        return $this->newInstance();
    }

    /**
     * Enter description here...
     *
     * @return QueryObject|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
     */
    public function plugin($class, $file = null)
    {
        Query::plugin($class, $file);
        return $this;
    }
}
