<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-6-17
 * Time: 上午8:54
 * Desc:  采集类目调整
 */
//采类目
//就按照目前的结构，采三级，就这么写吧
$result = getInfo(1036617);
print_r($result);
die();
$result = getInfo(0);
$ids1 = array();
$ids2 = array();
$ids3 = array();
$ids4 = array();

//SELECT * FROM `catInfo`  group by catsId having count(*)>1

//一级
$sql1 = '';
foreach ($result['result']['toReturn'] as $key => $value) {
    $sql1 .= sql($value);
    $ids1[] = array('id'=>$value['catsId'],'leaf'=>$value['leaf']);//一级分类

}
file_put_contents('cate1.sql', $sql1);echo "一级over\r\n";

//二级
$sql2 = '';
foreach ($ids1 as $k => $v) {
    $result = getInfo($v['id']);
    foreach ($result['result']['toReturn'] as $key => $value) {
        $sql2 .= sql($value);
        $ids2[] = array('id'=>$value['catsId'],'leaf'=>$value['leaf']);//二级分类
    }
}
file_put_contents('cate2.sql', $sql2);echo "二级over\r\n";

//三级
$sql3 = '';
foreach ($ids2 as $k => $v) {
    if($v['leaf']==1) continue;
    if(empty($result['result']['toReturn'])) continue;
    foreach ($result['result']['toReturn'] as $key => $value) {
        $sql3 .= sql($value);
        $ids3[] = array('id'=>$value['catsId'],'leaf'=>$value['leaf']);//二级分类
    }
}
file_put_contents('cate3.sql', $sql3);echo "三级over\r\n";

//四级
//$sql4 = '';
//foreach ($ids3 as $k => $v) {
////    if($v['leaf']==1) continue;
//    $result = getInfo($v['id']);
//    if(empty($result['result']['toReturn'])) continue;
//    foreach ($result['result']['toReturn'] as $key => $value) {
//        $sql4 .= sql($value, $v['id']);
//        $ids4[] = array('id'=>$value['catsId'],'leaf'=>$value['leaf']);//二级分类
//    }
//}
//file_put_contents('cate3.sql', $sql4);
//print_r($ids4);
//构造采集
function getInfo($cateid)
{
    echo "run:{$cateid}\r\n";
    $appSecret ='FksHCjGHilv';
    $sign_str = 'param2/1/cn.alibaba.open/category.getCatListByParentId/1016611parentCategoryID'.$cateid;
    $code_sign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $appSecret, true)));
    //阿里开放的接口
    $url = 'http://gw.open.1688.com/openapi/param2/1/cn.alibaba.open/category.getCatListByParentId/1016611?parentCategoryID='.$cateid.'&_aop_signature=';
    $json = curl($url.$code_sign);echo $url.$code_sign;
    $result = json_decode($json, true);
    return $result;
}

//构造SQL
function sql($value)
{
    $value['isSupportOnlineTrade']  = ($value['supportOnlineTrade']==true) ? 1 : 0;
    $value['isSupportSKUPrice']     = ($value['supportSKUPrice']==true) ? 1 : 0;
    $value['isLeaf']                = ($value['leaf']==true) ? 1 : 0;
    $value['applySPU']              = intval($value['applySpu']);
    $value['supportMixWholesale']   = intval($value['supportMixWholesale']);
    $value['batchPost']             = intval($value['batchPost']);
    $value['spuPriceExt']           = intval($value['spuPriceExt']);
    $value['applyRealPrice']        = intval($value['applyRealPrice']);
    //json
    $value['parentCats']            = json_encode($value['parentCats']);
    $value['childrenCats']          = json_encode($value['childrenCats']);


    $sql  = "INSERT INTO catInfo (`catsId`,`catsName`,`catsDescription`,`isLeaf`,`tradeType`,`parentCats`,`applySPU`,`isSupportOnlineTrade`,";
    $sql .= "`isSupportSKUPrice`,`supportMixWholesale`,`batchPost`,`childrenCats`,`spuPriceExt`,`applyRealPrice`) VALUE ";
    $sql .= "({$value['catsId']},'{$value['catsName']}','{$value['catsDescription']}',{$value['isLeaf']},{$value['tradeType']},";
    $sql .= "'{$value['parentCats']}',{$value['applySPU']},{$value['isSupportOnlineTrade']},{$value['isSupportSKUPrice']},";
    $sql .= "{$value['supportMixWholesale']},{$value['batchPost']},'{$value['childrenCats']}',{$value['spuPriceExt']},";
    $sql .= "{$value['applyRealPrice']});";
    $sql .= "\r\n";
    return $sql;
}
//batchPost childrenCats spuPriceExt applyRealPrice

function curl($url, $method = '', $post = '', $returnHeaderInfo = false, $timeout = 2)
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