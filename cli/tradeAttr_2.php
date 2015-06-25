<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-6-17
 * Time: 下午1:47
 * Desc:  类目属性信息  单独一个表
 */

die('stop');
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

    $row = $db->get('catInfo','*',array('AND'=>array('id[>]'=>$id,'isSupportOnlineTrade'=>1,'isLeaf'=>1),'ORDER'=>'id ASC','LIMIT' => 1));
    $id = $row['id'];
    if(empty($row)){die("over:{$id}");}
    $resArr = getInfo($row['catsId']);

    if(!empty($resArr['result']['toReturn'])){
        foreach($resArr['result']['toReturn'] as $value){
            //先查询是否存在
//            $tmp = $db->get('tradeAttributesInfo', '*', array('fid' => $value['fid']));
//            if(empty($tmp)){//数据不存在添加
                $value['supportDefinedValues']  = intval($value['supportDefinedValues']);
                $value['keyAttr']               = intval($value['keyAttr']);
                $value['specExtendedAttr']      = intval($value['specExtendedAttr']);
                $value['specAttr']              = intval($value['specAttr']);
                $value['suggestionType']        = intval($value['suggestionType']);
                $value['hasChildAttr']          = intval($value['hasChildAttr']);
                $value['required']              = intval($value['required']);
                $value['specPicAttr']           = intval($value['specPicAttr']);
                (!isset($value['unit'])) && $value['unit'] = '';
                if(!isset($value['valueIds'])){
                    $value['valueIds'] = '[]';
                }else{
                    $value['valueIds'] = json_encode($value['valueIds']);
                }
                if(!isset($value['values'])){
                    $value['values'] = '[]';
                }else{
                    $value['values'] = json_encode($value['values']);
                }
                $value['categoryId'] = $row['catsId'];
                $i = $db->insert('tradeAttributesInfo_bank', $value);
                if($i<=0){
                    print_r($db->error());
                    die();
                }
//            }
//            $arr = array('fid' => $value['fid'], 'categoryId' => $row['catsId']);
//            $i = $db->insert('tradeAttr', $arr);
//            if($i<=0){
//                print_r($db->error());
//                die();
//            }
        }
    }else{
//        print_r();
    }
//    die();
//    foreach($parentArr as $value){
//        $sql = "insert into parentCatInfo (catsId,parentCatsId,`order`) VALUE ({$row['catsId']}, {$value['parentCatsId']}, {$value['order']})";
//        echo $sql;
//        mysql_query($sql, $conn);
//    }

}

function getInfo($cateid)
{
    echo "run:{$cateid}\r\n";
    $appSecret ='FksHCjGHilv';
    $sign_str = 'param2/1/cn.alibaba.open/tradeAttributes.get/1016611categoryID'.$cateid;
    $code_sign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $appSecret, true)));
    //阿里开放的接口
    $url = 'http://gw.open.1688.com/openapi/param2/1/cn.alibaba.open/tradeAttributes.get/1016611?categoryID='.$cateid.'&_aop_signature=';
    $json = curl($url.$code_sign);//echo $url.$code_sign;
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