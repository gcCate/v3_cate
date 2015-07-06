<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-7-1
 * Time: 下午3:43
 * Desc:  获取子分类
 */
    error_reporting(E_ALL);
    ini_set('display_errors', 'ON');
    require "../lib/medoo.php";
    $db = new medoo(array(
        'database_type' => 'mysql',
        'database_name' => 'v3_category',
        'server'        => '192.168.8.18',
        'username'      => 'root',
        'password'      => 'gc7232275',
        'port'          => 3306,
        'charset'       => 'utf8',
        'option'        => array(PDO::ATTR_CASE => PDO::CASE_NATURAL)
    ));

    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $cateId = isset($_GET['cateId']) ? intval($_GET['cateId']) : 0;
    $return = array();
    if($action == 'cate'){
        $resArr         = getCate($cateId);
        $curCate        = getCurCate($cateId);
        if($curCate['is_leaf']){
            //叶子分类返回属性
            $attr = getAttr($cateId);
            $return['attr'] = attr($attr);
        }
        //属性前置为分类
        if(empty($resArr) && ($curCate['has_virtual']==1)){
            $resArr = getVirtualCate($cateId);
        }
        $return['html'] = option($resArr);
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
    $temp = $db->select('cg_cate_relation', 'cateid', array('parentid' => $cateId,'ORDER'=>'sort asc'));
    $idArr = array();
    foreach($temp as $v){
        $idArr[] = $v;
    }
    $resArr = $db->select('cg_cateinfo', '*', array('cateid' => $idArr, 'ORDER' => array('cateid', $idArr)));
    return $resArr;
}
//获取当前分类信息
function getCurCate($cateId)
{
    global $db;
    $resArr = array();
    if($cateId>0){
        $resArr = $db->get('cg_cateinfo', '*', array('cateid' => $cateId, 'LIMIT' => 1));
    }
    return $resArr;
}
//获取分类下属性
function getAttr($cateid)
{
    global $db;
    if($cateid<=0)
        return array();
    $resArr = $db->select('cg_attrinfo', '*', array('cateid' => $cateid));
    return $resArr;
}
//获取虚拟分类
function getVirtualCate($cateid)
{
    global $db;
    $resArr = $db->get("cg_attrinfo", '*', array('AND' => array('cateid' => $cateid, 'preposecateid[>]' => 0)));
    if(empty($resArr)){
        return array();
    }else{
        $attArr = json_decode($resArr['attrvalues'], true);
        $return = array();
        foreach($attArr as $value){
            $return[] = array(
                'cateid'    => $value['vid'],
                'catename'  => $value['value']
            );
        }
        return $return;
    }
}
//分类html
function option($arr)
{
    $html = '';
    if(empty($arr)) return $html;
    foreach($arr as $value)
    {
        $html .= "<option value=\"{$value['cateid']}\">{$value['catename']}</option>";
    }
    return $html;
}
//属性html
function attr($arr)
{
    $html = '<form class="form-horizontal">';
    foreach($arr as $value)
    {
        $html .= "<div class=\"form-group\"><label class=\"col-sm-2 control-label\">{$value['fname']}</label><div class=\"col-sm-10\">";
        if(in_array($value['showtype'], array(-1,0))){
            //文本输入框
            $html .= "<input>";
        }elseif($value['showtype']==1) {
            $html .="<select class=\"form-control\">";
            $attrvalue = json_decode($value['attrvalues'], true);
            foreach($attrvalue as $v){
                $html .= "<option value=\"{$v['vid']}\">{$v['value']}</option>";
            }
            $html .= "</select>";
        }elseif($value['showtype']==2){
            //多选
            $html .= "<div class=\"checkbox\">";
            $attrvalue = json_decode($value['attrvalues'], true);
            foreach($attrvalue as $v){
                $html .= "<label><input type=\"checkbox\" value=\"{$v['vid']}\">{$v['value']}</label>";
            }
            $html .= "</div>";
        }elseif($value['showtype']==3){
            //单选
            $html .= "<div class=\"radio\">";
            $attrvalue = json_decode($value['attrvalues'], true);
            foreach($attrvalue as $v){
                $html .= "<label><input type=\"radio\" name=\"{$value['fname']}\" value=\"{$v['vid']}\">{$v['value']}</label>";
            }
            $html .= "</div>";
        }
        $html .= "</div></div>";
    }
    $html .="</form>";
    return $html;
}