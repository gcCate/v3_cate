<?php
/**
 * 预热下v3产品信息
 */
include_once "ssdb.php";
$startid = 0;
$prepare = new PreparePro();
$prepare->run($startid);
class PreparePro
{
    public $limit = 0; //最多获取1000条产品信息写入ssdb
    public function __construct()
    {
        $this->localObj = new Table("local");
    }

    public function run($startid)
    {
        while (1) {
            $productRs = $this->localObj->findAll("select * from productinfo.pd_info where pid > '{$startid}' and status = 1 order by  pid asc limit 100");
            if (empty($productRs)) {
                break;
            }
            foreach ($productRs as $product) {
                $startid = $product['pid'];
                if ($this->limit > 1000) {
                    break 2;
                }
                $this->runPrepare($product);
            }
        }
    }

    /**
     * 预热产品sssdb
     * @return [type] [description]
     */
    public function runPrepare($product)
    {
        $cid = $product['cid'];
        $pid = $product['pid'];
        if ($cid < 1) {
            return false;
        }
        $companyRs = $this->localObj->findOne("select * from gongchanginfo.gc_company where cid = '{$cid}' limit 1");
        if (empty($companyRs) || $companyRs['status'] != 1) {
            return false;
        }
        //处理下产品信息
        $product = $this->convertProduct($product);
        //将信息存入ssdb
        $ssdbObj = new SimpleSSDB("192.168.8.18", 8888);
        $rs      = $ssdbObj->set("gc:pd:" . $pid, json_encode($product));
        if (!$rs) {
            return false;
        }
        file_put_contents(__DIR__ . "/com.log", $cid . "\n", FILE_APPEND);
        file_put_contents(__DIR__ . "/proPrepare.log", "gc:pd:" . $pid . "\n", FILE_APPEND);
        $ssdbObj->close();
        $this->limit++;
    }

    /**
     * 产品信息基本处理
     * @param  [type] $product [description]
     * @return [type]          [description]
     */
    public function convertProduct($product)
    {
        $cateArr                    = array($product['cate1'], $product['cate2'], $product['cate3']);
        $product['picurl']          = $product['picurl'] ? json_decode($product['picurl'], true) : array();
        $product['property_format'] = Xz_PdCate::getProperty($cateArr, $product['property']);
        return $product;
    }
}
class Config
{
    public static $dbArr = array(
        'online'   => array(
            '192.168.2.163', "gongchangdb", "gongchangdb7232275", "caijiproductinfo",
        ),
        'info'     => array(
            '192.168.2.101', 'hangye', 'hangye7232275', 'hangye',
        ),
        'local'    => array(
            '192.168.8.18', 'root', 'gc7232275', 'gcoperate',
        ),
        "local170" => array(
            '192.168.8.170', "root", "gc7232275", "test",
        ),
        'main'     => array(
            'read.mysql.ch.gongchang.com', 'gcwork', 'gcwork7232275', 'catesearch',
        ),
        'maind'    => array(
            'write.mysql.ch.gongchang.com', 'gcwork', 'gcwork7232275', 'catesearch',
        ),
        "product"  => array(
            "pdinfo.read.mysql.ch.gongchang.com", "gccontent", "gccontent7232275", "caijiproductinfo",
        ),
        'club'     => array('55651c3e54ae6.sh.cdb.myqcloud.com', 'cdb_outerroot', 'ScIwH*3fEB(', 'cn_clubnew', 8287),

    );
    public static $cateApiUrl = 'http://cate.ch.gongchang.com/cate_json/'; //本地接口无法使用，暂时调取线上的
}
class Table
{
    public $conn = '';
    public $config;
    public function __construct($connName)
    {
        $db           = Config::$dbArr;
        $this->config = $db[$connName];
        $this->getConnect();
    }

    public function getConnect()
    {
        $config     = $this->config;
        $this->conn = new mysqli($config[0], $config[1], $config[2], $config[3], isset($config[4]) ? $config[4] : 3306);
        $this->conn->query("set names utf8");
        if (!$this->conn) {
            throw new Exception("connect error@" . $config[0]);
        }
    }

    public function findOne($sql)
    {
        if (empty($sql)) {
            return $sql;
        }
        $query = $this->query($sql);
        return $query->fetch_assoc();
    }

    public function findAll($sql, $primary = "")
    {
        if (empty($sql)) {
            return array();
        }
        $result = array();
        $query  = $this->query($sql);
        while ($item = $query->fetch_assoc()) {
            if ($primary && isset($item[$primary])) {
                $result[$item[$primary]] = $item;
            } else {
                $result[] = $item;
            }
        }
        return $result;
    }

    public function update($sql)
    {
        $rs = $this->query($sql);
        return $this->conn->affected_rows;
    }

    public function insert($data, $table)
    {
        $sql = $this->getInsertSql($data, $table);
        $this->query($sql);
        return $this->conn->insert_id;
    }

    public function getInsertSql($data, $table)
    {
        $sql = "insert into " . $table . " (`" .
        implode("`,`", array_keys($data)) . "`) values" .
        " ('" . implode("','", array_values($data)) . "')";
        return $sql;
    }

    public function query($sql)
    {
        try {
            $query = $this->conn->query($sql);
        } catch (Exception $e) {
            echo $this->conn->error();
            echo "\n";
            echo $e->getMessage();exit();
        }
        if (!$query && in_array($this->conn->errno, array(2006, 2013))) {
            $this->conn - close();
            $this->getConnect();
            return $this->query($sql);
        } elseif (!$query) {
            echo $this->conn->errno . "\t" . $this->conn->error . "\n";
            echo $sql;
            echo "\n";
            exit;
        }
        return $query;
    }

    public function logSql($sql)
    {
        $path = "/tmp/init_" . date("Ymd") . ".log";
        file_put_contents($path, $sql . "\t" . date("H:i:s") . "\n", FILE_APPEND);
    }
}

class Xz_PdCate
{
    /**
     * 获取后台分类、属性 导航信息数据
     * backcate:1:info
     * backcate:1:nav
     * backcate:1:property
     * backcate:'.$id.':property:'.$parentid.':'.$parentVid
     * @param  array  $cateid 分类数组
     * @param  array  $select 获取数据类型
     * @return [Array]
     *Array(
     *      [1] => Array
     *      (
     *          [info] => {"cateid":"1","firstid":"0"}
     *          [nav] => [{"cateid":"1","catename":"\u706f\u5177\u7167\u660e","level":"1","parentid":"0"}]
     *      )
     *     )
     */
    public static function getBackCate(array $cateid, array $select = array('info'))
    {
        $newcateId = array();
        if (!empty($cateid)) {
            foreach ($cateid as $ctid) {
                foreach ($select as $value) {
                    if ($ctid == 0) {
                        $ctid = 'cate';
                    }
                    $newcateId[] = 'backcate:' . $ctid . ':' . $value;
                }
            }
        }
        //YAC 缓存获取数据
        $yac        = new Yac("pdcate_");
        $yacCateArr = $yac->get($newcateId);
        //缓存中不存在的数据 走URL后端
        $noCacheKeys = self::getNoCacheKeys($newcateId, $yacCateArr);
        $cacheArr    = $cateData    = array();
        if (!empty($noCacheKeys)) {
            $cateApiUrl = isset(Config::$cateApiUrl) ? Config::$cateApiUrl : exit('请设置分类URL配置');
            $cateApiUrl .= '?name=getcatename&cmds=mget%20';
            $cateStr = '';
            $cateStr = implode("%20", $noCacheKeys);
            $cateApiUrl .= $cateStr;
            $cateApiUrl .= "%0D%0A";
            $cateText = Xz_Http::curl_get_contents($cateApiUrl);
            $cateArr  = explode('@', str_replace(
                array('null', 'nil', '}[', ']{', '][', '}{', "\n"),
                array('{}', '{}', '}@[', ']@{', ']@[', '}@{', ""), $cateText));
            //数组格式化
            if (count($noCacheKeys) === count($cateArr)) {
                $cacheArr = array_combine($noCacheKeys, $cateArr);
            } else {
                foreach ($noCacheKeys as $key => $value) {
                    foreach ($cateArr as $k => $v) {
                        if ($k == $key) {
                            $cacheArr[$value] = $cateArr[$key];
                        } else {
                            $cacheArr[$value] = '[]';
                        }
                    }
                }
            }
            $yac->set($cacheArr);
        }
        if (is_bool($yacCateArr)) {
            $yacCateArr = array();
        }
        //合并二者数据
        $cateData = array_merge($yacCateArr, $cacheArr);
        $newArr   = array();
        foreach ($cateData as $key => $value) {
            $keys                       = explode(':', $key);
            $newArr[$keys[1]][$keys[2]] = $value;
        }
        return $newArr;
    }

    /**
     * 获取前台分类信息数据
     * frontcate:cate:info 所有一级分类 传值 0 select 只能info
     * frontcate:1:info
     * frontcate:1:nav
     * frontcate:1:subcate
     * frontcate:1:att
     * frontcate:1:seo
     * @param  array  $cateid 分类数组
     * @param  array  $select 获取数据类型
     * @return [Array]
     *Array(
     *      [1] => Array
     *      (
     *          [info] => {"cateid":"1","firstid":"0"}
     *          [nav] => [{"cateid":"1","catename":"\u706f\u5177\u7167\u660e","level":"1","parentid":"0"}]
     *      )
     *     )
     */
    public static function getFrontCate(array $cateid, array $select = array('info'))
    {
        $newcateId = array();
        if (!empty($cateid)) {
            foreach ($cateid as $ctid) {
                foreach ($select as $value) {
                    if ($ctid == 0) {
                        $ctid = 'cate';
                    }
                    $newcateId[] = 'frontcate:' . $ctid . ':' . $value;
                }
            }
        }
        //YAC 缓存获取数据
        $yac        = new Yac("pdcate_");
        $yacCateArr = $yac->get($newcateId);
        //缓存中不存在的数据 走URL后端
        $noCacheKeys = self::getNoCacheKeys($newcateId, $yacCateArr);
        $cacheArr    = $cateData    = array();
        if (!empty($noCacheKeys)) {
            $cateApiUrl = isset(Config::$cateApiUrl) ? Config::$cateApiUrl : exit('请设置分类URL配置');
            $cateApiUrl .= '?name=getcatename&cmds=mget%20';
            $cateStr = '';
            $cateStr = implode("%20", $noCacheKeys);
            $cateApiUrl .= $cateStr;
            $cateApiUrl .= "%0D%0A";
            $cateText = Xz_Http::curl_get_contents($cateApiUrl);
            $cateArr  = explode('@', str_replace(
                array('null', 'nil', '}[', ']{', '][', '}{', "\n"),
                array('{}', '{}', '}@[', ']@{', ']@[', '}@{', ""), $cateText));
            //数组格式化
            if (count($noCacheKeys) === count($cateArr)) {
                $cacheArr = array_combine($noCacheKeys, $cateArr);
            } else {
                foreach ($noCacheKeys as $key => $value) {
                    foreach ($cateArr as $k => $v) {
                        if ($k == $key) {
                            $cacheArr[$value] = $cateArr[$key];
                        } else {
                            $cacheArr[$value] = '[]';
                        }
                    }
                }
            }
            $yac->set($cacheArr);
        }
        if (is_bool($yacCateArr)) {
            $yacCateArr = array();
        }
        $cateData = array_merge($yacCateArr, $cacheArr);
        $newArr   = array();
        foreach ($cateData as $key => $value) {
            $keys                       = explode(':', $key);
            $newArr[$keys[1]][$keys[2]] = $value;
        }
        return $newArr;
    }

    /**
     * 获取子属性
     * @param  [type]  $cateid    分类ID
     * @param  integer $parentid  tid
     * @param  integer $parentVid vid
     * @return [type]            json数据
     */
    public static function getSubProperty($cateid, $parentid = 0, $parentVid = 0)
    {
        if (!empty($parentid) && !empty($parentVid)) {
            $propertyKey = 'backcate:' . $cateid . ':property:' . $parentid . ':' . $parentVid;
        }
        //YAC 缓存获取数据
        $yac         = new Yac("pdcate_");
        $yacProperty = $yac->get($propertyKey);
        if (!$yacProperty) {
            $property = self::getCurl($propertyKey);
            if (isset($property) && !empty($property) && ($property != 'nil')) {
                $property = json_decode($property, true);
                if (is_array($property) && $property) {
                    $yacProperty = json_encode(array_values($property));
                } else {
                    $yacProperty = json_encode(array());
                }
                //写本地缓存
                $yac->set($propertyKey, $yacProperty);
            }
        }
        return $yacProperty;
    }

    /**
     * work后台使用格式化部分数据
     * @param  [type] $id     [description]
     * @param  [type] $select array('info', 'nav', 'subcate', 'property');
     * @return [type]         [description]
     */
    public static function format($id, $select)
    {
        $jsonArr = array(
            'cate'     => '',
            'nav'      => '',
            'subcate'  => '',
            'property' => '',
        );
        $default = array('info', 'nav', 'subcate', 'property');
        foreach ($select as $key => $value) {
            if (!in_array($value, $default)) {
                unset($select[$value]);
            }
        }
        //获取源数据
        $cateArr = self::getBackCate(array($id), $select);
        if ($id == 0) {
            $id = 'cate';
        }
        //数据格式化
        $jsonArr['cate'] = $cateArr[$id]['info'];
        if (in_array('nav', $select)) {
            $jsonArr['nav'] = $cateArr[$id]['nav'];
        }
        if (in_array('subcate', $select)) {
            $subcate = json_decode($cateArr[$id]['subcate'], true);
            if (isset($subcate) && !empty($subcate)) {
                foreach ($subcate as $key => $value) {
                    $subcateArr[] = array(
                        'id'       => $key,
                        'name'     => $value['catename'],
                        'isParent' => $value['isleaf'] ? 'false' : 'true',
                        'trueid'   => isset($value['trueid']) ? $value['trueid'] : '',
                    );
                }
                $jsonArr['subcate'] = json_encode($subcateArr);
            }
        }
        if (in_array('property', $select)) {
            $jsonArr['property'] = $cateArr[$id]['property'];
        }
        return $jsonArr;
    }

    /**
     * 获取单个KEY的数据
     * @param  [type] $key cate KEY
     * @return [type]      [description]
     */
    public static function getCurl($key)
    {
        $cateApiUrl = isset(Config::$cateApiUrl) ? Config::$cateApiUrl : exit('请设置分类URL配置');
        $cateApiUrl .= '?name=getcatename&cmds=get%20';
        $cateApiUrl .= $key;
        $cateApiUrl .= '%0D%0A';
        return Xz_Http::curl_get_contents($cateApiUrl);
    }

    /**
     * 根据产品、企业、采购的分类ID转换为名称
     * 产品按前台分类转换；企业、采购按后台分类转换
     *
     * @access public
     * @return str
     * @author 文帅营
     * @修改日期 2013-04-7 13:52:24
     */
    public static function getCatename(array $cateid, $type = 'pro')
    {
        $ret = array();
        if (in_array($type, array('com', 'buy'))) {
            $catename = self::getBackCate($cateid);
            foreach ($catename as $key => $value) {
                $value = json_decode($value['info'], true);
                if (empty($value)) {
                    continue;
                }

                $ret[$key] = $value['catename'];
            }
        } else {
            $catename = self::getFrontCate($cateid);
            foreach ($catename as $key => $value) {
                $value = json_decode($value['info'], true);
                if (empty($value)) {
                    continue;
                }

                $ret[$key] = $value['catename'];
            }
        }
        return $ret;
    }

    /**
     * 根据产品的1、2、3级分类 和产品保存的属性信息转换前台可读的格式
     * @param  array  $cate     产品的1、2、3级分类
     * @param  [json字符串] $property 产品保存的属性值
     * @return [array]           格式化后的数组
     */
    public static function getProperty(array $cate, $property)
    {

        if ($cate[2] != 0) {
            $cateid          = $cate[2];
            $array['docate'] = $cate[2];
        } elseif ($cate[1] != 0) {
            $cateid          = $cate[1];
            $array['docate'] = $cate[1];
        } elseif ($cate[0] != 0) {
            $cateid          = $cate[0];
            $array['docate'] = $cate[0];
        } else {
            $array['docate'] = 0;
        }
        if (empty($cateid)) {
            return array();
        }
        if ($array['docate'] == 0) {
            return array();
        }
        $property = json_decode($property, true);
        if (empty($property)) {
            return array();
        }
        $propertyArr = Xz_PdCate::getBackCate(array($cateid), array('property'));
        if (!isset($propertyArr[$cateid]['property'])) {
            return array();
        }
        //得到分类的属性数组
        $doproperty = json_decode($propertyArr[$cateid]['property'], true);

        //循环处理用户数据
        $newProperty = array();
        foreach ($doproperty as $key => $value) {
            if (isset($property[$key])) {
                if ($value['inputType'] == 0) {
                    $newKey               = html_entity_decode($value['attname'], ENT_COMPAT, 'UTF-8');
                    $newProperty[$newKey] = $property[$key] . $value['unit'];
                } else {
                    $dovalue = $value['attValue'];
                    if (!is_array($dovalue)) {
                        $dovalue = array();
                    }
                    $newKey = html_entity_decode($value['attname'], ENT_COMPAT, 'UTF-8');
                    foreach ($dovalue as $k => $v) {
                        if (is_array($property[$key])) {
                            foreach ($property[$key] as $pak => $pav) {
                                $newProperty[$newKey] = $pav . $value['unit'];
                            }
                        } else {
                            if ($v['attvid'] == $property[$key]) {
                                $newProperty[$newKey] = $v['attvalue'] . $value['unit'];
                            }
                        }

                        //有子属性
                        if ($value['hasChild'] == 'Y' || $value['hasChild'] == 'N') {
                            $subProperty = Xz_PdCate::getSubProperty($cateid, $value['attid'], $v['attvid']);
                            $subProperty = json_decode($subProperty, true);
                            if (!is_array($subProperty)) {
                                $subProperty = array();
                            }
                            //循环得到相应的键值 子属性的值也有enum
                            foreach ($subProperty as $suk => $suv) {
                                foreach ($property as $pk => $pv) {
                                    if ($pk == $suv['attid']) {
                                        $newKey = $suv['attname'];
                                        if ($suv['inputType'] == 0) {
                                            $newProperty[$newKey] = $property[$pk] . $value['unit'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $newProperty;
    }

    /**
     * 获取没被缓存的KEYs
     * @param  [type] $keys [description]
     * @param  [type] $rs   [description]
     * @return [type]       [description]
     */
    protected function getNoCacheKeys($keys, $rs)
    {
        if (!is_array($keys)) {
            $keys = array();
        }
        if (!is_array($rs)) {
            $rs = array();
        }
        $tmpArr = array();
        foreach ($rs as $key => $value) {
            if ($value) {
                $tmpArr[] = $key;
            }
        }
        return array_diff($keys, $tmpArr);
    }
}

class Xz_Http
{

    /**
     * CURL模拟浏览器
     * Enter description here ...
     * @param string $url URL地址
     * @param post|get $method 是否POST默认GET
     * @param string $post POST内容
     * @param string $returnHeaderInfo 是否返回头信息，true返回头信息和获取的内容数组
     * @return html
     * @author suweilin
     */
    public static function curl($url, $method = '', $post = '', $returnHeaderInfo = false, $timeout = 3)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); //设置超时时间,单位秒
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
            return array(
                $httpCode,
                $str,
            );
        }
        return $str;
    }
    //获取api调取内容
    public static function curl_get_contents($url, $timeout = 2)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); //设置超时时间,单位秒
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $str = curl_exec($ch);
        curl_close($ch);
        return $str;
    }
    /**
     * 可以设置毫秒的curl函数
     *
     * @access public
     * @param mixed $url
     * @param int $timeout
     * @return void
     * @author 刘建辉
     * @修改日期 2013-08-14 10:53:08
     */
    public function curl_get_content_ms($url, $timeout = 800)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout); //设置超时时间,单位毫秒
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1); //毫秒时必备
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $str      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode == 200) {
            return $str;
        } else {
            return false;
        }
    }

}
