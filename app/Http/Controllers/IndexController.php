<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
class IndexController extends Controller
{
    //接入微信
    public function index(){
        $echoStr =request()->get("echostr","");
        if($this->checkSignature() && !empty($echoStr)){
                //至少微信公众平台第一次接入调用走这个
                echo $echoStr;
        }
    }
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = "mashuaisheng";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    //获取access_token
    public function getAccessToken(){
            $key = 'wx:access_token';//Redis存入名称
            //检查是否有 token
            $token = Redis::get($key);
            if($token)
            {
                //echo "有缓存";echo '</br>';
            }else{
                //echo "无缓存";
                $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSEC');
                $response = file_get_contents($url);//整个文件读入一个字符串
                $data = json_decode($response,true);//对JSON格式的字符串进行解码
                $token = $data['access_token'];
                //保存到Redis中 时间为 3600
                Redis::set($key,$token);//存入reds
                Redis::expire($key,3600);//过期时间
            }
            echo "access_token: ".$token;
    }



}
