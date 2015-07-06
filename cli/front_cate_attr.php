<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-7-2
 * Time: 下午7:14
 * Desc:  处理前置为分类的属性
 * Result:分类下属性 preposecateid大于0的属性，就是可以前置为分类的属性
 */
die();
error_reporting(E_ALL);
ini_set('display_errors', 'ON');
require "../lib/medoo.php";
$db_ali = new medoo(array(
    'database_type' => 'mysql',
    'database_name' => 'v3_cate_ali',
//    'server' => '192.168.8.18',
//    'username' => 'root',
//    'password' => 'gc7232275',
    'server' => '127.0.0.1',
    'username' => 'root',
    'password' => '123456',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL)
));
$db_v3 = new medoo(array(
    'database_type' => 'mysql',
    'database_name' => 'v3_category',
//    'server' => '192.168.8.18',
//    'username' => 'root',
//    'password' => 'gc7232275',
    'server' => '127.0.0.1',
    'username' => 'root',
    'password' => '123456',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL)
));
$id = 0;
while(true){
    $cateArr = $db_v3->get('cg_cateinfo', '*', array('has_virtual' => 1, 'ORDER' => "cateid asc", "LIMIT" => 1));
    if(empty($cateArr)){
        die("Over!\r\n");
    }else{
        $id = $cateArr['cateid'];
    }
    $attArr = $db_v3->get('cg_attrinfo', '*', array('AND' => array('cateid' => $cateArr['cateid'], 'preposecateid[>]' => 0)));
    if(empty($attArr)){
        echo "empty:";print_r($cateArr);
    }

}