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
}
