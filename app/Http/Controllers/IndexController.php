<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
class IndexController extends Controller
{
    //接入微信
    public function index(){
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = env('WX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
             echo $_GET['echostr'];
        }else{
            echo "111";
        }
    }

    /**
         * 处理推送事件
         */
        public function wxEvent()
        {
            $signature = $_GET["signature"];
            $timestamp = $_GET["timestamp"];
            $nonce = $_GET["nonce"];

            $token = env('WX_TOKEN');
            $tmpArr = array($token, $timestamp, $nonce);
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode( $tmpArr );
            $tmpStr = sha1( $tmpStr );

            if( $tmpStr == $signature ){            //验证通过

                // 1 接收数据
                $xml_str = file_get_contents("php://input");

                // 记录日志
                file_put_contents('wx_event.log',$xml_str);
                echo "";
                die;

            }else{
                echo "";
            }
        }




    //获取access_token
    public function getAccessToken(){
            $key = 'wx:access_token';//Redis存入名称
            //检查是否有 token
            $token = Redis::get($key);
            if($token)
            {
                echo "有缓存";echo '</br>';
            }else{
                //echo "无缓存";
                $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSEC');
                //$response = file_get_contents($url);//整个文件读入一个字符串

                //使用guzzle发起get请求
                $client = new Client();//实例化 客户端
                $response = $client->request('GET',$url,['verify'=>false]);//发起请求并接受响应
                $json_str = $response->getBody();   //服务器的响应数据
                //echo $json_str;

                $data = json_decode($json_str,true);//对JSON格式的字符串进行解码
                $token = $data['access_token'];
                //保存到Redis中 时间为 3600
                Redis::set($key,$token);//存入reds。

                Redis::expire($key,3600);//过期时间
            }
            //echo "access_token: ".$token;
            return $token;
    }

    //上传素材
    public function guzzle2(){
            $access_token = $this->getAccessToken();
            $type='image';
            $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$access_token.'&type='.$type;
            //使用guzzle发起get请求
            $client = new Client();//实例化 客户端
            $response = $client->request('POST',$url,[
                        'verify'    => false,
                        'multipart' => [
                            [
                                'name'  => 'media',
                                'contents'  => fopen('timg.jpg','r')
                            ],         //上传的文件路径
                        ]
                    ]);       //发起请求并接收响应
            $data = $response->getBody();
            echo $data;
    }

    //处理消息
        private function responseText($xml,$content){
            $fromUserName=$xml->ToUserName;
            $toUserName=$xml->FromUserName;
            $time=time();
            $msgType="text";
            $template="<xml>
                       <ToUserName><![CDATA[%s]]></ToUserName>
                       <FromUserName><![CDATA[%s]]></FromUserName>
                       <CreateTime>%s</CreateTime>
                       <MsgType><![CDATA[%s]]></MsgType>
                       <Content><![CDATA[%s]]></Content>
                       </xml>";//发送//来自//时间//类型//内容
            echo sprintf($template,$toUserName,$fromUserName,$time,$msgType,$content);
        }


}
