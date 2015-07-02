<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-6-30
 * Time: 下午3:10
 * Desc:  阿里发布属性处理到表中
 */
error_reporting(E_ALL);
ini_set('display_errors', 'ON');
require "../lib/medoo.php";
$db_ali = new medoo(array(
    'database_type' => 'mysql',
    'database_name' => 'v3_cate_ali',
    'server' => '192.168.8.18',
    'username' => 'root',
    'password' => 'gc7232275',
//    'server' => '127.0.0.1',
//    'username' => 'root',
//    'password' => '123456',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL)
));
$db_v3 = new medoo(array(
    'database_type' => 'mysql',
    'database_name' => 'v3_category',
    'server' => '192.168.8.18',
    'username' => 'root',
    'password' => 'gc7232275',
//    'server' => '127.0.0.1',
//    'username' => 'root',
//    'password' => '123456',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL)
));
//初始化数据库
init();die();
$id = 0;
$time = microtime(true);
while(true){
    //开始处理数据
    $resArr = $db_ali->get('productAttributesInfo', '*', array('id[>]' => $id, 'ORDER' => 'id asc', 'LIMIT' => 1));
    if(empty($resArr))
        die("Over!\r\n");
    else
        $id = $resArr['id'];
    echo $id,"\r\n";
    deal($resArr);
}
//初始化，将新表清空
function init()
{
    global $db_v3;
    $db_v3->query("truncate attr_new_ali");             //阿里属性和新属性对应表
    $db_v3->query("truncate cg_attrinfo");              //后台属性表
    $db_v3->query("truncate cg_attrinfo_front");        //前台属性表
    $db_v3->query("truncate cg_attrval_kv");            //属性键值表
    $db_v3->query("truncate  cg_frontattr_featureid");  //属性值唯一表
}
//主要处理程序
function deal($resArr)
{
    global $db_ali,$db_v3,$time;
    $db_v3->clear();
    $db_ali->clear();
    //查看是否属于虚拟分类
    $temp = $db_ali->get('attprepose', '*', array('catsId' => $resArr['fid'], 'LIMIT'=>1));
    //查找父属性id
    $parent = $db_ali->get('postatrribute', '*', array('AND' => array('fid' => $resArr['fid'], 'categoryId' => $resArr['categoryId']), 'LIMIT'=>1));
    //获取新分类
    $cateArr = $db_v3->get('cg_cateinfo', '*', array('alicateid' => $resArr['categoryId'], 'LIMIT'=>1));
    if(empty($cateArr)){
        print_r($resArr);
        die('wrong');
    }
    $data = array(
//        'fid',
        'fname'             => $resArr['name'],
        'unit'              => $resArr['unit'],
        'parentfid'         => !empty($parent['parentId']) ? $parent['parentId'] : 0,
        'has_childattr'     => !empty($resArr['childrenFids']) ? 1 : 0,
        'attrvalues'        => $resArr['values'],
        'cateid'            => $cateArr['cateid'],
        'aspect'            => $resArr['aspect'],
        'defaultvalueid'    => $resArr['defaultValueId'],
        'defaultvalue'      => $resArr['defaultValue'],
        'sort'              => $resArr['order'],
        'preposecateid'     => isset($temp['parentId']) ? $temp['parentId'] : 0,
        'is_specattr'       => $resArr['specAttr'],
        'is_keyattr'        => $resArr['keyAttr'],
        'is_need'           => $resArr['required'],
        'is_definedvalues'  => $resArr['supportDefinedValues'],
        'showtype'          => $resArr['showType'],
        'inputmaxlength'    => $resArr['inputMaxLength'],
    );
    //写入后台属性表
    $fid = $db_v3->insert('cg_attrinfo', $data);
//    echo "5:".(microtime(true)-$time)."\r\n";
    if($fid<=0){
        print_r($db_v3->error());
        die();
    }
    //属性键值表、featureId表
    if(!empty($resArr['values'])){
        $valArr = json_decode($resArr['values'], true);
        $keyArr = json_decode($resArr['valueIds'], true);
        if(!empty($valArr)){
            foreach($valArr as $k => $v){
                //属性键值表
                $data1[] = array('vid' => $keyArr[$k], 'vname' => $v);
                //featureId表
                $data2[] = array(
                    'fid'   => $fid,
                    'fname' => $resArr['name'],
                    'vid'   => $keyArr[$k],
                    'vname' => $v,
                );

            }
            $db_v3->insert('cg_attrval_kv', $data1);
            $db_v3->insert('cg_frontattr_featureid', $data2);
        }else{
            echo $resArr['values'];
            print_r($valArr);
        }
    }//echo "6:".(microtime(true)-$time)."\r\n";

    //阿里属性和新属性对应表
    $data = array('fid' => $fid, 'ali_fid' => $resArr['fid']);
    $db_v3->insert('attr_new_ali', $data);
    //将选择类，作为前台属性表
    if(in_array($resArr['showType'], array(1,2,3))){
        $data = array(
            'fid'           => $fid,
            'fname'         => $resArr['name'],
            'unit'          => $resArr['unit'],
            'parentid'     => !empty($parent['parentId']) ? $parent['parentId'] : 0,
            'attrvalues'    => $resArr['values'],
            'cateid'        => $cateArr['cateid'],
            'sort'          => $resArr['order'],
        );
        $i = $db_v3->insert('cg_attrinfo_front', $data);
        if($i<=0){
            print_r($db_v3->error());
            die();
        }
    }
}