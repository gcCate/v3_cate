<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-7-7
 * Time: 下午2:27
 * Desc:  将工厂网属性按新的属性表存储
 */
die();
error_reporting(E_ALL);
ini_set('display_errors', 'ON');
require "../lib/medoo.php";
$db_gc = new medoo(array(
    'database_type' => 'mysql',
    'database_name' => 'gongchangcate',
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

//处理普通属性  96768   41172
//$cateid = 0;
//while(true){
//    $cateArr = $db_gc->get('f_cate', '*', array('cateid[>]'=>$cateid, 'ORDER'=>'cateid asc', 'LIMIT'=>1));
//    if(empty($cateArr)){
//        die("Over!\r\n");
//    }else{
//        $cateid = $cateArr['cateid'];
//    }
//    echo $cateid,"\r\n";
//    doJob($cateArr);
//    $db_gc->clear();
//    $db_v3->clear();
//}

//处理规格属性
//doGuige();

//处理程序
function doJob($cateArr)
{
    global $db_gc,$db_v3;
    //获取属性
    $attArr = $db_gc->select("f_property", '*', array('cateid'=>$cateArr['cateid']));
    if(empty($attArr)){
        return;
    }
    //获取属性名、属性值
    $temp     = array();
    $attIdArr = array();
    $valIdArr = array();
    foreach($attArr as $value){
        $temp[$value['attid']][] = $value;
        $attIdArr[] = $value['attid'];
        $valIdArr[] = $value['attvid'];
    }
    $attIdArr = array_unique($attIdArr);
    $valIdArr = array_unique($valIdArr);
    $attArr   = $temp;
    //获取属性名
    $attNameArr = array();
    $temp = $db_gc->select('f_attrsname', '*', array('attid'=>$attIdArr));
    foreach ($temp as $value) {
        $attNameArr[$value['attid']] = $value['attname'];
    }
    //获取属性值
    $attValArr = array();
    $temp = $db_gc->select('f_attrsvalue', '*', array('attvid'=>$valIdArr));
    foreach($temp as $value){
        $attValArr[$value['attvid']] = $value['attvalue'];
    }
    //将属性写入表中
    foreach($attArr as $value){
        $data = array(
            'attid'               => $value[0]['attid'],
            'fname'             => $attNameArr[$value[0]['attid']],
            'unit'              => $value[0]['unit'],
            'parentfid'         => $value[0]['parentid'],
            'parentvid'         => $value[0]['parentvid'],
            'has_childattr'     => ($value[0]['haschild']=='Y') ? 1 : 0,
            'attrvalues'        => '',
            'cateid'            => $value[0]['cateid'],
            'aspect'            => 0,
            'defaultvalueid'    => 0,
            'defaultvalue'      => '',
            'sort'              => $value[0]['order'],
            'is_precateid'      => 0,
            'is_specattr'       => 0,
            'is_keyattr'        => 0,
            'is_need'           => $value[0]['isneeded'],
            'is_definedvalues'  => 0,
            'showtype'          => $value[0]['inputtype'],
            'inputmaxlength'    => $value[0]['maxlength'],
        );
        if(count($value)>1){
            //如果属性值比较多，将属性值存入属性值表中，同时属性表冗余
            $attrvaluesArr = array();
            foreach($value as $v){
                if($v['attvid']>0){
                    $attrvaluesArr[] = array('value'=>$attValArr[$v['attvid']], 'vid'=>$v['attvid']);
                    $db_v3->insert('cg_attrval_kv_gc', array('vid'=>$v['attvid'],'vname'=>$attValArr[$v['attvid']]));
                }else{
                    $data['is_definedvalues'] = 1;
                }
            }
            $data['attrvalues'] = json_encode($attrvaluesArr);
        }
        $i = $db_v3->insert('cg_attrinfo_gc', $data);
        if($i<=0){
            print_r($db_v3->error());
            die();
        }
    }
}
function doGuige()
{
    global $db_gc,$db_v3;
    $cateid = 0;
    while(true){
        $resArr = $db_gc->get('f_specproperty', '*', array('cateid[>]'=>$cateid,'ORDER'=>'cateid asc','LIMIT'=>1));
        if(empty($resArr)){
            die('Over!\r\n');
        }else{
            $cateid = $resArr['cateid'];
        }
        echo $cateid,"\r\n";
        $resArr = $db_gc->select('f_specproperty', '*', array('cateid'=>$cateid));
        if(empty($resArr)) continue;
        $attrArr = array();
        foreach($resArr as $value){
            $attrArr[$value['attid']][] = $value;
        }
        foreach($attrArr as $value){
            $data = array(
                'attid'             => $value[0]['attid'],
                'fname'             => $value[0]['attname'],
                'unit'              => $value[0]['unit'],
                'parentfid'         => $value[0]['parentid'],
                'parentvid'         => $value[0]['parentvid'],
                'has_childattr'     => ($value[0]['haschild']=='Y') ? 1 : 0,
                'attrvalues'        => '',
                'cateid'            => $value[0]['cateid'],
                'aspect'            => 0,
                'defaultvalueid'    => 0,
                'defaultvalue'      => '',
                'sort'              => $value[0]['sort'],
                'is_precateid'      => 0,
                'is_specattr'       => 1,
                'is_keyattr'        => 0,
                'is_need'           => $value[0]['isneeded'],
                'is_definedvalues'  => 0,
                'showtype'          => $value[0]['inputtype'],
                'inputmaxlength'    => $value[0]['maxlength'],
            );
            if(count($value)>1){
                $attrValArr = array();
                foreach($value as $v){
                    if(empty($v['attvalue'])) continue;
                    $attrValArr[] = array(
                        'value' => $v['attvalue'],
                        'vid' => $db_v3->insert('cg_attrval_kv_gc', array('vname'=>$v['attvalue']))
                    );
                }
                $data['attrvalues'] = json_encode($attrValArr);
            }
            $i = $db_v3->insert('cg_attrinfo_gc', $data);
            if($i<=0){
                print_r($db_v3->error());
                die();
            }
        }
        $db_gc->clear();
        $db_v3->clear();
    }
}