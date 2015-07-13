<?php
/**
 * classdoc: 分类属性相关类库
 *
 * 函数如下：
 * 1. getCate1
 * 2. getCate2
 * 3. getCate3    （三级分类，也可能是前置属性）
 * 4. getCate4     (四级为前置属性)
 * 5. getCateInfo （根据cateid ,获取分类信息）
 */
namespace Xz\Lib\Category;

class Category
{
    public $idc;
    private static $_instance;

    public static function getInstance($idc)
    {
        if (null === self::$_instance) {
            self::$_instance = new self($idc);
        }
        return self::$_instance;
    }

    public function __construct($idc='dev')
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
        $rsArr = Qconf::getBatchConf("/Qconf/Category/Total", $this->idc);

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
        $rsArr = Qconf::getBatchConf("/Qconf/Category/Total/{$cate1id}", $this->idc);

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

        $rsArr = Qconf::getBatchConf("/Qconf/Category/Total/{$cate1id}/{$cate2id}", $this->idc);
        if (empty($rsArr)) {
            $keyArr = Qconf::getBatchKeys("/Qconf/Category/Total/{$cate1id}/{$cate2id}", $this->idc);
            if (empty($keyArr[0])) {return array();}
            $rsArr = Qconf::getBatchConf("/Qconf/Category/Total/{$cate1id}/{$cate2id}/{$keyArr[0]}", $this->idc);
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

        $keyArr = Qconf::getBatchKeys("/Qconf/Category/Total/{$cate1id}/{$cate2id}/{$cate3id}", $this->idc);
        if (empty($keyArr[0])) {return array();}
        $rsArr = Qconf::getBatchConf("/Qconf/Category/Total/{$cate1id}/{$cate2id}/{$cate3id}/{$keyArr[0]}", $this->idc);

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
        $rsArr = Qconf::getBatchConf("/Qconf/Category/Cateinfo/{$cateid}", $this->idc);
        if ($format == 'array' && !empty($rsArr)) {
            foreach ($rsArr as $key => &$value) {
                $value = json_decode($value, true);
            }
        }
        return !empty($rsArr) ? $rsArr : array();
    }

    /**
     * @Author   lvxh
     * @Desc    根据分类获取前置为分类的属性
     */
    public function getPreAttr($cateid, $format = 'json')
    {
        $cateid = intval($cateid);
        if ($cateid <= 0) {
            return array();
        }
        $str = Qconf::getConf("/Qconf/Category/PreAttr/{$cateid}", $this->idc);echo $str;
        if ($format == 'array' && !empty($str))
          return json_decode($str, true);
        else
            return $str;
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
        $str = Qconf::getConf("/Qconf/Category/Attr/{$cateid}", $this->idc);
        if ($format == 'array' && !empty($str))
            return json_decode($str, true);
        else
            return $str;
    }
    /**
     * @Author   lvxh
     * @Desc    根据分类获取规格属性
     */
    public function getSpecAttr($cateid, $format = 'json')
    {
        $cateid = intval($cateid);
        if ($cateid <= 0) {
            return array();
        }
        $str = Qconf::getConf("/Qconf/Category/SpecAttr/{$cateid}", $this->idc);
        if ($format == 'array' && !empty($str))
            return json_decode($str, true);
        else
            return $str;
    }
    /**
     * @Author   lvxh
     * @Desc    根据属性和属性值获取子属性
     */
    public function getChildAttr($parentfid, $parentvid, $format = 'json')
    {
        $parentfid = intval($parentfid);
        $parentvid = intval($parentvid);
        if ($parentfid <= 0 || $parentvid<=0) {
            return array();
        }
        $str = Qconf::getConf("/Qconf/Category/ChildAttr/{$parentfid}/{$parentvid}", $this->idc);
        if ($format == 'array' && !empty($str))
            return json_decode($str, true);
        else
            return $str;
    }
    /**
     * @Author   lvxh
     * @Desc    根据属性值获取属性名称
     */
    public function getAttrVal($vid, $format = 'json')
    {
        $vid = intval($vid);
        if ($vid <= 0) {
            return array();
        }
        $str = Qconf::getConf("/Qconf/Category/AttrVal/{$vid}", $this->idc);
        if ($format == 'array' && !empty($str))
            return json_decode($str, true);
        else
            return $str;
    }
}
