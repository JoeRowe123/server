<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/2/5
 * Time: 17:17
 */

namespace server\controllers;

use server\components\HttpUtil;
use server\components\lib\Client;
use server\components\Tools;
use server\components\XMLService;
use yii\web\Controller;
use yii\web\HttpException;
use yii\web\UploadedFile;

class ServerController extends Controller
{
    /**下发xml指令
     * @return array|mixed
     */
    public function actionInstructionIssued()
    {
        $requestData = \Yii::$app->request->post();
        $retData = ["retCode" => 1, "retData" => [], "retMsg" => ""];
        $cmd = $requestData['request']??"";
        if (!in_array($cmd, \Yii::$app->params['instructions']))
        {
            return ["retCode" => 1, "retMsg" => "指令类型错误"];
        }
        $dstIP = $requestData['dstIp']??"";
        if (!$dstIP || !Tools::ping($dstIP))
        {
            return ["retCode" => 502, "retMsg" => "IP网络错误"];
        }
        $data = $requestData['param'];
        $dom = XMLService::arr2xml($data);
        $xml = $dom->saveXML();
        $gw_res = HttpUtil::postFile($dstIP . "/api/request", $xml);
        $gw_res = json_decode($gw_res, true);
        if (!$gw_res || $gw_res['retCode'])
        {
            return ["retCode" => 1, "retMsg" => $gw_res["retMsg"]??"网关异常"];
        }
        if ($cmd == "channel_scan_query")
        {
            return $gw_res;
        }
        $redis = \Yii::$app->redis;
        $MsgID = $data["MsgID"];
        $num = 0;
        for (; ;)
        {
            $response = $redis->get($MsgID);
            if ($response)
            {
                $retData["retCode"] = 0;
                $retData["retData"] = json_decode($response, true);
                $redis->del($MsgID);
                break;
            }
            if ($num >= 150)
            {
                $retData["retMsg"] = "请求超时";
                break;
            }
            usleep(200000);
            $num++;
        }

        return $retData;
    }

    /**接收主动上报xml
     * @return bool|mixed
     *
     * @throws HttpException
     */
    public function actionAutoUp()
    {
        if (\Yii::$app->request->isPost)
        {
            $image = UploadedFile::getInstanceByName('file');
            $dom = new \DOMDocument("1.0", \Yii::$app->params['encoding']);
            if (!$image)
            {
                $xml = file_get_contents("php://input"); //接收post数据
                $dir = \Yii::$app->params['upXmlPath'] . "/" . date("Ymd", time());
                $fullName = $dir . "/" . uniqid() . ".xml";
                if (!is_dir($dir))
                {
                    mkdir($dir, 0777, true);
                }
                file_put_contents($fullName, $xml);
            } else
            {//上传文件
                $imageName = $image->getBaseName();

                $ext = $image->getExtension();
                if ($ext != "xml")
                {
                    return ['retCode' => 1, 'retMsg' => '请上传XML文件'];
                }
                $path = \Yii::$app->params['upXmlPath'] . "/" . date('Ymd/');
                if (!file_exists($path))
                {
                    mkdir($path, 0755, true);
                }
                $fullName = $path . $imageName . "." . $ext;
                $image->saveAs($fullName);
            }
            $dom->load($fullName);
            $arr = XMLService::xml2arr($dom);
            if (!($arr['Msg']??0))
            {
                return ["retCode" => 1, "retMsg" => "XML数据错误"];
            }

            $data = $arr["Msg"];
            file_put_contents(\Yii::$app->params['upXmlPath'] . "/log.txt", json_encode($arr, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            $type = $data["Return"]["Type"]??"";
            /*=================================================================*/
            switch ($type)
            {
                case "ChannelScanQuery":
                {
                    if (isset($data["ReturnInfo"]["ChannelScanQuery"]["ChannelScan"]))
                    {
                        $freq = $data["ReturnInfo"]["ChannelScanQuery"]["ChannelScan"];
                        if (array_keys($freq) !== array_keys(array_keys($freq)))
                        {
                            $channels = $freq["Channel"];
                            if (array_keys($channels) !== array_keys(array_keys($channels)))
                            {
                                $freq["Channel"] = [];
                                $freq["Channel"][] = $channels;
                            }
                            $data["ReturnInfo"]["ChannelScanQuery"]["ChannelScan"] = [];
                            $data["ReturnInfo"]["ChannelScanQuery"]["ChannelScan"][] = $freq;
                        } else
                        {
                            $freqArr = [];
                            foreach ($freq as $val)
                            {
                                $channel = $val["Channel"];
                                if (array_keys($channel) !== array_keys(array_keys($channel)))
                                {
                                    $val["Channel"] = [];
                                    $val["Channel"][] = $channel;
                                    $freqArr[] = $val;
                                } else
                                {
                                    $freqArr[] = $val;
                                }
                            }
                            $data["ReturnInfo"]["ChannelScanQuery"]["ChannelScan"] = $freqArr;
                        }
                    }
                    break;
                }
                case "GetRecordVideoTime":
                {
                    if (isset($data["ReturnInfo"]["GetRecordVideoTime"]["RecordeVideoInfo"]))
                    {
                        $videoInfo = $data["ReturnInfo"]["GetRecordVideoTime"]["RecordeVideoInfo"];
                        if (array_keys($videoInfo) !== array_keys(array_keys($videoInfo)))
                        {
                            $data["ReturnInfo"]["GetRecordVideoTime"]["RecordeVideoInfo"] = [];
                            $data["ReturnInfo"]["GetRecordVideoTime"]["RecordeVideoInfo"][] = $videoInfo;
                        }
                    }
                    break;
                }
                case "AlarmSearchRFSet":
                {
                    if (isset($data["ReturnInfo"][$type]))
                    {
                        $alarmInfo = $data["ReturnInfo"]["AlarmSearchRFSet"];
                        if (array_keys($alarmInfo) !== array_keys(array_keys($alarmInfo)))
                        {
                            $rf = $alarmInfo["AlarmSearchRF"];

                            if (array_keys($rf) !== array_keys(array_keys($rf)))
                            {
                                $alarmInfo["AlarmSearchRF"] = [];
                                $alarmInfo["AlarmSearchRF"][] = $rf;
                            }
                            $data["ReturnInfo"]["AlarmSearchRFSet"] = [];
                            $data["ReturnInfo"]["AlarmSearchRFSet"][] = $alarmInfo;
                        } else
                        {
                            $rfArr = [];
                            foreach ($alarmInfo as $val)
                            {
                                $rf = $val["AlarmSearchRF"];
                                if (array_keys($rf) !== array_keys(array_keys($rf)))
                                {
                                    $rfArr[] = ["Freq" => $val["Freq"], "AlarmSearchRF" => [$rf]];
                                } else
                                {
                                    $rfArr[] = $val;
                                }
                            }
                            $data["ReturnInfo"]["AlarmSearchRFSet"] = $rfArr;
                        }
                    }
                    break;
                }
                case "AlarmSearchStreamSet":
                {
                    if (isset($data["ReturnInfo"]["AlarmSearchStreamSet"]))
                    {
                        $alarmInfo = $data["ReturnInfo"]["AlarmSearchStreamSet"];
                        if (array_keys($alarmInfo) !== array_keys(array_keys($alarmInfo)))
                        {
                            $stream = $alarmInfo["AlarmSearchStream"];
                            if (array_keys($stream) !== array_keys(array_keys($stream)))
                            {
                                $alarmInfo["AlarmSearchStream"] = [];
                                $alarmInfo["AlarmSearchStream"][] = $stream;
                            }
                            $data["ReturnInfo"]["AlarmSearchStreamSet"] = [];
                            $data["ReturnInfo"]["AlarmSearchStreamSet"][] = $alarmInfo;
                        } else
                        {
                            $streamArr = [];
                            foreach ($alarmInfo as $val)
                            {
                                $stream = $val["AlarmSearchStream"];
                                if (array_keys($stream) !== array_keys(array_keys($stream)))
                                {
                                    $streamArr[] = ["Freq" => $val["Freq"], "AlarmSearchStream" => [$stream]];
                                } else
                                {
                                    $streamArr[] = $val;
                                }
                            }
                            $data["ReturnInfo"]["AlarmSearchStreamSet"] = $streamArr;
                        }
                    }
                    break;
                }
                case "AlarmSearchPSet":
                {
                    if (isset($data["ReturnInfo"]["AlarmSearchPSet"]))
                    {
                        $alarmInfo = $data["ReturnInfo"]["AlarmSearchPSet"];
                        if (array_keys($alarmInfo) !== array_keys(array_keys($alarmInfo)))
                        {
                            $ch = $alarmInfo["AlarmSearchP"];
                            if (array_keys($ch) !== array_keys(array_keys($ch)))
                            {
                                $alarmInfo["AlarmSearchP"] = [];
                                $alarmInfo["AlarmSearchP"][] = $ch;
                            }
                            $data["ReturnInfo"]["AlarmSearchPSet"] = [];
                            $data["ReturnInfo"]["AlarmSearchPSet"][] = $alarmInfo;
                        } else
                        {
                            $chArr = [];
                            foreach ($alarmInfo as $val)
                            {
                                $ch = $val["AlarmSearchP"];
                                if (array_keys($ch) !== array_keys(array_keys($ch)))
                                {
                                    $chArr[] = ["Freq" => $val["Freq"], "ServiceID" => $val["ServiceID"], "AlarmSearchRF" => [$ch]];
                                } else
                                {
                                    $chArr[] = $val;
                                }
                            }
                            $data["ReturnInfo"]["AlarmSearchPSet"] = $chArr;
                        }
                    }
                    break;
                }
            }
            if (in_array($type, \Yii::$app->params['report_instructions']))
            {
                //主动上报接口
                HttpUtil::postCurl(\Yii::$app->params["reportUrl"], $data);
            } else
            {
                $redis = \Yii::$app->redis;
                $redis->set($data["ReplyID"], json_encode($data));
            }
            return ["retCode" => 0, "retMsg" => "success"];
        } else
        {
            return ["retCode" => 1, "retMsg" => "请求错误"];
        }
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

    public static function actionTest()
    {

    }

}