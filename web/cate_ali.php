<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-7-2
 * Time: 上午10:38
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

    $cate1 = isset($_GET['cate1']) ? intval($_GET['cate1']) : 0;
    $cate2 = isset($_GET['cate2']) ? intval($_GET['cate2']) : 0;
    $cate3 = isset($_GET['cate3']) ? intval($_GET['cate3']) : 0;

    //获得1级分类
    $cate1Arr = getCate(0);
//    print_r($cate1Arr);

    //获取子分类
    function getCate($cateId=0)
    {
        global $db;
//        $resArr = $db->select('cg_cate_relation', '*', array('parentid' => $cateId));
//        $idArr  = array();
//        foreach($resArr as $value){
//            $idArr[] = $value['cateid'];
//        }
//        if(!empty($idArr)){
//            $resArr = $db->select('cg_cateinfo', '*', array('cateid' => $idArr));
//            return $resArr;
//        }else{
//            return array();
//        }
        $temp = $db->select('parentCatInfo', 'catsId', array('parentCatsId' => $cateId, 'ORDER'=>'order asc'));
        $idArr = array();
        foreach($temp as $v){
            $idArr[] = $v;
        }
        $resArr = $db->select('catInfo', '*', array('catsId' => $idArr,'ORDER' => array('catsId', $idArr)));
        return $resArr;
    }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <title>分类属性</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="../vendor/twitter/bootstrap/dist/css/bootstrap.min.css">
    <script src="../vendor/components/jquery/jquery.min.js"></script>
    <script src="../vendor/twitter/bootstrap/dist/js/bootstrap.min.js"></script>
    <style type="text/css">
        body{padding: 50px 0 0 0px;}
        select{min-height: 300px;}
    </style>
</head>
<body>
<div class="container">
    <div class="row">

    </div>
    <div class="row">
        <div class="col-md-3" >
            <?php
            echo "<select multiple class=\"form-control\" id='cate1'>";
            if(!empty($cate1Arr)){
                foreach($cate1Arr as $value){
                    echo "<option value=\"{$value['catsId']}\">{$value['catsName']}</option>";
                }
            }
            echo "</select>";
            ?>
        </div>
        <div class="col-md-3">
            <select multiple class="form-control" id="cate2">

            </select>
        </div>
        <div class="col-md-3">
            <select multiple class="form-control" id="cate3">

            </select>
        </div>
        <div class="col-md-3">
            <select multiple class="form-control" id="cate4">

            </select>
        </div>
    </div>
    <div class="row" id="attr">
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        $("#cate1").on('click','option',function(){
            var cate1 = $("#cate1").val();
            $.ajax({url:"/api/cate_ali.php?action=cate&cateId="+cate1, success:function(responseText){
                var res = jQuery.parseJSON(responseText);
                $("#cate2").html(res.html);
                $("#cate3").empty();
                $("#cate4").empty();
                $("#attr").html(res.attr);
            }});
        });
        $("#cate2").on('click', 'option', function(){
            var cate2 = $("#cate2").val();
            var htmlobj=$.ajax({url:"/api/cate_ali.php?action=cate&cateId="+cate2,async:false});
            var res = JSON.parse(htmlobj.responseText);
            $("#cate3").html(res.html);
            $("#cate4").empty();
            $("#attr").html(res.attr);
        });
        $("#cate3").on('click', 'option', function(){
            var cate3 = $("#cate3").val();
            var htmlobj=$.ajax({url:"/api/cate_ali.php?action=cate&cateId="+cate3,async:false});
            var res = JSON.parse(htmlobj.responseText);
            $("#cate4").html(res.html);
            $("#attr").html(res.attr);
        });
    });
</script>
</body>
</html>