<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-6-18
 * Time: 下午4:50
 * Desc:  属性的属性，子属性
 */
error_reporting(E_ALL);
ini_set('display_errors', 'ON');
require "../lib/medoo.php";
$db = new medoo(array(
    'database_type' => 'mysql',
    'database_name' => 'v3_cate_ali',
    'server' => '192.168.8.18',
    'username' => 'root',
    'password' => 'gc7232275',
    'port' => 3306,
    'charset' => 'utf8',
    'option' => array(PDO::ATTR_CASE => PDO::CASE_NATURAL)
));

$id = 0;
while(true) {
    echo 'id:' . $id . "\r\n";
    //获取叶子类目
    $where = array('AND' => array('catsId[>]'=>$id, 'leaf'=>1), 'ORDER' => 'catsId asc', 'LIMIT' => 1);
    $resArr = $db->get('offercatsInfo', '*', $where);
    if (empty($resArr)) {
        die('over!\r\n');
    }
    $id = $resArr['catsId'];
    //获取叶子类目的属性
    $where = array('AND' => array('categoryId' => $resArr['catsId'], 'childrenFids[!]' => ''));
    $res = $db->select('postatrribute', '*', $where);
    if(!empty($res)){
        foreach($res as $value){
            if(!empty($value['childrenFids'])){
                $value['featureIdValues'] = preg_replace('/u([0-9a-f]{4})/', "\\u$1", $value['featureIdValues']);
                $feaArr = json_decode($value['featureIdValues'], true);
                $chilArr = json_decode($value['childrenFids'], true);
                if(empty($chilArr)) continue;
                foreach($feaArr as $fea){
                    $str = "{$value['fid']}:{$fea['vid']}";echo $str,"\r\n";
                    $r = getInfo($resArr['catsId'], $str);
                    $i = insert($r['categoryFeatures']);
//                    foreach($chilArr as $v){
//                        $t = $db->get('postatrribute', '*', array('fid' => $v));
//                        if(isset($t['childrenFids']) && !empty($t['childrenFids'])){
//                            $t['featureIdValues'] = preg_replace('/u([0-9a-f]{4})/', "\\u$1", $t['featureIdValues']);
//                            $a = json_decode($t['featureIdValues'], true);
//                            foreach($a as $aa){
//                                $str = "{$value['fid']}:{$fea['vid']}>$v:{$aa['vid']}";echo $str,"\r\n";
//                                $r = getInfo($resArr['catsId'], $str);
//                                $i = insert($r['categoryFeatures']);
//                            }
//                        }
//                    }
                }
            }else{
                continue;
            }
        }
    }
}
function insert($data)
{
    global $db;
    if(empty($data)) return;
    foreach($data as $value){
        print_r($value);
        //写入键值表
        if(!empty($value['featureIdValues'])){
            foreach($value['featureIdValues'] as $v){
                $temp = $db->get('CategoryFeatureIdValue', '*', array('vid' => $v['vid']));
                if(empty($temp)){
                    $i = $db->insert('CategoryFeatureIdValue', $v);
                    if($i<=0) print_r($db->error());
                }
            }
        }
        //写入主表
        $temp = $db->get('CategoryFeature', '*', array('fid' => $value['fid']));
        if(empty($temp)) {
            $value['featureIdValues'] = json_encode($value['featureIdValues']);
            $value['childrenFids'] = json_encode($value['childrenFids']);
            $i = $db->insert('CategoryFeature', $value);
            if ($i <= 0) {
                print_r($db->error());
                die('DB errors');
            }
        }
    }
}
//请求数据
function getInfo($cateid, $keyAttr)
{
//    $cateid  = 1031910;
//    $keyAttr = '100000691:46874>7108:21958';
//    echo "run:{$cateid}\r\n";
    $appSecret ='FksHCjGHilv';
    $sign_str = 'param2/1/cn.alibaba.open/category.level.attr.get/1016611access_token6a05bcb8-90f6-48e4-b91a-3899819b6f5bcatId'.$cateid.'pathValues'.$keyAttr;
    $code_sign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $appSecret, true)));
    $code_sign.= "&access_token=6a05bcb8-90f6-48e4-b91a-3899819b6f5b";
    //阿里开放的接口
    $url = 'http://gw.open.1688.com/openapi/param2/1/cn.alibaba.open/category.level.attr.get/1016611?catId='.$cateid.'&pathValues='.$keyAttr.'&_aop_signature=';
//    echo $url.$code_sign,"\r\n";//die();
    $json = curl($url.$code_sign);
    $result = json_decode($json, true);
    return $result;
}

function curl($url, $method = '', $post = '', $returnHeaderInfo = false, $timeout = 10)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);//设置超时时间,单位秒
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.8) Gecko/20100722 Firefox/3.6.8");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    if ($method == 'post') {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    }
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $str = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    unset($curl);
    if(!$str)
    {
        return false;
    }
    //返回头信息
    if ($returnHeaderInfo) {
        return array($httpCode, $str);
    }
    return $str;
}