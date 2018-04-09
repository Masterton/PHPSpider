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

namespace PHPSpider\core;

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
