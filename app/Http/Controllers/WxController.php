<?php

namespace App\Http\Controllers;
use App\Http\Model\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
class WxController extends Controller
{
    //微信公众平台
        public function index(){
            $this->responseMsg();
        }
        //配置连接
        private function checkSignature()
        {
            $signature = $_GET["signature"];
            $timestamp = $_GET["timestamp"];
            $nonce = $_GET["nonce"];

            $token = "mss";
            $tmpArr = array($token, $timestamp, $nonce);
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode( $tmpArr );
            $tmpStr = sha1( $tmpStr );

            if( $tmpStr == $signature ){
                $xml_str = file_get_contents("php://input");
                file_put_contents('wx_event.log',$xml_str);
                echo "";
                die;
            }else{
                return false;
            }
        }


        //处理推送事件
            public function wx(){
                $signature = $_GET["signature"];
                $timestamp = $_GET["timestamp"];
                $nonce = $_GET["nonce"];

                $token = "mss";
        //        echo $token;die;
                $tmpArr = array($token, $timestamp, $nonce);
                sort($tmpArr, SORT_STRING);
                $tmpStr = implode( $tmpArr );
                $tmpStr = sha1( $tmpStr );

                if( $tmpStr == $signature ){
                    $xml_str = file_get_contents("php://input");
                    file_put_contents('wx_event.log',$xml_str);
                    echo "";
                    die;
                }else{
                    return false;
                }
            }
            //获取token
            public function token(){
                $key="wx:access_token";
                $token = Redis::get($key);
                if($token){
                    echo"有缓存";
                }else{
                    echo"无缓存";
                    $APPID="wx081332fd20aa0b5e";
                    $APPSECRET="ea8f602332da945cb62c6804ccf5e419";
                    $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$APPID}&secret={$APPSECRET}";
                    $respon = file_get_contents($url);
                    $data = json_decode($respon,true);
                    $token = $data['access_token'];
                    Redis::set($key,$token);//存redis
                    Redis::expire($key,3600);//设置过期时间
                }
                echo "access_token：".$token;
            }
public function responseMsg(){
        $postStr=file_get_contents("php://input");
        $postobj=simplexml_load_string($postStr);
        if($postobj->MsgType=='event'){
            if($postobj->Event=='subscribe'){
                $ToUserName=$postobj->FromUserName;
                $FromUserName=$postobj->ToUserName;
                $CreateTime=time();
                $MsgType='text';
                $a=[
                    "欢迎",
                    "来了老弟",
                    "什么风把你吹来了",
                    "welcome",
                ];
                $array=$a;
                $Content=$array[array_rand($array)];
                $temple='<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Content><![CDATA['.$Content.']]></Content>
                         </xml>';
                $info =  sprintf($temple,$ToUserName,$FromUserName,$CreateTime,$MsgType,$Content);

                echo $info;
                exit;
                }
            }else if($postobj->MsgType=='text'){
            //接收回复
                $msg = $postobj->Content;
                $ToUserName=$postobj->FromUserName;
                $FromUserName=$postobj->ToUserName;
                $CreateTime=time();
                $MsgType='text';
                switch($msg){
                    case'命令';
                        $Content='在吗，你是，1，图文';
                        break;
                    case'在吗';
                        $Content='在呢';
                        break;
                    case'你是';
                        $Content='2080';
                        break;
                    case'1';
                        $Content='one';
                        break;
                    case'图文';
                        $Content=[
                            'Title'=>'哈哈',
                            'Description'=>'哈哈',
                            'PicUrl'=>'https://ss1.bdstatic.com/70cFvXSh_Q1YnxGkpoWK1HF6hhy/it/u=2583035764,1571388243&fm=26&gp=0.jpg',
                            'Url'=>'http://jd.com',
                    ];
                    $this->textimg($postobj,$Content);
                    default:
                    $Content='你可以尝试一下换个命令：比如命令';
                    break;
            }
            $temple='<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA['.$Content.']]></Content>
                    </xml>';
            $info =  sprintf($temple,$ToUserName,$FromUserName,$CreateTime,$MsgType,$Content);
            echo $info;
            // exit;
        }

    }

}
