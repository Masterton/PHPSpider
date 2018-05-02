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
     * @var unknow_type
     */
    public $bubbles = true;
}