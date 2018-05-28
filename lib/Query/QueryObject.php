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
}
