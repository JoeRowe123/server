<?php
/**
 * Copyright(c) 2018-2050,BWSC.Co.Ltd.
 * Created by PhpStorm.
 * User: JoeRowe
 * Date: 2020/3/19 0019
 * Time: 15:21
 *
 */

namespace server\components;


class Socket
{
    public function transponder($sendData)
    {
        //创建一个socket套接流
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        /****************设置socket连接选项，这两个步骤你可以省略*************/
        //接收套接流的最大超时时间1秒，后面是微秒单位超时时间，设置为零，表示不管它
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 1, "usec" => 0));
        //发送套接流的最大超时时间为6秒
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array("sec" => 6, "usec" => 0));
        /****************设置socket连接选项，这两个步骤你可以省略*************/

        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, true);

        //连接服务端的套接流，这一步就是使客户端与服务器端的套接流建立联系
        if (socket_connect($socket, "127.0.0.1", "2346") == false)
        {
            $retData['result'] = 1;
            $retData['retMsg'] = $this->doEncoding(socket_strerror(socket_last_error()));
        } else
        {
            $message = json_encode($sendData);
            //转为GBK编码，处理乱码问题，这要看你的编码情况而定，每个人的编码都不同
            $message = mb_convert_encoding($message, 'GBK', 'UTF-8');
            //向服务端写入字符串信息
            if (self::write($socket, self::MSG_SRV_MGT_JSON, $message) == false)
            {
                $retData['result'] = 1;
                $retData['retMsg'] = $this->doEncoding(socket_strerror(socket_last_error()));
            } else
            {
                //读取服务端返回来的套接流信息0
                /*while ($callback = @socket_read($socket, 1024))
                {
                    //$retData = empty(json_decode($callback, true)) ? $callback : json_decode($callback, true);
                    $retData = $callback;
                }*/
                if ($callback = $header = @socket_read($socket, 1024))
                {
                    $retData = ["result"=>1,"retMsg" => "success"];
                } else
                {
                    $retData['result'] = 1;
                    $retData['retMsg'] = "无返回值";
                }
            }
        }
        socket_close($socket);
        return $retData;
    }

    /**
     * @param $str
     * @return string
     */
    function doEncoding($str)
    {
        $encode = strtoupper(mb_detect_encoding($str, ["ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5']));
        if ($encode != 'UTF-8')
        {
            $str = mb_convert_encoding($str, 'UTF-8', $encode);
        }
        return $str;
    }

    /**发送数据
     * @param $socket
     * @param $tag
     * @param $msg
     * @return int
     */
    private static function write($socket, $type, $msg)
    {
        $buf = pack('N', $type) . pack('N', strlen($msg)) . $msg;
        return @socket_write($socket, $buf);
    }

    /**
     * 接收数据
     * @param socket $socket
     * @return bool|string
     */
    private static function read($socket)
    {
        $header = @socket_read($socket, 8);

        if ($header !== false)
        {
            $tmp = @unpack('N1tag/N1len', $header);
            if ($tmp === FALSE)
            {
                return FALSE;
            }
            //print_r($tmp);
            $tag = $tmp['tag'];
            $len = $tmp['len'];
            $responseMsg = null;
            while ($len > 0)
            {
                $buf = null;
                $readlen = @socket_recv($socket, $buf, $len, 0);
                if ($readlen === false)
                {
                    break;
                }
                $len = $len - $readlen;
                $responseMsg .= $buf;
            }

            return $responseMsg ? trim($responseMsg) : FALSE;
        }
        return FALSE;
    }
}