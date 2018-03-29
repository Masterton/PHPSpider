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
    'save_running_state' => true,
    //'multiserver' => true,
    'log_show' => true,
    //'save_running_state' => false,
    // 'multiserver' => true,
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
    'queue_config' => array(
        'host'      => '127.0.0.1',
        'port'      => 6379,
        'pass'      => '',
        'db'        => 5,
        'prefix'    => 'phpspider',
        'timeout'   => 30,
    ),
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
$spider->start();
print_r("<pre>");
print_r($spider::$configs['name']);
exit;