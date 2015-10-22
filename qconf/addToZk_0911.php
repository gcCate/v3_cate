<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-7-10
 * Time: 上午9:35
 * Desc: 从数据库读取属性数据，写入zk
 */

error_reporting(E_ALL);
ini_set('display_errors', 'ON');
require "./medoo.php";
require "./Zookeeper_Api.php";
$db_v3 = new medoo(array(
    'database_type' => 'mysql',
    'database_name' => 'gccateinfo',
    'server'        => '172.17.11.2',
    'username'      => 'gongchang',
    'password'      => 'gongchang123',
    //    'server' => '127.0.0.1',
    //    'username' => 'root',
    //    'password' => '123456',
    'port'          => 3306,
    'charset'       => 'utf8',
    'option'        => array(PDO::ATTR_CASE => PDO::CASE_NATURAL),
));
$zk     = new Zookeeper_Api('172.17.11.5:2181');
$cateid = 0;
while (true) {
    $cateArr = $db_v3->get('cate_cateinfo', '*', array('cateid[>]' => $cateid, 'ORDER' => 'cateid ASC', 'LIMIT' => 1));
    if (empty($cateArr)) {
        die("Over\r\n");
    } else {
        $cateid = $cateArr['cateid'];
    }
    echo $cateid, "\r\n";
    doJob($cateArr);
    $db_v3->clear();
}
//处理属性值
//doAttrVal();

//主要处理程序
function doJob($cateArr)
{
    global $db_v3, $zk;
    //获取能前置当分类的属性
    // if($cateArr['has_virtual']){
    //     $temp = $db_v3->get('cg_attrinfo', '*', array('AND'=>array('cateid'=>$cateArr['cateid'], 'is_precateid'=>1), 'LIMIT'=>1));
    //     if(!empty($temp)){
    //         $zk->set("/Qconf/Category/PreAttr/{$cateArr['cateid']}", json_encode($temp));
    //     }

    // }
    //获取普通属性
    // $temp = $db_v3->select('cg_attrinfo', '*', array('AND' => array('cateid' => $cateArr['cateid'], 'is_specattr' => 0, 'parentfid' => 0), 'ORDER' => 'sort asc'));
    $temp = $db_v3->select('cate_attrinfo', '*', array('AND' => array('cateid' => $cateArr['cateid'], 'parentfid' => 0), 'ORDER' => 'sort asc'));

    if (!empty($temp)) {
        $zk->set("/qconf/backcate/attr/{$cateArr['cateid']}", json_encode($temp, JSON_UNESCAPED_UNICODE));
//        foreach($temp as $value){
        //            $zk->set("/Qconf/Category/SingleAttr/{$value['fid']}", json_encode($value));
        //        }
    }

    //获取规格属性
    //     $temp = $db_v3->select('cg_attrinfo', '*', array('AND' => array('cateid' => $cateArr['cateid'], 'is_specattr' => 1, 'parentfid' => 0), 'ORDER' => 'sort asc'));
    //     if (!empty($temp)) {
    //         $zk->set("/Qconf/Category/SpecAttr/{$cateArr['cateid']}", json_encode($temp));
    // //        foreach($temp as $value){
    //         //            $zk->set("/Qconf/Category/SingleAttr/{$value['fid']}", json_encode($value));
    //         //        }
    //     }
    //根据父属性和属性值获得子属性
    $temp = $db_v3->select('cate_attrinfo', '*', array('AND' => array('cateid' => $cateArr['cateid'], 'parentfid[>]' => 0), 'ORDER' => 'sort asc'));
    if (!empty($temp)) {
        foreach ($temp as $value) {
            $zk->set("/qconf/backcate/childattr/{$value['parentfid']}/{$value['parentvname']}", json_encode($value, JSON_UNESCAPED_UNICODE));
//            $zk->set("/Qconf/Category/SingleAttr/{$value['fid']}", json_encode($value));
        }
    }
}
//处理属性值
// function doAttrVal()
// {
//     global $db_v3,$zk;
//     $vid = 0;
//     while(true){
//         $resArr = $db_v3->select('cg_attrval_kv', '*', array('vid[>]'=>$vid, 'ORDER'=>'vid asc', 'LIMIT'=>10));
//         if(empty($resArr))
//             die("Over!\r\n");
//         echo $vid,"\r\n";
//         foreach($resArr as $value){
//             ($value['vid']>$vid) && $vid = $value['vid'];
//             $zk->set("/Qconf/Category/AttrVal/{$value['vid']}", $value['vname']);
//         }
//     }
// }
