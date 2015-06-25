<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-6-18
 * Time: 上午11:15
 * Desc:  根据产品属性获取信息
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
while(true){
    echo 'id:'.$id."\r\n";
    $where  = array('AND' => array('id[>]' => $id,'keyAttr' => 1), 'ORDER'=>'id asc', 'LIMIT' => 1);
    $resArr = $db->get('productAttributesInfo', '*', $where);
    if(empty($resArr)){
        die('over!\r\n');
    }
    $id = $resArr['id'];
    $valueArr = !empty($resArr['values']) ? json_decode($resArr['values'], true) : array();
    if(!empty($valueArr)){//print_r($resArr);
        foreach($valueArr as $value){
            $result = getInfo($resArr['categoryId'], "{$resArr['fid']}:$value");
            //print_r($result);//die();
            if(!empty($result['result']['toReturn'])){
                if(!empty($result['result']['toReturn'])){
                    foreach($result['result']['toReturn'] as $v) {
                        if(!empty($v['standardSpuAttrValues'])){
                            foreach($v['standardSpuAttrValues'] as $vv){
                                if(isset($vv['name']) || isset($vv['key'])) die('exists');
                                $data = array(
                                    'catsId' => $resArr['categoryId'],
                                    'spuId'  => $v['spuId'],
                                    'fid'    => $vv['fid'],
                                    'unit'   => isset($vv['unit']) ? $vv['unit'] : '',
                                    'value'  => $vv['value'],
                                    'name'   => '',
                                    'key'    => $resArr['keyAttr'],
                                );
                                $i = $db->insert('standardSpuAttrValues', $data);
                                if($i<=0){
                                    print_r($db->error());
                                    die();
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}


function getInfo($cateid, $keyAttr)
{
//    echo "run:{$cateid}\r\n";
    $appSecret ='FksHCjGHilv';
    $sign_str = 'param2/1/cn.alibaba.open/spubyattribute.get/1016611categoryID'.$cateid.'keyAttributes'.$keyAttr;
    $code_sign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $appSecret, true)));
    //阿里开放的接口
    $url = 'http://gw.open.1688.com/openapi/param2/1/cn.alibaba.open/spubyattribute.get/1016611?categoryID='.$cateid.'&keyAttributes='.$keyAttr.'&_aop_signature=';
//    echo $url.$code_sign;die();
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
