<?php

require __DIR__ . '/../../vendor/autoload.php';


$ws_worker = new \Workerman\Worker('websocket://0.0.0.0:2346');

$ws_worker->count = 4;

$ws_worker->onConnect = function ($connection) {
    global $connections, $global_uid;
    // 为这个连接分配一个uid
    $connection->uid = ++$global_uid;
    $connections[$connection->uid] = $connection;
};

$ws_worker->onMessage = function ($connection, $data) {
    // Send hello $data
    $connection->send("OK");
    global $connections;
    file_put_contents(__DIR__ . '/auto_up.log', date('Y-m-d H:i:s', time()) . "\n" . $data . "\n", FILE_APPEND);
    //播发信息
    $json = json_decode($data, true);
    if (!empty($json)) {
        //给所有socket终端发送消息
        foreach ($connections as $k => $con) {
            if ($k !== $connection->uid) {
                $con->send(json_encode($json));
            }
        }
    }
};

// Emitted when connection closed
$ws_worker->onClose = function ($connection) {
    global $connections;
    unset($connections[$connection->uid]);
};

$ws_worker->onError = function ($connection, $code, $msg)
{
    echo "error $code $msg\n";
};

$ws_worker->onWorkerStart = function($worker)
{
    global $connections, $global_uid;
    $global_uid = 0;
    $connections = [];
};

// Run worker
\Workerman\Worker::runAll();