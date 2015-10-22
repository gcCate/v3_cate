<?php

header('content-type:text/html;charset=utf-8');
date_default_timezone_set('Asia/Shanghai');
set_time_limit(0);

$hostmaster = '172.17.11.2'; //主库主机地址
$dbuser     = 'gongchang'; //用户名
$pwd        = 'gongchang123'; //密码

$port   = '3306';
$dbname = 'gccateinfo';

$conn = mysql_connect($hostmaster, $dbuser, $pwd);

mysql_query('set names utf8', $conn);

$sql_cate = "select `cateid`, `catename` from {$dbname}. `cate_cateinfo` ";
$cateArr  = findAll($sql_cate, $conn);
foreach ($cateArr as $key => $value) {
    $cate[$value['cateid']] = $value['catename'];
}
// echo "<pre>";
// var_dump($cate);exit;
$minid = 0;
$maxid = 8360;
$id    = 'cateid';
require "./Zookeeper_Api.php";

$zk = new Zookeeper_Api('172.17.11.5:2181');

while (true) {
    $sql = "select `cateid`, `catename`, `is_leaf` from {$dbname}. `cate_cateinfo` where $id > {$minid} order by $id asc limit 100";
    if (!$conn) {
        $conn = mysql_connect($hostmaster, $dbuser, $pwd);
    }
    $array = findAll($sql, $conn);
// echo "<pre>";
    // print_r($array);exit;
    foreach ($array as $key => $value) {
        $frontInfo = handle($value, $dbname, $conn, $cate);
        echo $frontInfo . "\r\n";
        $zk->set("/qconf/backcate/cateinfo/{$value['cateid']}", $frontInfo);
    }

    if (isset($array[99][$id]) && $array[99][$id] < $maxid) {
        $minid = $array[99][$id];
    } else {
        exit('处理完毕');
    }
}

function handle($cateArr, $dbname, $conn, $cate)
{
    $navArr  = $childcateArr  = $attrArr  = $infoArr  = $childArr  = array();
    $sql2    = "select * from {$dbname}.`cate_cateinfo` where cateid = {$cateArr['cateid']}";
    $infoArr = findOne($sql2, $conn);
    //计算面包屑
    $navArr = searchcate($cateArr['cateid'], $cateArr['catename'], $dbname, $conn, $cate);

    //是叶子，计算属性
    if ($cateArr['is_leaf'] == 1) {
        $sql1 = "select * from {$dbname}.`cate_attrinfo` where cateid = {$cateArr['cateid']} and showtype >= 1 and showtype <= 4";
        $rs1  = findAll($sql1, $conn);
        foreach ($rs1 as $key => &$value) {
            $tmpArr = json_decode($value['attrvalues'], true);
            foreach ($tmpArr as $k => &$v) {
                unset($v['vid']);
            }
            $value['attrvalues'] = $tmpArr;
        }
        $attrArr = $rs1;

    } else {
        //不是叶子，计算子分类
        $sql0 = "select * from {$dbname}.`cate_cateinfo` where parentid= {$cateArr['cateid']}";
        $rs   = findAll($sql0, $conn);
        // echo '<pre>';
        // var_dump($rs);exit;
        if (empty($rs)) {
            // echo $sql0 . "\r\n";
        } else {
            foreach ($rs as $key => $value) {
                $childcateArr[$value['cateid']] = $cate[$value['cateid']];
                //计算该子分类是否还有子分类
                $sql6                       = "select * from {$dbname}.`cate_cateinfo` where parentid= {$value['cateid']}";
                $rs                         = findAll($sql6, $conn);
                $haschild                   = !empty($rs) ? 1 : 0;
                $childArr[$value['cateid']] = array('catename' => $cate[$value['cateid']], 'haschild' => $haschild);
            }
        }

    }

    $finalArr = array(
        'info'      => $infoArr,
        'nav'       => $navArr,
        'childcate' => $childcateArr,
        'attr'      => $attrArr,
        'child'     => $childArr);
    return json_encode($finalArr, JSON_UNESCAPED_UNICODE);
}

function searchcate($id, $name, $dbname, $conn, $cate)
{
    $ttArr = $navArr = $va = $value = $v = array();
    $sql_0 = "select `cateid`, `parentid` from {$dbname}. `cate_cateinfo` where cateid = '{$id}'";

    $ttArr = findAll($sql_0, $conn);
    if (count($ttArr) > 1) {
        echo $sql_0 . "\r\n";
    } else {
        $va = $ttArr[0];
        if ($va['parentid'] == 0) {
            $navArr = array(array('cateid' => $id, 'catename' => $name));
        } else {
            $sql_tmp = "select `cateid`, `parentid`, `is_leaf` from {$dbname}. `cate_cateinfo` where cateid = '{$va['parentid']}'";
            $tmpArr  = findAll($sql_tmp, $conn);
            if (count($tmpArr) > 1) {
                echo $sql_tmp . "\r\n";
            } else {
                $value = $tmpArr[0];
                if ($value['parentid'] == 0) {
                    $navArr = array(
                        array('cateid' => $value['cateid'], 'catename' => $cate[$value['cateid']]),
                        array('cateid' => $id, 'catename' => $name),
                    );
                    // file_put_contents("leafcate_relation_multi_ali_2.log", $cate[$value['cateid']] . ',' . $name . "\r\n", FILE_APPEND);
                } else {
                    $sql_tmp2 = "select `cateid`, `parentid` from {$dbname}. `cate_cateinfo` where cateid = '{$value['parentid']}'";
                    $tmp2Arr  = findAll($sql_tmp2, $conn);
                    if (count($tmp2Arr) > 1) {
                        echo $sql_tmp2 . "\r\n";
                    } else {
                        $v = $tmp2Arr[0];
                        if ($v['parentid'] == 0) {
                            $navArr = array(
                                array('cateid' => $v['cateid'], 'catename' => $cate[$v['cateid']]),
                                array('cateid' => $value['cateid'], 'catename' => $cate[$value['cateid']]),
                                array('cateid' => $id, 'catename' => $name),
                            );
                            // file_put_contents("leafcate_relation_multi_ali_2.log", $cate[$v['cateid']] . ',' . $cate[$value['cateid']] . ',' . $name . "\r\n", FILE_APPEND);
                        } else {
                            file_put_contents('error_cate_ali_0907.log', $cate[$v['cateid']] . ',' . $cate[$value['cateid']] . ',' . $name . "\t\t" . '"' . $v['cateid'] . ',' . $value['cateid'] . ',' . $id . "\r\n", FILE_APPEND);
                        }
                    }

                }
            }

        }
    }

    return $navArr;

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

function findOne($sql, $conn)
{
    $rs = mysql_query($sql, $conn);
    return mysql_fetch_assoc($rs);

}
