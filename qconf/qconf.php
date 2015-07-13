<?php
//$value = Qconf::getConf("/attr");
//print_r($value);
//
//$host = Qconf::getHost("/attr");
//print_r($host);
//echo "\r\n";
//require 'Work.php';
//$worker = new Work('127.0.0.1:2181');
//$worker->run();

require '../lib/Zookeeper_Api.php';
$zk = new Zookeeper_Api('localhost:2181');
//var_dump($zk->get('/'));
//var_dump($zk->getChildren('/'));
//var_dump($zk->set('/test123', 'abc'));
//var_dump($zk->get('/test123'));
//var_dump($zk->getChildren('/'));
//var_dump($zk->set('/foo/001', 'bar1'));
//var_dump($zk->set('/foo/002', 'bar2'));
//var_dump($zk->getChildren('/foo'));

