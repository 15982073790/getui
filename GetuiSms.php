<?php

namespace Common\Logic;
use Mrstock\Helper\Config;

class GetuiSmsLogic
{
    private $getuiSmsAppID;
    private $getuiSmsAppKey;
    private $getuiSmsAppSecret;
    private $getuiSmsMasterSecret;

    public function __construct()
    {
        $getui = Config::get('getuisms');
        $this->getuiSmsAppID = $getui['getuiSmsAppID'];
        $this->getuiSmsAppKey = $getui['getuiSmsAppKey'];
        $this->getuiSmsAppSecret = $getui['getuiSmsAppSecret'];
        $this->getuiSmsMasterSecret = $getui['getuiSmsMasterSecret'];
        $this->apiUrl = "https://restapi.getui.com/v2/";
        $this->getuiAuthToken = 'getuipushauthtoken';
    }

    /**
     * 鉴权接口
     */
    public function AuthSign()
    {
        //鉴权url
        $url = "https://openapi-smsp.getui.com/v1/sps/auth_sign";
        //将个推短信服务提供的app对应的appkey和masterSecret，可自行替换
        $appkey = $this->getuiSmsAppKey;
        $masterSecret = $this->getuiSmsMasterSecret;
        $appId = $this->getuiSmsAppID;
        $timestamp = $this->micro_time();
        $signCombination = $appkey . $timestamp . $masterSecret;
        $sign = hash("sha256", $signCombination);
        $params = array();
        $params["sign"] = $sign;
        $params["timestamp"] = $timestamp;
        $params["appId"] = $appId;
        //http头部
        $headers = array(
            "Content-Type:application/json;charset=utf-8",
            "Accept:application/json;charset=utf-8"
        );
        //json序列化
        $params = json_encode($params);
        $result = $this->do_post($url, $params, $headers);
        $result = json_decode($result, true);
        if ($result['result'] != 20000) {
            throw new \Exception($result['msg'], -1);
        }
        return $result['data']['authToken'];
    }

    /**
     * 群推接口
     */
    public function SmsPushList($mobile, $code)
    {
        //短信群推url
        $url = "https://openapi-smsp.getui.com/v1/sps/push_sms_list";
        $appId = $this->getuiSmsAppID;
        $requestDataObject = array();
        $requestDataObject["appId"] = $appId;
        $authToken = $this->AuthSign();
        $requestDataObject["authToken"] = $authToken;
        $requestDataObject["smsTemplateId"] = "202207011621290089";
        //params中填写你的模版中的占位符参数；
        $params["code"] = (string)$code;
        $requestDataObject["smsParam"] = $params;
        $requestDataObject["recNum"] = (array)md5($mobile);
        $headers = array(
            "Content-Type:application/json;charset=utf-8",
            "Accept:application/json;charset=utf-8"
        );
        //json序列化
        $params = json_encode($requestDataObject);
        $result = $this->do_post($url, $params, $headers);
        if ($result['result'] != 20000) {
            throw new \Exception($result['msg'], -1);
        }
    }


    private function do_post($url, $params, $headers)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }


    private function micro_time()
    {
        list($usec, $sec) = explode(" ", microtime());
        $time = ($sec . substr($usec, 2, 3));
        return $time;
    }
}
