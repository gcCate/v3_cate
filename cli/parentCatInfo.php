<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-6-17
 * Time: 上午11:05
 * Desc:  父类目表
 */
die();
error_reporting(E_ALL);
ini_set('display_errors', 'ON');
$conn = mysql_connect("192.168.8.18", "root", "gc7232275");
mysql_select_db("v3_cate_ali", $conn);
mysql_query("set names utf8", $conn);
//量级不多，直接查
$id = 0;
while(true){
    $sql = "select * from catInfo where id>{$id} order by id asc limit 1 ";
    $res = mysql_query($sql, $conn);
    $row = mysql_fetch_array($res, MYSQL_ASSOC);//print_r($row);
    if(empty($row)){
        die("over:{$id}");
    }else
        $id = $row['id'];
    echo $id,"\r\n";
    $parentArr = json_decode($row['parentCats'], true);

    foreach($parentArr as $value){
        $sql = "insert into parentCatInfo (catsId,parentCatsId,`order`) VALUE ({$row['catsId']}, {$value['parentCatsId']}, {$value['order']})";
//        echo $sql;
        mysql_query($sql, $conn);
    }
}
