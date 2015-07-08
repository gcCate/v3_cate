<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-7-8
 * Time: 上午9:23
 * Desc: 将工厂网属性合并到阿里属性上 83019   923208173
 */
error_reporting(E_ALL);
ini_set('display_errors', 'ON');
require "../lib/medoo.php";
$dbv3 = new medoo(array(
    'database_type' => 'mysql',
    'database_name' => 'v3_category',
    'server' => '192.168.8.189',
    'username' => 'root',
    'password' => '123456',
//    'server' => '127.0.0.1',
//    'username' => 'root',
//    'password' => '123456',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL)
));
$db_v3 = new medoo(array(
    'database_type' => 'mysql',
    'database_name' => 'v3_category_bak',
    'server' => '192.168.8.189',
    'username' => 'root',
    'password' => '123456',
//    'server' => '127.0.0.1',
//    'username' => 'root',
//    'password' => '123456',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL)
));

$cateid = 0;
while(true) {
    $cateArr = $db_v3->get('cg_cateinfo', '*', array('cateid[>]'=>$cateid, 'ORDER'=>'cateid asc', 'LIMIT'=>1));
    if(empty($cateArr)){
        die('Over');
    }else{
        $cateid = $cateArr['cateid'];
    }
    if($cateArr['is_leaf'] != 1) continue;
    echo $cateid,"\r\n";
    do_work($cateArr);
    $db_v3->clear();
    $dbv3->clear();
}

//处理程序
function do_work($cateArr)
{
    //注意子属性的对应
    global $db_v3,$dbv3;
    //获取新属性
    $newAttArr = $db_v3->select('cg_attrinfo', '*', array('cateid'=>$cateArr['cateid']));
    $gcCateId = 0;
    $temp = $db_v3->get('correspond', '*', array('cate3_new'=>$cateArr['cateid']));
    if(empty($temp)){
        $temp = $db_v3->get('correspond', '*', array('cate2_new'=>$cateArr['cateid']));
        !empty($temp) && $gcCateId = $temp['cate2_gc'];
    }else{
        $gcCateId = $temp['cate3_gc'];
    }
    //获取工厂网属性
    if($gcCateId>0)
        $gcAttArr = $db_v3->select('cg_attrinfo_gc', '*', array('cateid'=>$gcCateId));
    else
        $gcAttArr = array();
    //对比区别
    if(!empty($newAttArr)){
        foreach($newAttArr as $new){
            $isMatch = false;
            if(!empty($gcAttArr)) {
                foreach ($gcAttArr as $gc) {
                    if ($new['fname'] == $gc['fname']) {
                        $isMatch = true;
                        //匹配成功 共有的
                        $dbv3->update('cg_attrinfo', array('type'=>1), array('fid' => $new['fid']));
                        $db_v3->update('cg_attrinfo_gc', array('new_fid'=>$new['fid']), array('fid'=>$gc['fid']));
                        break;
                    }
                }
            }
            if ($isMatch == false) {//阿里独有的
                $dbv3->update('cg_attrinfo', array('type'=>1), array('fid' => $new['fid']));
            }
        }
    }

    if(!empty($gcAttArr)) {
        foreach ($gcAttArr as $gc) {
            $isMatch = false;
            if(!empty($newAttArr)){
                foreach ($newAttArr as $new) {
                    if ($new['fname'] == $gc['fname']) {
                        $isMatch = true;
                        break;
                    }
                }
            }
            if ($isMatch == false) {//工厂网独有的
                //将工厂网数据合并过去
                $data = $gc;
                unset($data['fid']);
                unset($data['attid']);
                unset($data['new_fid']);
                $data['type'] = 3;
                $fid = $dbv3->insert('cg_attrinfo', $data);
                if($fid<=0){
                    print_r($dbv3->error());die();
                }
                $db_v3->update('cg_attrinfo_gc', array('new_fid'=>$fid), array('fid'=>$gc['fid']));
                //处理属性值
                $attValArr = json_decode($data['attrvalues'], true);
                if(!empty($attValArr)){
                    foreach($attValArr as $value){
                        $temp = $db_v3->get('attrval_new_gc', '*', array('new_vid'=>$value['vid']));
                        if(empty($temp)){//不存在写入
                            $vid = $dbv3->insert('cg_attrval_kv', array('vname' =>$value['value']));
                            //写入对应表
                            $db_v3->insert('attrval_new_gc', array('new_vid'=>$vid, 'gc_vid'=>$value['vid']));
                        }
                    }
                }
            }
        }
    }
}