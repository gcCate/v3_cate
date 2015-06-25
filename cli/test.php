<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-6-19
 * Time: 上午9:27
 */

$value['featureIdValues'] = '[{"value":"u767du8272","vid":28320},{"value":"u9ed1u8272","vid":28341}]';
echo preg_replace('/u([0-9a-f]{4})/', "\\u$1", $value['featureIdValues']);
//$arr = json_decode($value['featureIdValues'], true);
//foreach($arr as $key => $value){
//    $value['value'] = str_replace('u', '\u', $value['value']);
//    $arr[$key] = $value;
//}
//print_r($arr);

//echo $value['featureIdValues'];