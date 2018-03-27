<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PHPSpider\core\PHPSpider;
// use PHPSpider\core\requests;
// use PHPSpider\core\db;

/* Do NOT delete this comment */
/* 不要删除这段注释 */

//$url = "https://istore.oppomobile.com/storeapp/home?size=10&start=0";
//$data = requests::get($url);
//$info = requests::$info;
//print_r($info);
//exit;

$configs = array(
    'name' => 'test',
    'tasknum' => 1,
    //'multiserver' => true,
    'log_show' => true,
    //'save_running_state' => false,
    'domains' => array(
        'www.test.com'
    ),
    'scan_urls' => array(
        "http://www.test.com/qingchunmeinv/"
    ),
    'list_url_regexes' => array(
        "http://www.test.com/qingchunmeinv/index_\d+.html"
    ),
    'content_url_regexes' => array(
        "http://www.test.com/qingchunmeinv/\d+.html"
    ),
    //'export' => array(
        //'type' => 'db', 
        //'table' => 'meinv_content',
    //),
    'db_config' => array(
        'host'  => '127.0.0.1',
        'port'  => 3306,
        'user'  => 'test',
        'pass'  => 'test',
        'name'  => 'test',
    ),
    'fields' => array(),
);

$spider = new PHPSpider($configs);
print_r("<pre>");
print_r($spider::$configs);
exit;