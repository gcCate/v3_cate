<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-6-9
 * Time: 下午3:43
 * Desc: 腾讯搜索测试
 */

    define('ROOT', dirname(__FILE__));
    error_reporting(E_ALL ^ E_NOTICE);
    require_once ROOT.'/../lib/src/QcloudApi/QcloudApi.php';
    $config = array(
        'SecretId'       => 'AKIDIqC1J4xeNtF09aahQM1nqor0DDMRRlhw',
        'SecretKey'      => '7rIjwLo1wfmJAVWw19IBE7bjeguwZDUL',
        'RequestMethod'  => 'GET',
        'DefaultRegion'  => 'gz');
    $cvm = QcloudApi::load(QcloudApi::MODULE_CVM, $config);

    $arr = array(
        'appId'         => 26530002,
        'serch_query'   => 'henan',
        'page_id'       => 0,
        'num_per_page'  => 10,
//        'search_id'     => time(),
//        'query_encode'  => 0,       //0表示utf8，1表示gbk，建议指定
//        'num_filter'    => '',      //数值过滤
//        'cl_filter'     => '',      //分类过滤
//        'rank_type'     => '',      //排序类型
//        'extra'         => '',      //检索用户相关字段
//        'source_id'     => '',      //检索来源
//        'second_search' => 0,       //是否二次检索，0关闭，1打开，默认0
//        'max_doc_return'=> 300,     //指定返回最大篇数，无特殊原因不建议指定，默认300篇
//        'is_smartbox'   => 0,       //是否smartbox检索，0关闭，1打开,默认是0
//        'enable_abs_highlight' => 0,//是否打开高红标亮，0关闭，1打开,默认是0
//        'qc_bid'        => 0,       //指定访问QC业务ID
    );
//    $a = $cvm->generateUrl('DataSearch', $arr);echo $a,"\r\n";
    $a = $cvm->DataSearch($arr);

    if ($a === false) {
        print_r($cvm->getError());
        die();
    } else {
        echo "add lines ",count($data),"\r\n";
    }