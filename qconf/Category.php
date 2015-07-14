<?php
/**
 * classdoc: 分类属性相关类库
 *
 * 函数列表如下：
 * 1. getCate1      （获取一级分类）
 * 2. getCate2      （获取二级分类）
 * 3. getCate3      （获取三级分类，也可能是前置属性）
 * 4. getCate4      （获取四级分类，四级为前置属性)
 * 5. getCateInfo   （根据cateid ,获取分类信息）
 * 6. getMultiCateInfo  （获取多个分类信息）
 * 7. getAttr           （获取属性信息，含商品属性+规格属性）
 * 8. getChildAttr      （获取子属性信息）
 * 9. formatProperty    （格式化产品属性）
 */
namespace Xz\Lib;

class Category
{
    public $idc;

    public function __construct($idc = 'dev')
    {
        $this->idc = $idc;
    }

    /**
     * 功能描述：获取所有一级分类
     *
     * @Author   yxg
     * @DateTime 2015-07-10T10:39:33+0800
     * @param    string fromat 值为 json|array 默认json
     * @return   array
     */
    public function getCate1($format = 'json')
    {
        $rsArr = Qconf::getBatchConf("/qconf/backcate/cascade", $this->idc);

        if ($format == 'array' && !empty($rsArr)) {
            foreach ($rsArr as $key => &$value) {
                $value = json_decode($value, true);
            }
        }
        return !empty($rsArr) ? $rsArr : array();
    }

    /**
     * 功能描述：获取某一级分类的二级分类
     *
     * @Author   yxg
     * @DateTime 2015-07-10T10:40:25+0800
     * @param    int 一级分类id
     * @param    string fromat 值为 json|array 默认json
     * @return   array
     */
    public function getCate2($cate1id, $format = 'json')
    {
        $cate1id = intval($cate1id);
        if ($cate1id <= 0) {return array();}
        $rsArr = Qconf::getBatchConf("/qconf/backcate/cascade/{$cate1id}", $this->idc);

        if ($format == 'array' && !empty($rsArr)) {
            foreach ($rsArr as $key => &$value) {
                $value = json_decode($value, true);
            }
        }
        return !empty($rsArr) ? $rsArr : array();
    }

    /**
     * 功能描述：获取某二级分类下的三级分类或前置属性
     *
     * @Author   yxg
     * @DateTime 2015-07-10T10:40:33+0800
     * @param    int  一级分类id
     * @param    int  二级分类id
     * @param    string fromat 值为 json|array 默认json
     * @return   array
     */
    public function getCate3($cate1id, $cate2id, $format = 'json')
    {
        $cate1id = intval($cate1id);
        $cate2id = intval($cate2id);
        if ($cate1id <= 0 || $cate2id <= 0) {return array();}

        $rsArr = Qconf::getBatchConf("/qconf/backcate/cascade/{$cate1id}/{$cate2id}", $this->idc);
        $reset = reset($rsArr);
        if (!$reset) {
            $keyArr = Qconf::getBatchKeys("/qconf/backcate/cascade/{$cate1id}/{$cate2id}", $this->idc);
            if (empty($keyArr[0])) {return array();}
            $rsArr = Qconf::getBatchConf("/qconf/backcate/cascade/{$cate1id}/{$cate2id}/{$keyArr[0]}", $this->idc);
        }

        if ($format == 'array' && !empty($rsArr)) {
            foreach ($rsArr as $key => &$value) {
                $value = json_decode($value, true);
            }
        }
        return !empty($rsArr) ? $rsArr : array();
    }

    /**
     * 功能描述：获取某三级分类下的前置属性
     *
     * @Author   yxg
     * @DateTime 2015-07-10T10:40:41+0800
     * @param    int 一级分类id
     * @param    int 二级分类id
     * @param    int 三级分类id
     * @param    string fromat 值为 json|array 默认json
     * @return   array
     */
    public function getCate4($cate1id, $cate2id, $cate3id, $format = 'json')
    {
        $cate1id = intval($cate1id);
        $cate2id = intval($cate2id);
        $cate3id = intval($cate3id);
        if ($cate1id <= 0 || $cate2id <= 0 || $cate3id <= 0) {return array();}

        $keyArr = Qconf::getBatchKeys("/qconf/backcate/cascade/{$cate1id}/{$cate2id}/{$cate3id}", $this->idc);
        if (empty($keyArr[0])) {return array();}
        $rsArr = Qconf::getBatchConf("/qconf/backcate/cascade/{$cate1id}/{$cate2id}/{$cate3id}/{$keyArr[0]}", $this->idc);

        if ($format == 'array' && !empty($rsArr)) {
            foreach ($rsArr as $key => &$value) {
                $value = json_decode($value, true);
            }
        }
        return !empty($rsArr) ? $rsArr : array();
    }

    /**
     * 功能描述：获取某分类下的信息
     *
     * @Author   yxg
     * @DateTime 2015-07-10T10:40:48+0800
     * @param    int 分类id
     * @param    string fromat 值为 json|array 默认json
     * @return   array
     */
    public function getCateInfo($cateid, $format = 'json')
    {
        $cateid = intval($cateid);
        if ($cateid <= 0) {
            return array();
        }
        $rsArr = Qconf::getConf("/qconf/backcate/cateinfo/{$cateid}", $this->idc);
        if ($format == 'array' && !empty($rsArr)) {
            $rsArr = json_decode($rsArr, true);
        }
        return !empty($rsArr) ? $rsArr : array();
    }

    /**
     * 功能描述：获取多个分类信息
     * despcription
     *
     * @Author   yxg
     * @DateTime 2015-07-11T09:21:45+0800
     * @param    array                    $cateidArr [description]
     * @param    string                   $format    [description]
     * @return   [type]                              [description]
     */
    public function getMultiCateInfo($cateidArr = array(), $format = 'json')
    {
        $rsArr = array();
        if (!is_array($cateidArr) || empty($cateidArr)) {
            return array();
        }
        $cateidArr = array_unique(array_filter(array_map('intval', $cateidArr)));
        if (empty($cateidArr)) {
            return array();
        }

        foreach ($cateidArr as $key => $value) {
            $rsArr[$value] = $this->getCateInfo($value, $format);
        }
        return $rsArr;
    }

    /**
     * @Author   lvxh
     * @Desc    根据分类获取属性
     */
    public function getAttr($cateid, $format = 'json')
    {
        $cateid = intval($cateid);
        if ($cateid <= 0) {
            return array();
        }
        $str = Qconf::getConf("/qconf/backcate/attr/{$cateid}", $this->idc);
        if ($format == 'array' && !empty($str)) {
            return json_decode($str, true);
        } else {
            return $str;
        }

    }

    /**
     * @Author   lvxh
     * @Desc    根据属性和属性值获取子属性
     */
    public function getChildAttr($parentfid, $parentvid, $format = 'json')
    {
        $parentfid = intval($parentfid);
        $parentvid = intval($parentvid);
        if ($parentfid <= 0 || $parentvid <= 0) {
            return array();
        }
        $str = Qconf::getConf("/qconf/backcate/childattr/{$parentfid}/{$parentvid}", $this->idc);
        if ($format == 'array' && !empty($str)) {
            return json_decode($str, true);
        } else {
            return $str;
        }

    }

    /**
     * 功能描述：格式化产品属性
     * despcription
     *
     * @Author   yxg
     * @DateTime 2015-07-13T20:46:43+0800
     * @param    array                    $array [description]
     * @return   [type]                          [description]
     */
    public function formatProperty(array $array)
    {
        if (!is_array($array) && empty($array)) {
            return array();
        }
        if ($array['cate'][3] != 0) {

            $array['docate'] = $array['cate'][3];
        } elseif ($array['cate'][2] != 0) {

            $array['docate'] = $array['cate'][2];
        } elseif ($array['cate'][1] != 0) {

            $array['docate'] = $array['cate'][1];
        } else {
            $array['docate'] = 0;
        }

        if ($array['docate'] == 0) {
            return array();
        }
        $property = json_decode($array['property'], true);
        if (!$property || empty($property)) {
            return json_encode(array());
        }
        $doproperty = $this->getAttr($array['docate'], 'array');
        foreach ($property as $dokey => $value) {
            if (!isset($doproperty[$dokey])) {
                return json_encode(array());
            }
            $myval = '';
            if ($doproperty[$dokey]['inputType'] == 0 || $doproperty[$dokey]['inputType'] == -1) { //文本或数字输入框
                $myval = $value . $doproperty[$dokey]['unit'];
            } else {
                $dovalue = json_decode($doproperty[$dokey]['attrvalues'], true);
                if (!is_array($dovalue)) {
                    $dovalue = array();
                }
                if ($doproperty[$dokey]['inputType'] == 2) { //复选框
                    $temp = '';
                    foreach ($dovalue as $mvalue) {
                        foreach ($value as $skey => $svalue) {
                            if ($mvalue['vid'] == $svalue) {
                                $temp .= $mvalue['value'] . $doproperty[$dokey]['unit'] . "  ";
                            }
                        }
                    }
                    $myval = $temp;
                } else {   //select框
                    foreach ($dovalue as $mvalue) {
                        if (is_array($value)) {
                            foreach ($value as $skey => $svalue) {
                                if ($mvalue['vid'] == $skey) {
                                    $myval = $mvalue['value'] == '其他' ? $svalue . $doproperty[$dokey]['unit'] : $mvalue['value'] . $doproperty[$dokey]['unit'];
                                }
                            }
                        } else {
                            if ($mvalue['vid'] == $value) {
                                $myval = $mvalue['value'] . $doproperty[$dokey]['unit'];
                            }
                        }
                    }
                }
            }

            //有子属性
            if ($value['has_childattr'] == 1) {
                $this->formatSubProperty($value['parentfid'], $value['parentvid'], $property, &$newProperty);
            }
            $dk               = html_entity_decode($doproperty[$dokey]['fname'], ENT_COMPAT, 'UTF-8');
            $newProperty[$dk] = $myval;
        }
        return $newProperty;
    }

    /**
     * 功能描述：格式化产品子属性
     * despcription
     *
     * @Author   yxg
     * @DateTime 2015-07-13T20:47:14+0800
     * @param    [type]                   $parentfid    [description]
     * @param    [type]                   $parentvid    [description]
     * @param    [type]                   $property     [description]
     * @param    [type]                   &$newProperty [description]
     * @return   [type]                                 [description]
     */
    public function formatSubProperty($parentfid, $parentvid, $property, &$newProperty)
    {
        $subProperty = $this->getChildAttr($parentfid, $parentvid, 'array');

        if (!is_array($subProperty)) {
            $subProperty = array();
        }
        //循环得到相应的键值 子属性的值也有enum
        foreach ($subProperty as $suk => $suv) {
            $subvalue = array();
            $subvalue = json_decode($suv['attrvalues'], true);
            foreach ($property as $pk => $pv) {
                if ($pk == $suv['fid']) {
                    $newKey = $suv['fname'];
                    if ($suv['inputType'] == 0) {
                        $newProperty[$newKey] = $property[$pk] . $suv['unit'];
                    } else {
                        $newProperty[$newKey] = $subvalue[$pk];
                    }
                }
            }
            if ($suv['has_childattr'] == 1) {
                $this->formatSubProperty($suv['parentfid'], $suv['parentvid'], $property, &$newProperty);
            }
        }

    }

}
