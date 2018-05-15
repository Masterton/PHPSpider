<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PHPSpider\Core\PHPSpider;
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
    'name' => '马蜂窝',
    'tasknum' => 1,
    // 'save_running_state' => true,
    // 'multiserver' => true,
    'log_show' => true,
    // 'save_running_state' => false,
    // 'multiserver' => true,
    'domains' => array(
        'www.mafengwo.cn'
    ),
    'scan_urls' => array(
        "http://www.mafengwo.cn/travel-scenic-spot/mafengwo/10088.html"
    ),
    'list_url_regexes' => array(
        "http://www.mafengwo.cn/mdd/base/list/pagedata_citylist\?page=\d+",
        "http://www.mafengwo.cn/gonglve/ajax.php\?act=get_travellist\&mddid=\d+",
    ),
    'content_url_regexes' => array(
        "http://www.mafengwo.cn/i/\d+.html"
    ),
    //'export' => array(
        //'type' => 'db', 
        //'table' => 'meinv_content',
    //),
    /*'queue_config' => array(
        'host'      => '127.0.0.1',
        'port'      => 6379,
        'pass'      => '',
        'db'        => 5,
        'prefix'    => 'phpspider',
        'timeout'   => 30,
    ),*/
    /*'db_config' => array(
        'host'  => '127.0.0.1',
        'port'  => 3306,
        'user'  => 'test',
        'pass'  => 'test',
        'name'  => 'test',
    ),*/
    'fields' => array(
        // 标题
        array(
            'name' => "name",
            'selector' => "//h1[contains(@class,'headtext')]",
            //'selector' => "//div[@id='Article']//h1",
            'required' => true,
        ),
        // 分类
        array(
            'name' => "city",
            'selector' => "//div[contains(@class,'relation_mdd')]//a",
            'required' => true,
        ),
        // 出发时间
        array(
            'name' => "date",
            'selector' => "//li[contains(@class,'time')]",
            'required' => true,
        ),
    ),
);

$spider = new PHPSpider($configs);
$spider->start();
print_r("<pre>");
print_r($spider::$configs['name']);
exit;