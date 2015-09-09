<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-7-2
 * Time: 上午10:51
 */
    error_reporting(E_ALL);
    ini_set('display_errors', 'ON');
    require "../lib/medoo.php";
    $db = new medoo(array(
        'database_type' => 'mysql',
        'database_name' => 'v3_cate_ali',
//        'server'        => '192.168.8.18',
//        'username'      => 'root',
//        'password'      => 'gc7232275',
        'server'        => 'localhost',
        'username'      => 'root',
        'password'      => '123456',
        'port'          => 3306,
        'charset'       => 'utf8',
        'option'        => array(PDO::ATTR_CASE => PDO::CASE_NATURAL)
    ));

    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $cateId = isset($_GET['cateId']) ? intval($_GET['cateId']) : 0;
    $return = array();
    if($action == 'cate'){
        $resArr = getCate($cateId);
        if(empty($resArr)){
            //属性前置为分类
            $temp = $db->select('attprepose', '*', array('parentId' => $cateId));
            !empty($temp) && $resArr = $temp;
        }
        $return['html'] = option($resArr);
        $curCate = getCurCate($cateId);
        if($curCate['isLeaf']){
            //叶子分类返回属性
            $attr = getAttr($cateId);
            $return['attr'] = attr($attr);
        }

    }elseif($action == 'attr'){
        $attr = getAttr($cateId);
        $return['attr'] = attr($attr);
    }else{

    }
    echo json_encode($return);

//获取子分类
function getCate($cateId=0)
{
    global $db;
    $temp = $db->select('parentCatInfo', 'catsId', array('parentCatsId' => $cateId, 'ORDER'=>'order asc'));
    $idArr = array();
    foreach($temp as $v){
        $idArr[] = $v;
    }
    $resArr = $db->select('catInfo', '*', array('catsId' => $idArr,'ORDER' => array('catsId', $idArr)));
    return $resArr;
}
//获取当前分类信息
function getCurCate($cateId)
{
    global $db;
    $resArr = array();
    if($cateId>0){
        $resArr = $db->get('catInfo', '*', array('catsId' => $cateId, 'LIMIT' => 1));
    }
    return $resArr;
}
//获取分类下属性
function getAttr($cateid)
{
    global $db;
    if($cateid<=0)
        return array();
//    $resArr = $db->select('postatrribute', '*', array('categoryId' => $cateid, 'ORDER' => 'order asc'));
//    $resArr = $db->select('productAttributesInfo', '*', array('categoryId' => $cateid, 'ORDER' => 'order asc'));
    //按属性、规格、交易属性区分、排序时必填属性考前
    $resArr['attr'] = $db->select('postatrribute', '*',
        array('AND'=>array('categoryId' => $cateid, ), 'ORDER' => 'keyAttr desc,order asc'));
    return $resArr['attr'];
}
//分类html
function option($arr)
{
    $html = '';
    if(empty($arr)) return $html;
    foreach($arr as $value)
    {
        $html .= "<option value=\"{$value['catsId']}\">{$value['catsName']}</option>";
    }
    return $html;
}
//属性html
function attr($arr)
{
    $html = '<form class="form-horizontal">';
    foreach($arr as $value)
    {
        $html .= "<div class=\"form-group\"><label class=\"col-sm-2 control-label\">{$value['name']}</label><div class=\"col-sm-10\">";
        if(in_array($value['showType'], array(-1,0))){
            //文本输入框
            $html .= "<input>";
        }elseif($value['showType']==1) {
            $html .="<select class=\"form-control\">";
            $attrvalue = json_decode($value['featureIdValues'], true);
            foreach($attrvalue as $v){
                $html .= "<option value=\"{$v['vid']}\">{$v['value']}</option>";
            }
            $html .= "</select>";
        }elseif($value['showType']==2){
            //多选
            $html .= "<div class=\"checkbox\">";
            $attrvalue = json_decode($value['featureIdValues'], true);
            foreach($attrvalue as $v){
                $html .= "<label><input type=\"checkbox\" value=\"{$v['vid']}\">{$v['value']}</label>";
            }
            $html .= "</div>";
        }elseif($value['showType']==3){
            //单选
            $html .= "<div class=\"radio\">";
            $attrvalue = json_decode($value['featureIdValues'], true);
            foreach($attrvalue as $v){
                $html .= "<label><input type=\"radio\" name=\"{$value['name']}\" value=\"{$v['vid']}\">{$v['value']}</label>";
            }
            $html .= "</div>";
        }
        $html .="</div></div>";
    }
    $html .="</form>";
    return $html;
}