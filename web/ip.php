<?php
/**
 * Created by PhpStorm.
 * User: lxh
 * Date: 15-6-23
 * Time: 下午4:21
 * Desc:  获取代理ip
 */
//    $prosyip = '192.168.8.57:888';
    $ip = isset($_GET['ip']) ? $_GET['ip'] : '';
    $type = isset($_GET['type']) ? $_GET['type'] : 'showip';
    $cmd = array(
        'showip' => "ifconfig ppp0 | grep 'inet addr' | awk -F: '{print $2}' | awk '{print $1}'",
        'recon' => '/home/shell/reconnpppoe.sh',
    );
    if(!empty($ip) && isset($cmd[$type])){
        $str = exeCmd($ip, $cmd[$type]);
        return $str;
    }else{
        return '';
    }

    function exeCmd($host, $cmd)
    {
        $user       = "root";
        $passwd     = "123456";
        $connection = ssh2_connect($host, 2828);
        ssh2_auth_password($connection, $user, $passwd);
        $stream = ssh2_exec($connection, $cmd);
        stream_set_blocking($stream, true);
        $str = stream_get_contents($stream);
        return $str;
    }