<?php
	//采all属性
	$conn = mysql_connect("127.0.0.1", "root", "");
    mysql_select_db("gc", $conn);
    mysql_query("set names utf8", $conn);
    //量级不多，直接查
    $sql = "SELECT `cateid` FROM gc.cn_alicate1";
    $result = mysql_query($sql, $conn);
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC) ) {
        //echo $row1['catename']."\r\n";
        //三级类目
        $array[] = $row['cateid'];
    }


	$sql = '';
    // -1: 数字输入框; 0: 文本输入框（input）;1=下拉（list_box）;2=多选（check_box）;3=单选（radio）
	foreach ($array as $k => $v) {
		$cateid = $v;

	    $appSecret ='FksHCjGHilv';
	    $sign_str = 'param2/1/cn.alibaba.open/offerPostFeatures.get/1016611categoryID'.$cateid;
	    $code_sign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $appSecret, true)));

	    //阿里开放接口
	    $url = 'http://gw.open.1688.com/openapi/param2/1/cn.alibaba.open/offerPostFeatures.get/1016611?categoryID='.$cateid.'&_aop_signature='.$code_sign;
	   
	    $json = curl($url);
	    $result = json_decode($json, true);
	    $guige = $jiunit = array();
	    $b = array();
	    if (isset($result['result']['toReturn'])) {
	    	foreach ($result['result']['toReturn'] as $key => $value) {
	    		if (!empty($value['isSpecAttr'])) {
	    			// $value['featureIdValues'] = isset($value['featureIdValues']) ? $value['featureIdValues'] : array();
	    			// foreach ($value['featureIdValues'] as $k => $v) {
	    			// 	if (!in_array($v['value'], $a)) {
	    			// 		$a[] = $v['value'];
	    			// 	}
	    				
	    			// }
	    			// if (!in_array($value['name'], $a)) {
	    			// 	$a[] = $value['name'];
	    			// }
	    			$guige[] = $value;
	    		}
	    		//计量单位
	    		if ($value['aspect'] == 3 && $value['name'] == '计量单位') {
	    			foreach ($value['featureIdValues'] as $k2 => $v2) {
	    				$b[] = $v2['value'];
	    			}
	    			$jiunit[] = array('inputtype'=>$value['inputType'],'value'=> $b, 'name'=>$value['name']);  
	    		}
	    	}
	    	$guige = empty($guige) ? '' : addslashes(json_encode($guige));
		    $jiunit = empty($jiunit) ? '' : addslashes(json_encode($jiunit));
            $sql = "UPDATE cn_alicate1 set guige = '{$guige}', uint = '{$jiunit}', url = '{$url}' where cateid = {$cateid};"."\r\n";
            echo file_put_contents('updatesql_3_1.sql', $sql, FILE_APPEND);
	    } else {
	    	$aaaa[] = $v;
	    }

	}
	print_r($aaaa);
	//print_r($a);

  
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