<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-7-3
 * Time: 下午2:55
 * Desc:  将子属性转到属性表(max_fid 77082)、属性值表(总数46577)
 */
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
$id = 5249;
while(true){
    echo $id,"\r\n";
    $feaArr = $db_ali->get('CategoryFeature', '*', array('id[>]'=>$id, 'ORDER'=>'id asc', 'LIMIT'=>1));
    if(empty($feaArr)){
        die("Over!\r\n");
    }else{
        $id = $feaArr['id'];
    }
    //查阿里属性表，获得对应新属性id
    $aliAttrArr = $db_ali->get('productAttributesInfo', '*', array('AND'=>array('fid'=>$feaArr['parentId'], 'categoryId'=>$feaArr['categoryId']),'LIMIT'=>1));
    if(empty($aliAttrArr)){//5249 开始出错
//        print_r($feaArr);die();
//        continue;
        //如果在老的属性表里找不到，表明父属性是新增加入的，
        $temp = $db_ali->get('attr_new_fea', '*', array('AND' => array('old_fid'=>$feaArr['parentId'], 'old_cateid'=>$feaArr['categoryId'])));
        if(empty($temp)){
            print_r($feaArr);die();
        }
    }else {
        //通过对应表找到新的属性
        $temp = $db_v3->get('attr_new_ali', '*', array('ali_fid' => $aliAttrArr['id']));
        if (empty($temp)) {
            die('ERROR');
        }
    }
    //修改上一级属性has_childattr字段
    $db_v3->update('cg_attrinfo', array('has_childattr' => 1), array('fid' => $temp['fid']));
    $attArr = $db_v3->get('cg_attrinfo', '*', array('fid' => $temp['fid']));
    //写入新的属性
    $data = array(
        'fname'             => $feaArr['name'],
        'unit'              => $feaArr['unit'],
        'parentfid'         => $attArr['fid'],
        'parentvid'         => $feaArr['valueId'],
        'has_childattr'     => ($feaArr['childrenFids']!='[]') ? 1 : 0,
        'attrvalues'        => $feaArr['featureIdValues'],
        'cateid'            => $attArr['cateid'],
        'aspect'            => 0,
        'defaultvalueid'    => '',
        'defaultvalue'      => '',
        'sort'              => $feaArr['order'],
        'preposecateid'     => 0,
        'is_specattr'       => $feaArr['isSpecAttr'],
        'is_keyattr'        => $feaArr['isKeyAttr'],
        'is_need'           => $feaArr['isNeeded'],
        'is_definedvalues'  => $feaArr['isSupportDefinedValues'],
        'showtype'          => $feaArr['inputType'],
        'inputmaxlength'    => 50,
    );
    $i = $db_v3->insert('cg_attrinfo', $data);

    if($i<=0){print_r($feaArr);print_r($aliAttrArr);
        print_r($data);
        print_r($db_v3->error());die();
    }else{
        //新增的属性，必须加到新的属性对应表中
        $data = array(
            'fid'       => $i,
            'old_id'    => $feaArr['id'],
            'old_fid'   => $feaArr['fid'],
            'old_cateid'=> $feaArr['categoryId'],
        );
        $db_ali->insert('attr_new_fea', $data);
    }
    //写入新的属性键值
    $feaValArr = json_decode($feaArr['featureIdValues'], true);
    if(!empty($feaValArr)){
        foreach($feaValArr as $value){
            if($value['vid'] >0){
                $data = array('vid'=>$value['vid'], 'vname'=>$value['value']);
                $i = $db_v3->insert('cg_attrval_kv', $data);
                if($i<=0){
                    print_r($data);
                    print_r($db_v3->error());
                }
            }
        }
    }
    $db_v3->clear();
    $db_ali->clear();
}