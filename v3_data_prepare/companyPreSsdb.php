<?php
/**
 * 企业信息预热
 */
include_once "ssdb.php";
$prepare = new PrepareCom();
$prepare->run(0);
class PrepareCom
{
    public $startid = 0;
    public $limit   = 0;
    public function __construct()
    {
        $this->localObj = new Table("local");
    }

    public function run()
    {
        $this->runComLog();
        $this->runCom();
    }

    /**
     * 将之前产品预热所属企业存入ssdb
     * @return [type] [description]
     */
    public function runComLog()
    {
        $comLog = __DIR__ . "/com.log";
        //将企业id去重
        exec("sort " . $comLog . " |uniq > " . __DIR__ . "/comUniq.log");
        $f = fopen(__DIR__ . "/comUniq.log", 'r');
        if (!$f) {
            return false;
        }
        while (!feof($f)) {
            $cid = trim(fgets($f));
            $this->runPrepare($cid);
        }
    }

    /**
     * 如果生成的数量不够，则继续生成
     * @return [type] [description]
     */
    public function runCom()
    {
        if ($this->limit > 1000) {
            return false;
        }
        while (1) {
            $companyRs = $this->localObj->findAll("select cid from gongchanginfo.gc_company where cid > '" . $this->cid . "' and status = 1 order by cid asc limit 10");
            if (empty($companyRs)) {
                break;
            }
            foreach ($companyRs as $company) {
                $this->cid = $company['cid'];
                if ($this->limit > 1000) {
                    break 2;
                }
                $this->runPrepare($company["cid"]);
            }
        }
    }

    /**
     * 开始处理企业数据
     * @param  [type] $cid [description]
     * @return [type]      [description]
     */
    public function runPrepare($cid)
    {
        if ($cid < 1) {
            return false;
        }
        $companyRs = $this->localObj->findOne("select * from gongchanginfo.gc_company where cid = '{$cid}' limit 1");
        if (empty($companyRs) || $companyRs["status"] != 1) {
            return false;
        }
        $companyData = $this->localObj->findOne("select * from gongchanginfo.gc_company_data where cid = '{$cid}' limit 1");
        if ($companyData) {
            $companyRs = array_merge($companyRs, $companyData);
            unset($companyRs['companydesc']);
        }
        try {
            $ssdbObj = new SimpleSSDB("192.168.8.18", 8888);
        } catch (Exception $e) {
            $ssdbObj = new SimpleSSDB("192.168.8.18", 8888);
        } catch (Exception $e) {
            echo "无法链接ssdb\n";
        }
        $ssdbObj->set("gc:cominfo:" . $cid, json_encode($companyRs));
        $ssdbObj->close();
        file_put_contents(__DIR__ . "/comPrepare.log", "gc:cominfo:" . $cid . "\n", FILE_APPEND);
        $this->cid = $cid;
        $this->limit++;
        return true;
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
