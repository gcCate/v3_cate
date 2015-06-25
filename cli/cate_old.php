<?php
    //采类目
    //就按照目前的结构，采三级，就这么写吧
    $cateid = 0;
    $appSecret ='FksHCjGHilv';
    $sign_str = 'param2/1/cn.alibaba.open/category.getCatListByParentId/1016611parentCategoryID'.$cateid;
    $code_sign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $appSecret, true)));
    //阿里开放的接口
    $url = 'http://gw.open.1688.com/openapi/param2/1/cn.alibaba.open/category.getCatListByParentId/1016611?parentCategoryID='.$cateid.'&_aop_signature=';
    echo $url.$code_sign;die('123');
    $json = curl($url.$code_sign);
    $result = json_decode($json, true);
    //一级
    $sql1 = '';
    foreach ($result['result']['toReturn'] as $key => $value) {
        $value['leaf'] = empty($value['leaf']) ? 0 : 1;
        $sql1 .= "INSERT INTO `cn_alicate1` (`cateid`, `name`, `isLeaf`, `level`, `parentid`) VALUES ";
        $sql1 .= "({$value['catsId']}, '{$value['catsName']}', {$value['leaf']}, 1, 0);";
        $sql1 .= "\r\n";
        $ids[] = array('id'=>$value['catsId'],'leaf'=>$value['leaf']);//一级分类

    }
    file_put_contents('sql_jiekou1.sql', $sql1);
    //print_r($ids);
    //二级
    $sql2 = '';
    foreach ($ids as $k => $v) {
        $cateid = $v['id'];
        $appSecret ='FksHCjGHilv';
        $sign_str = 'param2/1/cn.alibaba.open/category.getCatListByParentId/1016611parentCategoryID'.$cateid;
        $code_sign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $appSecret, true)));

        $url = 'http://gw.open.1688.com/openapi/param2/1/cn.alibaba.open/category.getCatListByParentId/1016611?parentCategoryID='.$cateid.'&_aop_signature=';
           
        $json = curl($url.$code_sign);
        $result = json_decode($json, true);

        foreach ($result['result']['toReturn'] as $key2 => $value2) {
            $value2['leaf'] = empty($value2['leaf']) ? 0 : 1;
            //$ids2[$v['id']][] = array('id'=>$value2['catsId'],'leaf'=>$value2['leaf']);//二级分类
            $ids2[] = array('id'=>$value2['catsId'],'leaf'=>$value2['leaf']);//二级分类
            $sql2 .= "INSERT INTO `cn_alicate1` (`cateid`, `name`, `isLeaf`, `level`, `parentid`) VALUES ";
            $sql2 .= "({$value2['catsId']}, '{$value2['catsName']}', {$value2['leaf']}, 2, {$cateid});";
            $sql2 .= "\r\n";
        }
    }
    file_put_contents('sql_jiekou2.sql', $sql2);
   
    //print_r($ids2);
    //三级
    $sql3 = '';
    foreach ($ids2 as $k3 => $v3) {
        $cateid = $v3['id'];
        if (empty($v3['leaf'])) {
            $appSecret ='FksHCjGHilv';
            $sign_str = 'param2/1/cn.alibaba.open/category.getCatListByParentId/1016611parentCategoryID'.$cateid;
            $code_sign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $appSecret, true)));

            $url = 'http://gw.open.1688.com/openapi/param2/1/cn.alibaba.open/category.getCatListByParentId/1016611?parentCategoryID='.$cateid.'&_aop_signature=';
               
            $json = curl($url.$code_sign);
            $result = json_decode($json, true);
            if (!isset($result['result']['toReturn'])) {
                print_r($result);
                print_r($v3);
                die;
            }
            
            foreach ($result['result']['toReturn'] as $key3 => $value3) {
                $value3['leaf'] = empty($value3['leaf']) ? 0 : 1;
                if ($value3['leaf'] == 0) {
                    die;
                }
                //$ids3[$v3['id']][] = array('id'=>$value3['catsId'],'leaf'=>$value3['leaf']);//二级分类
                $ids3[] = array('id'=>$value3['catsId'],'leaf'=>$value3['leaf']);//二级分类
                $sql3 .= "INSERT INTO `cn_alicate1` (`cateid`, `name`, `isLeaf`, `level`, `parentid`) VALUES ";
                $sql3 .= "({$value3['catsId']}, '{$value3['catsName']}', {$value3['leaf']}, 3, {$cateid});";
                $sql3 .= "\r\n";
            }
        }
        
    }
    file_put_contents('sql_jiekou3.sql', $sql3);

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