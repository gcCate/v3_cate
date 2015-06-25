<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-6-8
 * Time: 下午4:58
 */

    define('ROOT', dirname(__FILE__));
    error_reporting(E_ALL ^ E_NOTICE);
    require_once ROOT.'/../lib/src/QcloudApi/QcloudApi.php';
    require_once ROOT.'/../lib/medoo.php';
    $db = new medoo(array(
        'database_type' => 'mysql',
        'database_name' => 'gcdv2_front',
        'server'        => '182.254.147.104',
        'username'      => 'root',
        'password'      => '7232275',
        'port'          => 3306,
        'charset'       => 'utf8',
        'option'        => array(PDO::ATTR_CASE => PDO::CASE_NATURAL)
    ));
    $config = array(
        'SecretId'       => 'AKIDIqC1J4xeNtF09aahQM1nqor0DDMRRlhw',
        'SecretKey'      => '7rIjwLo1wfmJAVWw19IBE7bjeguwZDUL',
        'RequestMethod'  => 'POST',
        'DefaultRegion'  => 'gz');
    $cvm = QcloudApi::load(QcloudApi::MODULE_CVM, $config);

    //主要处理代码
    $id = 28;
    $num = 100;
    while(true){
        $resArr = array();
        $field  = array('id','truetime','lastdotime','title','newstime','status','is_good','siteid','pic','domain','brandid','goods_count');
        $where  = array('status'=>1, 'LIMIT'=>array($id*$num, $num));
        $resArr = $db->select('phome_ecms_brands', $field, $where);
        if(empty($resArr)){
            die('over');
        }else{
            echo $id,"\r\n";
            $id++;
        }
        foreach($resArr as $key => $value){
            $value['isGood'] = $value['is_good'];
            unset($value['is_good']);
            $value['goodsCount'] = $value['goods_count'];
            unset($value['goods_count']);
            $resArr[$key] = $value;
        }
        add($resArr);
    }

    //连接云搜接口
    function add($data)
    {
        if(empty($data) || !is_array($data))
            return 0;

        global $cvm;
        $arr = array(
            'appId'     => 26530002,
            'op_type'   => 'add',
            'contents'  => $data
        );
        $a = $cvm->DataManipulation($arr);
        //$a = $cvm->generateUrl('DataManipulation', $arr);//只生成请求url,不发起请求
        if ($a === false) {
            print_r($cvm->getError());
            die();
        } else {
            echo "add lines ",count($data),"\r\n";
        }

    }

    function test()
    {
//        global $db
//        $sqlStr  = "SELECT `id`, `truetime`, `lastdotime`, `title`, `newstime`, `status`, `is_good`, `siteid`, `pic`, `domain`, `brandid`, ";
//        $sqlStr .= "`goods_count` FROM `phome_ecms_brands` WHERE `status` = 1 limit 100";
//        $field   =  array('id','truetime','lastdotime','title','newstime','status','is_good','siteid','pic','domain','brandid','goods_count');
//        $where   = array('status'=>1, 'LIMIT'=>array(0, 100));
//        $resArr  = $db->select('phome_ecms_brands', $field, $where);print_r($db->error());
//        foreach($resArr as $key => $value){
//            $value['isGood'] = $value['is_good'];
//            unset($value['is_good']);
//            $value['goodsCount'] = $value['goods_count'];
//            unset($value['goods_count']);
//            $resArr[$key] = $value;
//        }
//        $arr = array(
//            'content' => $resArr,
//            'op_type' => 'add',
//        );
//        file_put_contents(ROOT.'/1.txt', json_encode($arr));
    }


