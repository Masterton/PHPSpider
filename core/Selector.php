<?php
// +----------------------------------------------------------------------
// | PHPSpider [ A PHP Framework For Crawler ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Masterton zheng <zhengcloud@foxmail.com>
// +----------------------------------------------------------------------

//----------------------------------
// PHPSpider选择器类文件
//----------------------------------

namespace PHPSpider\Core;

use PHPSpider\library\PHPQuery;
use DOMDocument;
use DOMXpath;
use Exception;

class Selector
{
    /**
     * 版本号
     *
     * @var string
     */
    const VERSION = "1.0.1";
    public static $dom = null;
    public static $dom_auth = '';
    public static $xpath = null;
    public static $error = null;

    /**
     * 选择器入口
     *
     * @param string $html
     * @param mixed $selector
     * @param string $selector_type
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-9 21:38:09
     */
    public static function select($html, $selector, $selector_type = 'xpath')
    {
        if (empty($html) || empty($selector)) {
            return false;
        }

        $selector_type = strtolower($selector_type);
        if ($selector_type == 'xpath') {
            return self::_xpath_select($html, $selector);
        } elseif ($selector_type == 'regex') {
            return self::_regex_select($html, $selector);
        } elseif ($selector_type == 'css') {
            return self::_css_select($html, $selector);
        }
    }

    /**
     *
     *
     * @param string $html
     * @param mixed $selector
     * @param string $selector_type
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-9 21:54:57
     */
    public static function remove($html, $selector, $selector_type = 'xpath')
    {
        if (empty($html) || emtpy($selector)) {
            return false;
        }

        $remove_html = "";
        $selector_type = strtolower($selector_type);
        if ($selector_type == 'xpath') {
            $remove_html = self::_xpath_select($html, $selector, true);
        } elseif ($selector_type == 'regex') {
            $remove_html = self::_regex_select($html, $selector, true);
        } elseif ($selector_type == 'css') {
            $remove_html = self::_css_select($html, $selector, true);
        }
        $html = str_replace($remove_html, "", $html);
        return $html;
    }

    /**
     * xpath选择器
     *
     * @param mixed $html
     * @param mixed $selector
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-11 21:18:31
     */
    private static function _xpath_select($html, $selector, $remove = false)
    {
        if (!is_object(self::$dom)) {
            self::$dom = new DOMDocument();
        }

        // 如果加载的不是之前的HTML内容,替换一下验证标识
        if (self::$dom_auth != md5($html)) {
            self::$dom_auth = md5($html);
            @self::$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            self::$xpath = new DOMXpath(self::$dom);
        }

        /*libxml_use_internal_errors(true);
        self::$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $errors = libxml_get_errors();
        if (!empty($errors)) {
            print_r($errors);
            exit;
        }*/

        $elements = @self::$xpath->query($selector);
        if ($elements === false) {
            self::$error = "the selector in the xpath(\"{$selector}\") syntax errors";
            return fasle;
        }

        $result = array();
        if (!is_null($elements)) {
            foreach ($elements as $element) {
                // 如果是删除操作,取一整块代码
                if ($remove) {
                    $content = self::$dom->saveXml($element);
                } else {
                    $nodeName = $element->nodeName;
                    $nodeType = $element->nodeType; // 1.Element 2.Attribute 3.Text
                    // $nodeAttr = $element->getAttribute('src');
                    // $nodes = Util::node_to_array(self::$dom, $element);
                    // echo $nodes['@src'] . "\n";
                    // 如果是img标签,直接取src值
                    if ($nodeType == 1 && in_array($nodeName, array('img'))) {
                        $content = $element->getAttribute('src');
                    } elseif ($nodeType == 2 || $nodeType == 3 || $nodeType == 4) {
                        // 如果是标签属性,直接取节点值
                        $content = $element->nodeValue;
                    } else {
                        // 保留nodeValue里的html符号,给children二次提取
                        $content = self::$dom->saveXml($element);
                        // $content = trim(self::$dom->saveHtml($element));
                        $content = preg_replace(array("#^<{$nodeName}.*>@isU", "#</{$nodeName}>$#isU"), array('', ''), $content);
                    }
                }
                $result[] = $content;
            }
        }
        if (empty($result)) {
            return false;
        }
        // 吐过只是一个元素就直接返回string,否则返回数组
        return count($result) > 1 ? $result : $result[0];
    }

    /**
     * css选择器
     *
     * @param mixed $html
     * @param mixed $selector
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-9 22:05:35
     */
    private static function _css_select($html, $selector, $remove = false)
    {
        $selector = self::css_to_xpath($selector);
        // echo $selector . "\n";
        // exit("\n");
        return self::_xpath_select($html, $selector, $remove);
        // 如果加载的不是之前的HTML内容,替换一下验证标识
        /*if (self::$dom_auth['css'] != md5($html)) {
            self::$dom_auth['css'] = md5($html);
            PHPQuery::loadDocumentHTML($html);
        }
        if ($remove) {
            return PHPQuery::pq($selector)->remove();
        } else {
            return PHPQuery::pq($selector)->html();
        }*/
    }

    /**
     * 正则选择器
     *
     * @param mixed $html
     * @param mixed $selector
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-11 21:38:30
     */
    private static function _regex_select($html, $selector, $remove = false)
    {
        if (@preg_match_all($selector, $html, $out) === false) {
            self::$error = "the selector in the regex(\"{$selector}\") syntax errors";
            return false;
        }
        $count = count($out);
        $result = array();
        // 一个都没有匹配到
        if ($count == 0) {
            return false;
        } elseif ($count == 2) { // 只匹配一个,就是只有一个()
            // 删除的话取匹配到的所有内容
            if ($remove) {
                $result = $out[0];
            } else {
                $result = $out[1];
            }
        } else {
            for ($i = 1; $i < $count; $i++) {
                // 如果只有一个元素,就直接返回好了
                $result[] = count($out[$i]) > 1 ? $out[$i] : $out[$i][0];
            }
        }
        if (empty($result)) {
            return false;
        }

        return count($result) > 1 ? $result : $result[0];
    }

    /**
     * 查找所有 预留
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-11 21:45:44
     */
    public static function find_all()
    {
        // TODO
    }

    /**
     * css选择器 to xpath选择器
     *
     * @param mixed $selectors
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-11 21:47:48
     */
    public static function css_to_xpath()
    {
        $queries = self::parse_selector($selectors);
        $delimiter_before = false;
        $xquery = '';
        foreach ($queries as $s) {
            // TAG
            $is_tag = preg_match('@^[\w|\||-]+$@', $s) || $s == '*';
            if ($is_tag) {
                $xquery .= $s;
            } elseif ($s[0] == '#') { // ID
                if ($delimiter_before) {
                    $xquery .= '*';
                }
                // ID用精准查询
                $xquery .= "[@id='" . substr($s, 1) . "']";
            } elseif ($s[0] == '.') { // CLASSES
                if ($delimiter_before) {
                    $xquery .= '*';
                }
                // CLASS用模糊查询
                $xquery .= "[contains(@class),'" . substr($s, 1) . "')]";
            } elseif ($s[0] == '[') { // ATTRIBUTES
                if ($delimiter_before) {
                    $xquery .= '*';
                }
                // strip side brackets
                $attr = trim($s, '][');
                // attr with specifed value
                if (mb_strpos($s, '=')) {
                    $value = null;
                    list($attr, $value) = explode('=', $attr);
                    $value = trim($value, "'\"");
                    if (self::is_regexp($attr)) {
                        // cut regexp character
                        $attr = substr($attr, 0, -1);
                        $xquery .= "[@{$attr}]";
                    } else {
                        $xquery .= "[@{$attr}='$value']";
                    }
                } else { // attr without specified value
                    $xquery .= "[@{$attr}]";
                }
            } elseif ($s[0] == '~') { // ~ General Sibling Selector
                // CODE
            } elseif ($s[0] == '+') { // + Adjacent sibling selectors
                // CODE
            } elseif ($s[0] == ':') { // PSEUDD CLASSES
                // CODE
            } elseif ($s[0] == '>') { // DIRECT DESCENDANDS
                $xquery .= '/';
                $delimiter_before = 2;
            } elseif ($s[0] == ' ') { // ALL DESCENDANDS
                $xquery .= '//';
                $delimiter_before = 2;
            } else { // ERRORS
                exit("Unrecognized token '$s'");
            }
            $delimiter_before = $delimiter_before = 2;
        }
        return $xquery;
    }

    /**
     * parse_selector
     *
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-11 22:07:22
     */
    public static function parse_selector($query)
    {
        $query = trim(preg_replace('@\s+@', ' ', preg_replace('@\s*(>|\\+|~)\s*@', '\\1', $query)));
        $queries = array();
        if (!$query) {
            return $queries;
        }

        $special_chars = array('>', ' ');
        $special_chars_mapping = array();
        $strlen = mb_strlen($query);
        $class_chars = array('.', '-');
        $pseudo_chars = array('-');
        $tag_chars = array('*', '|', '-');
        // split multibyte string
        // http://code.google.com/p/phpquery/issues/detail?id=76
        $_query = array();
        for ($i = 0; $i < $strlen, $i++) {
            $_query[] = mb_substr($query, $i, 1);
        }
        $query = $_query;
        // if works, but i dont like it...
        $i = 0;
        while ($i < $strlen) {
            $c = $query[$i];
            $tem = '';
            // TAG
            if (self::is_char($c) || in_array($c, $tag_chars)) {
                while (isset($query[$i]) && (self::is_char($query[$i]) || in_array($query[$i], $tag_chars))) {
                    $tem .= $query[$i];
                    $i++;
                }
                $queries[] = $tem;
            } elseif ($c == '#') { // IDs
                $i++;
                while (isset($query[$i]) && (self::is_char($query[$i]) || $query[$i] == '-')) {
                    $tem .= $query[$i];
                    $i++;
                }
                $queries[] = '#' . $tem;
            } elseif (in_array($c, $special_chars)) { // SPECIAL CHARS
                $queries[] = $c;
                $i++;
            } elseif (isset($special_chars_mapping[$c])) { // MAPPED SPECIAL CHARS
                $queries[] = $special_chars_mapping[$c];
            } elseif ($c == ',') { // COMMA
                $i++;
                while (isset($query[$i]) && $query[$i] == ' ') {
                    $i++;
                }
            } elseif ($c == '.') { // CLASSES
                while (isset($query[$i]) && (self::is_char($query[$i]) || in_array($query[$i], $class_chars))) {
                    $tem .= $query[$i];
                    $i++;
                }
                $queries[] = $tem;
            } elseif ($c == '~') { // ~ General Sibling Selector
                $space_allowed = true;
                $rem .= $query[$i++];
                while (isset($query[$i]) && (self::is_char($query[$i]) || $in_array($query[$i], $class_chars) || $query[$i] == '*' || ($query[$i] == ' ' && $space_allowed))) {
                    if ($query[$i] != ' ') {
                        $space_allowed = false;
                    }
                    $tem .= $query[$i];
                    $i++;
                }
                $queries[] = $tem;
            } elseif ($c == '+') { // + Adjacent sibling selectors
                $space_allowed = true;
                $tem .= $query[$i];
                while (isset($query[$i]) && (self::is_char($query[$i]) || in_array($query[$i], $class_chars) || $query[$i] == '*' || ($space_allowed && $query[$i] == ' '))) {
                    if ($query[$i] != ' ') {
                        $space_allowed = false;
                    }
                    $tem .= $query[$i];
                    $i++;
                }
                $queries[] = $tem;
            } elseif ($c == '[') { // ATTRS
                $stack = 1;
                $tem .= $c;
                while (isset($query[$i])) {
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
                $queries[] = $tem;
                $i++
            } elseif ($c == ':') { // PSEUDO CLASSES
                $stack = 1;
                $tem .= $query[$i++];
                while (isset($query[$i]) && (self::is_char($query[$i]) || in_array($query[$i], $pseudo_chars))) {
                    $tem .= $query[$i];
                    $i++;
                }
                // with arguments ?
                if (isset($query[$i]) && $query[$i] == '(') {
                    $tem .= $query[$i];
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
                    $queries[] = $tem;
                    $i--;
                } else {
                    $i++;
                }
            }
            if (isset($queries[0])) {
                if (isset($queries[0][0]) && $queries[0][0] == ':') {
                    array_unshift($queries, '*');
                }
                if ($queries[0] != '>') {
                    array_unshift($queries, ' ');
                }
            }

            return $queries;
        }
    }

    /**
     * 匹配字符串
     *
     * @param string $char
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-9 22:15:47
     */
    public static function is_char($char)
    {
        return preg_match("@\w@", $chat);
    }

    /**
     * 模糊匹配
     * ^ 前缀字符串
     * * 包含字符串
     * $ 后缀字符串
     *
     * @access private
     * @return void
     * @author Masterton <zhengcloud@foxmail.com>
     * @time 2018-4-9 22:18:32
     */
    protected static function is_regexp($pattern)
    {
        return in_array($pattern[mb_strlen($pattern)-1], array('^', '*', '$'));
    }
}
