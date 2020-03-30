<?php
/**
 * Copyright(c) 2018-2050,BWSC.Co.Ltd.
 * Created by PhpStorm.
 * User: JoeRowe
 * Date: 2020/3/18 0018
 * Time: 16:33
 *
 */
namespace server\controllers;

use Workerman\Worker;
use yii\web\Controller;

class WorkermanController extends Controller
{
    public function actionIndex()
    {

// Create a Websocket server
        $ws_worker = new Worker('websocket://0.0.0.0:2346');

// 4 processes
        $ws_worker->count = 4;

// Emitted when new connection come
        $ws_worker->onConnect = function ($connection) {
            echo "New connection\n";
        };

// Emitted when data received
        $ws_worker->onMessage = function ($connection, $data) {
            // Send hello $data
            $connection->send('Hello ' . $data);
        };

// Emitted when connection closed
        $ws_worker->onClose = function ($connection) {
            echo "Connection closed\n";
        };

// Run worker
        Worker::runAll();
    }

}