<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/2/12
 * Time: 17:07
 */

namespace server\components;


class XMLService
{
    /**生成XML文件
     * @param $arr
     * @return bool|string
     */
    public static function createXmlFile($arr, $cmd_type = "Msg")
    {
        $xmlFilename = $cmd_type . "-" . $arr['MsgID'] . ".xml";
        $dir = \Yii::$app->params['xmlPath'] . "/" . date("Ymd", time());
        $filePath = $dir . "/" . $xmlFilename;
        if (!is_dir($dir))
        {
            mkdir($dir, 0777, true);
        }
        $dom = self::arr2xml($arr);
        if ($dom->save($filePath))
        {
            return $filePath;
        } else
        {
            return false;
        }
    }

    /**数组转xml
     * @param $arr
     * @param int $dom
     * @param int $root
     * @param string $rootName
     * @return int
     */
    public static function arr2xml($arr, $dom = 0, $root = 0, $rootName = "Msg")
    {
        if (!$dom)
        {
            $arr = array_merge(["Version" => "1.0", "Type" => "WSMonDown", "Priority" => 1], $arr);
            $dom = new \DOMDocument("1.0", \Yii::$app->params['encoding']);
            $dom->standalone = true;
        }
        if (!$root)
        {
            $root = $dom->createElement($rootName);// 根节点
            $dom->appendChild($root);
        }
        foreach ($arr as $key => $val)
        {
            if (is_string($key))
            { //关联数组
                $leaf = $dom->createElement($key);
                if (is_array($val))
                {
                    if (array_keys($val) === array_keys(array_keys($val)))
                    {//val为索引数组
                        self::arr2xml($val, $dom, $root, $key);
                    } else
                    {
                        $root->appendChild($leaf);
                        self::arr2xml($val, $dom, $leaf, $key);
                    }
                } else
                {
                    $root->setAttribute($key, $val);
                }
            } else
            { //索引数组
                if (is_array($val))
                {
                    $child = $dom->createElement($rootName);
                    $root->appendChild($child);
                    self::arr2xml($val, $dom, $child, $rootName);
                } else
                {
                    $root->setAttribute($key, $val);
                }
            }
        }
        //return $dom->saveXML();
        return $dom;
    }

    /**
     * @param $xml
     * @param int $isFile
     * @return bool|mixed
     */
    public static function xml2arr($oDomNode = null)
    {
        if (!$oDomNode->hasChildNodes())
        {
            $mResult = $oDomNode->nodeValue;
        } else
        {
            $mResult = array();
            foreach ($oDomNode->childNodes as $oChildNode)
            {
                $oChildNodeList = $oDomNode->getElementsByTagName($oChildNode->nodeName);
                $iChildCount = 0;
                foreach ($oChildNodeList as $oNode)
                {
                    if ($oNode->parentNode->isSameNode($oChildNode->parentNode))
                    {
                        $iChildCount++;
                    }
                }
                $mValue = self::xml2arr($oChildNode);
                $sKey = ($oChildNode->nodeName{0} == '#') ? 0 : $oChildNode->nodeName;
                $mValue = is_array($mValue) ? $mValue [$oChildNode->nodeName] : $mValue;

                if (is_string($mValue))
                {
                    $mValue = trim(str_replace(array("\r\n", "\r", "\n"), "", $mValue));
                }
                if (!empty($mValue))
                {
                    if ($iChildCount > 1)
                    {
                        $mResult [$sKey] [] = $mValue;
                    } else
                    {
                        $mResult [$sKey] = $mValue;
                    }
                }
            }
            if (count($mResult) == 1 && isset ($mResult [0]) && !is_array($mResult [0]))
            {
                $mResult = $mResult [0];
            }
        }
        $arAttributes = array();
        if ($oDomNode->hasAttributes())
        {
            foreach ($oDomNode->attributes as $sAttrName => $oAttrNode)
            {
                $arAttributes ["{$oAttrNode->nodeName}"] = $oAttrNode->nodeValue;
            }
        }
        if ($oDomNode instanceof DOMElement && $oDomNode->getAttribute('xmlns'))
        {
            $arAttributes ["xmlns"] = $oDomNode->getAttribute('xmlns');
        }
        if (count($arAttributes))
        {
            if (!is_array($mResult))
            {
                $mResult = (trim($mResult)) ? array($mResult) : array();
            }
            $mResult = array_merge($arAttributes, $mResult);
        }
        $arResult = array($oDomNode->nodeName => $mResult);
        return $arResult['#document']??$arResult;
    }
}