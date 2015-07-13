<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-7-10
 * Time: 上午10:34
 */
class Attr
{
    public $zkObj;
    public function __construct($zkObj)
    {
        $this->zkObj = $zkObj;
    }

    //根据分类id获取前置分类属性
    public function getPreAttr($cateid)
    {
        return $this->zkObj->get("/Qconf/Category/PreAttr/{$cateid}");
    }

    //根据分类id获取普通属性
    public function getAttr($cateid)
    {
        return $this->zkObj->get("/attr_{$cateid}");
    }

    //根据分类id获取规格属性
    public function getSpecAttr($cateid)
    {
        return $this->zkObj->get("/spec_attr_{$cateid}");
    }

    //获取子属性
    public function getChildAttr($fid, $vid)
    {
        return $this->zkObj->get("/child_attr_{$fid}_{$vid}");
    }
}

//测试
require '../lib/Zookeeper_Api.php';
$zk = new Zookeeper_Api('localhost:2181');
$attrObj = new Attr($zk);
$preAttr = $attrObj->getPreAttr(91);
var_dump($preAttr);