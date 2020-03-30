<?php
/**
 * Copyright(c) 2018-2050,BWSC.Co.Ltd.
 * Created by PhpStorm.
 * User: JoeRowe
 * Date: 2020/3/3 0003
 * Time: 11:14
 *
 */

$redis = new Redis();
$redis->connect("127.0.0.1","6379");
$MsgID = '123';
$retData = ["retCode" => 1, "retData" => [], "retMsg" => ""];
ini_set('max_execution_time', 10);
$pid = pcntl_fork(); //fork出子进程
pcntl_wait($status); // 父进程必须等待一个子进程退出后，再创建下一个子进程。

if ($pid == -1)
{ // 创建错误，返回-1
    die('进程fork失败');
}

$child_id = $pid; //子进程的ID
$redis->set($MsgID,$child_id);
$response = $redis->get($MsgID);
$pid = posix_getpid(); //获取当前进程Id
$ppid = posix_getppid(); // 进程的父级ID
$retData["retData"] = json_decode($response, true);
var_dump($retData);
