<?php
// echo '<pre>';
// var_dump(handle(1033199));

$conn   = mysql_connect("192.168.8.18", "root", "gc7232275");
$dbname = 'v3_cate_ali';

$minid = 0;
while (true) {
    $sql       = '';
    $cateidArr = array();
    $sql       = "SELECT `catsId` FROM  `{$dbname}`.`offercatsInfo` where catsId > {$minid} and leaf = 1  order by catsId asc limit 10";
    if (!$conn) {
        $conn = mysql_connect("192.168.8.18", "root", "gc7232275");
    }
    $array = findAll($sql, $conn);

    foreach ($array as $key => $value) {

        handle($value['catsId']);
    }
    if (isset($array[9]['catsId']) && $array[9]['catsId'] < 124680001) {
        $minid = $array[9]['catsId'];
    } else {
        exit('处理完毕');
    }
}

function handle($cateid)
{
    //采集发布类目信息
    header("Content-type:text/html;charset=utf-8");
    $appSecret = 'FksHCjGHilv';
    $sign_str  = 'param2/1/cn.alibaba.open/offerPostFeatures.get/1016611categoryID' . $cateid;
    $code_sign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $appSecret, true)));
    //阿里开放的接口
    $url = 'http://gw.open.1688.com/openapi/param2/1/cn.alibaba.open/offerPostFeatures.get/1016611?categoryID=' . $cateid . '&_aop_signature=';

    $json   = curl($url . $code_sign);
    $result = json_decode($json, true);
    // return $result;

    $sql1 = '';
    foreach ($result['result']['toReturn'] as $key => $value) {
        $value['unit']            = empty($value['unit']) ? '' : $value['unit'];
        $value['fieldFlag']       = empty($value['fieldFlag']) ? '' : $value['fieldFlag'];
        $value['featureIdValues'] = (isset($value['featureIdValues']) && !empty($value['featureIdValues'])) ? json_encode($value['featureIdValues']) : "";
        $value['childrenFids']    = (isset($value['childrenFids']) && !empty($value['childrenFids'])) ? json_encode($value['childrenFids']) : "";

        $value['featureIdValues'] = addslashes($value['featureIdValues']);

        $value['isKeyAttr']              = empty($value['isKeyAttr']) ? 0 : 1;
        $value['isSpecAttr']             = empty($value['isSpecAttr']) ? 0 : 1;
        $value['isSuggestion']           = empty($value['isSuggestion']) ? 0 : 1;
        $value['isSupportDefinedValues'] = empty($value['isSupportDefinedValues']) ? 0 : 1;
        $value['isSpecExtendedAttr']     = empty($value['isSpecExtendedAttr']) ? 0 : 1;
        $value['required']               = empty($value['required']) ? 0 : 1;

        $sql1 .= "INSERT INTO `postatrribute`(`fid`, `unit`, `featureIdValues`, `childrenFids`, `name`, `showType`, `isNeeded`,";
        $sql1 .= "`order`, `fieldFlag`, `aspect`, `defaultValueId`, `categoryId`, `keyAttr`, `specAttr`, `suggestType`,";
        $sql1 .= "`supportDefinedValues`, `specExtendedAttr`, `inputType`,`featureType`,`required`,`standardUnit`,`fieldType`,`standardType`,`attrType`) VALUES ({$value['fid']},'{$value['unit']}','{$value['featureIdValues']}','{$value['childrenFids']}',";
        $sql1 .= "'{$value['name']}','{$value['showType']}','{$value['isNeeded']}','{$value['order']}','{$value['fieldFlag']}','{$value['aspect']}',";
        $sql1 .= "'{$value['defaultValueId']}','{$cateid}',{$value['isKeyAttr']},{$value['isSpecAttr']},{$value['isSuggestion']},";
        $sql1 .= "{$value['isSupportDefinedValues']},{$value['isSpecExtendedAttr']},'{$value['inputType']}','{$value['featureType']}','{$value['required']}','{$value['standardUnit']}','{$value['fieldType']}','{$value['standardType']}','{$value['attrType']}');";

        $sql1 .= "\r\n";

    }
    file_put_contents('sql_postatrribute_0619_tmp.sql', $sql1, FILE_APPEND);
    file_put_contents('leaf_cateid_postatrributeSECELECT_0619_tmp.log', $cateid . "\r\n", FILE_APPEND);
}

function curl($url, $method = '', $post = '', $returnHeaderInfo = false, $timeout = 2)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); //设置超时时间,单位秒
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.8) Gecko/20100722 Firefox/3.6.8");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    if ($method == 'post') {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    }
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $str      = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    unset($curl);
    if (!$str) {
        return false;
    }
    //返回头信息
    if ($returnHeaderInfo) {
        return array($httpCode, $str);
    }
    return $str;
}

function findAll($sql, $conn)
{
    $rs        = mysql_query($sql, $conn);
    $returnArr = array();

    while ($row = mysql_fetch_assoc($rs)) {
        $returnArr[] = $row;
    }
    return $returnArr;
}
