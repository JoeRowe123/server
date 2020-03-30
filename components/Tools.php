<?php
/**
 * Copyright(c) 2018-2050,BWSC.Co.Ltd.
 * Created by PhpStorm.
 * User: JoeRowe
 * Date: 2020/3/26 0026
 * Time: 17:18
 *
 */

namespace server\components;


use server\components\lib\Client;

class Tools
{
    /**检测网络通信是否异常
     * @param $ip
     * @return bool
     */
    public static function ping($ip)
    {
        $ip_port = explode(':', $ip);
        if ($ip_port[0] == str_replace("https://", "", str_replace("http://", "", \Yii::$app->request->hostInfo)))
        {
            return true;
        }
        if (filter_var($ip_port[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
        {        //IPv6
            $socket = socket_create(AF_INET6, SOCK_STREAM, SOL_TCP);
        } elseif (filter_var($ip_port[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
        {    //IPv4
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        } else
        {
            return FALSE;
        }

        if (!isset($ip_port[1]))
        {        //没有写端口则指定为80
            $ip_port[1] = '80';
        }
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 1, "usec" => 0));
        //发送套接流的最大超时时间为1秒
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array("sec" => 1, "usec" => 0));
        /****************设置socket连接选项，这两个步骤你可以省略*************/

        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, true);

        //连接服务端的套接流，这一步就是使客户端与服务器端的套接流建立联系
        //var_dump(socket_connect($socket, $ip_port[0], $ip_port[1]));die;
        if (@socket_connect($socket, $ip_port[0], $ip_port[1]) != true)
        {
            return FALSE;
        }
        socket_close($socket);
        return true;
    }

    /**webSocket客户端
     * @param string $send_data
     * @return bool|null|string
     */
    public static function WsClient(string $send_data)
    {

        $url = "ws://127.0.0.1:2346"; //服务地址

        $client = new Client($url); //实例化

        $client->send($send_data); //发送数据

        $result = $client->receive(); //接收数据

        $client->close();//关闭连接

        return $result;
    }

    /**上报信息存入redis-list  只存10条数据
     * @param $msgID
     * @param string $msg_json
     * @return bool
     */
    public static function reportSave($key, string $msg_json)
    {
        $redis = \Yii::$app->redis;
        if ($redis->lpush($key, $msg_json))
        {
            if ($redis->llen($key) > 10)
            {
                $redis->rpop($key);
            }
            return TRUE;
        }
        return FALSE;
    }

    /**获取队列最新10条数据
     * @param $key
     * @return mixed
     */
    public static function getList($key)
    {
        return \Yii::$app->redis->lrange($key, 0, -1);
    }

    /**删除redis
     * @param $key
     * @return mixed
     */
    public static function delRedis($key)
    {
        return \Yii::$app->redis->del($key);
    }


}