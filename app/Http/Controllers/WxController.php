<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
class WxController extends Controller
{
    //微信公众平台
        public function index(){
        $res = request()->get('echostr','');
        if($this->checkSignature() && !empty($res)){
             echo $res;
        }
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
                return true;
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
                return $token;
            }

            /**
            * 处理推送事件
            */
            public function wxEvent(){
              //验签
              if($this->check()==false){
                //验签不通过
                exit;
              }

              // 1 接收数据
              $xml_str = file_get_contents("php://input");
              // 记录日志
              $log_str = date('Y-m-d H:i:s') . '>>>>>' . $xml_str .  " \n\n";
              file_put_contents('wx_event.log',$log_str,FILE_APPEND);

            }

        //关注回复
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
                    "欢迎来到微信荣耀",
                    "荣耀还有5秒到达战场，请做好准备",
                    "这场荣耀，你已经失败了",
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
                    case'查看所有命令';
                        $Content='在吗，你是谁，1，图文';
                        break;
                    case'在吗';
                        $Content='我在';
                        break;
                    case'你是谁';
                        $Content='我是微信公众号';
                        break;
                    case'1';
                        $Content='2';
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
                    $Content='未能识别您的信息，请输入：查看所有命令';
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

    //自定义菜单
    public function createMenu(){
        $access_token = $this->token();
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
        $menu = [
                    'button'    => [
                        [
                            'type'  => 'view',
                            'name'  => '商城',
                            'url'   => 'http://mss.mashukai.top/'
                        ],
                        [
                            'type'  => 'view',
                            'name'  => '签到',
                            'url'   => 'http://weixin.2004.com/test/qiandao'
                        ],[
                        "name"=> "erji",
                         "sub_button"=> [
                             [
                              "type"=> "pic_sysphoto",
                              "name"=> "系统",
                              "key"=> "rselfmenu_1_0"
                             ],
                             [
                              "type"=> "pic_photo_or_album",
                              "name"=> "拍照",
                              "key"=> "rselfmenu_1_1"
                             ],
                             [
                               "type"=> "pic_weixin",
                               "name"=> "图片",
                               "key"=> "rselfmenu_1_2"
                             ]
                          ]
                        ]
                    ],
                ];
                    //使用guzzle发起get请求
                    $client = new Client();//实例化 客户端
                    $response = $client->request('POST',$url,[
                                'verify'    => false,
                                'body'      =>json_encode($menu,JSON_UNESCAPED_UNICODE),
                            ]);       //发起请求并接收响应
                    $data = $response->getBody();
                    echo $data;
    }

}
