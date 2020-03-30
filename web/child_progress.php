<?php
/**
 * Copyright(c) 2018-2050,BWSC.Co.Ltd.
 * Created by PhpStorm.
 * User: JoeRowe
 * Date: 2020/3/3 0003
 * Time: 11:32
 *
 */

$redis = new Redis();
$redis->connect("127.0.0.1","6379");

$data = [
    "MsgID" => "123",
    "DateTime" => "2002-08-17 15:30:00",
    "StationName" => "XX台站",
    "SrcCode" => "110000M01",
    "DstCode" => "110000G01",
    "ReplyID" => "1000_ID",
    "Return" => ["Type" => "AgentInfoSet", "Val" => 0, "Desc" => "success"]];
$redis = \Yii::$app->redis;
$cpid = $redis->get($data["MsgID"]);
exec("kill {$cpid} -SIGINT");
$ret = $redis->set($data["MsgID"], json_encode($data));
var_dump($ret);