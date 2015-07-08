<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-7-6
 * Time: 上午11:25
 * Desc: 工厂网老类目和新类目关系
 */
die();
error_reporting(E_ALL);
ini_set('display_errors', 'ON');
require "../lib/medoo.php";
//$db_gc = new medoo(array(
//    'database_type' => 'mysql',
//    'database_name' => 'gongchangcate',
//    'server' => '192.168.8.189',
//    'username' => 'root',
//    'password' => '123456',
////    'server' => '127.0.0.1',
////    'username' => 'root',
////    'password' => '123456',
//    'port' => 3306,
//    'charset' => 'utf8',
//    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL)
//));
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
}

//处理程序
function do_work($cateArr)
{
    //注意子属性的对应
    global $db_v3,$db_gc;
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

    //先对应一级
    $comAttArr  = array();//共有属性
    $newOnlyArr = array();//新类目独有
    $gcOnlyArr  = array();//工厂网独有
    if(!empty($newAttArr)){
        foreach($newAttArr as $new){
            $isMatch = false;
            if(!empty($gcAttArr)) {
                foreach ($gcAttArr as $gc) {
                    if ($new['fname'] == $gc['fname']) {
                        $comAttArr[] = $new;
                        $isMatch = true;
                        $data = array(
                            'new_cateid'=> $cateArr['cateid'],
                            'gc_cateid' => $gcCateId,
                            'new_fid'   => $new['fid'],
                            'gc_fid'    => $gc['fid'],
                        );
                        $db_v3->insert('attr_new_gc', $data);
                        break;
                    }
                }
            }
            if ($isMatch == false) {
                $newOnlyArr[] = $new;
                $data = array(
                    'new_cateid'=> $cateArr['cateid'],
                    'gc_cateid' => $gcCateId,
                    'new_fid'   => $new['fid'],
                    'gc_fid'    => 0,
                );
                $db_v3->insert('attr_new_gc', $data);
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
            if ($isMatch == false) {
                $gcOnlyArr[] = $gc;
                $data = array(
                    'new_cateid' => $cateArr['cateid'],
                    'gc_cateid' => $gcCateId,
                    'new_fid' => 0,
                    'gc_fid' => $gc['fid'],
                );
                $db_v3->insert('attr_new_gc', $data);
            }
        }
    }
//    print_r($db_v3->error());
//        print_r($comAttArr);
//        print_r($newOnlyArr);
//        print_r($gcOnlyArr);
        //后对应父子分类
}