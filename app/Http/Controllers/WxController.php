<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
class WxController extends Controller
{
protected $xml_obj;
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
              // 1 接收数据
              $xml_str = file_get_contents("php://input");
              dd($xml_str);
              // 记录日志
              $log_str = date('Y-m-d H:i:s') . '>>>>>' . $xml_str .  " \n\n";
              file_put_contents('wx_event.log',$log_str,FILE_APPEND);

              $obj = simplexml_load_string($xml_str);//将文件转换成 对象
              var_dump($obj);die;
                      //$this->xml_obj = $obj;
                      $msg_type = $obj->MsgType;      //推送事件的消息类型
                      switch($msg_type){
                          case 'event' :
                              if($obj->Event=='subscribe')        // subscribe 扫码关注
                              {
                                  echo $this->subscribe();
                                  exit;
                              }elseif($obj->Event=='unsubscribe')     // // unsubscribe 取消关注
                              {
                                  echo "";
                                  exit;
                              }elseif ($obj->Event=='CLICK')          // 菜单点击事件
                              {
                                  $this->clickHandler();
                                  // TODO
                              }elseif($obj->Event=='VIEW')            // 菜单 view点击 事件
                              {
                                  // TODO
                              }
                              break;

                          case 'text' :           //处理文本信息
                              $this->textHandler();
                              break;

                          case 'image' :          // 处理图片信息
                              $this->imageHandler();
                              break;

                          default:
                              echo 'default';
                      }

                      echo "";
                }
            }

            /**
                 * 处理文本消息
                 */
                protected function textHandler()
                {
                    echo '<pre>';print_r($this->xml_obj);echo '</pre>';
                    $data = [
                        'open_id'       => $this->xml_obj->FromUserName,
                        'msg_type'      => $this->xml_obj->MsgType,
                        'msg_id'        => $this->xml_obj->MsgId,
                        'create_time'   => $this->xml_obj->CreateTime,
                    ];

                    //入库
                    WxMediaModel::insertGetId($data);

                }

                /**
                 * 处理图片消息
                 */
                protected function imageHandler(){
                    $token = $this->getAccessToken();
                    $media_id = $this->xml_obj->MediaId;
                    $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$token.'&media_id='.$media_id;
                    $img = file_get_contents($url);
                    $media_path = 'upload/cat.jpg';
                    $res = file_put_contents($media_path,$img);
                    if($res)
                    {
                        // TODO 保存成功
                    }else{
                        // TODO 保存失败
                    }

                    //入库
                    $info = [
                        'media_id'  => $media_id,
                        'open_id'   => $this->xml_obj->FromUserName,
                        'msg_type'  => $this->xml_obj->MsgType,
                        'msg_id'  => $this->xml_obj->MsgId,
                        'create_time'  => $this->xml_obj->CreateTime,
                        'media_path'    => $media_path
                    ];
                    WxMediaModel::insertGetId($info);

                }

                public function  subscribe(){

                        $ToUserName=$this->xml_obj->FromUserName;       // openid
                        $FromUserName=$this->xml_obj->ToUserName;
                        //检查用户是否存在
                        $u = WxUserModel::where(['openid'=>$ToUserName])->first();
                        if($u)
                        {
                            // TODO 用户存在
                            $content = "欢迎回来 现在时间是：" . date("Y-m-d H:i:s");
                        }else{
                            //获取用户信息，并入库
                            $user_info = $this->getWxUserInfo();

                            //入库
                            unset($user_info['subscribe']);
                            unset($user_info['remark']);
                            unset($user_info['groupid']);
                            unset($user_info['substagid_listcribe']);
                            unset($user_info['qr_scene']);
                            unset($user_info['qr_scene_str']);
                            unset($user_info['tagid_list']);

                            WxUserModel::insertGetId($user_info);
                            $content = "欢迎关注 现在时间是：" . date("Y-m-d H:i:s");

                        }

                        $xml="<xml>
                              <ToUserName><![CDATA[".$ToUserName."]]></ToUserName>
                              <FromUserName><![CDATA[".$FromUserName."]]></FromUserName>
                              <CreateTime>time()</CreateTime>
                              <MsgType><![CDATA[text]]></MsgType>
                              <Content><![CDATA[".$content."]]></Content>
                       </xml>";

                        return $xml;
                    }
/**
     * 获取用户基本信息
     */
    public function getWxUserInfo()
    {

        $token = $this->getAccessToken();
        $openid = $this->xml_obj->FromUserName;
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$token.'&openid='.$openid.'&lang=zh_CN';

        //请求接口
        $client = new Client();
        $response = $client->request('GET',$url,[
            'verify'    => false
        ]);
        return  json_decode($response->getBody(),true);
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
                    "欢迎来到微信公众号",
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
            // 1 接收数据

                          // 记录日志
                          $log_str = date('Y-m-d H:i:s') . '>>>>>' . $postStr .  " \n\n";
                          file_put_contents('wx_event.log',$log_str,FILE_APPEND);

                          $obj = simplexml_load_string($xml_str);//将文件转换成 对象

                                  //$this->xml_obj = $obj;
                                  $msg_type = $obj->MsgType;      //推送事件的消息类型
                                  switch($msg_type){
                                      case 'event' :
                                          if($obj->Event=='subscribe')        // subscribe 扫码关注
                                          {
                                              echo $this->subscribe();
                                              exit;
                                          }elseif($obj->Event=='unsubscribe')     // // unsubscribe 取消关注
                                          {
                                              echo "";
                                              exit;
                                          }elseif ($obj->Event=='CLICK')          // 菜单点击事件
                                          {
                                              $this->clickHandler();
                                              // TODO
                                          }elseif($obj->Event=='VIEW')            // 菜单 view点击 事件
                                          {
                                              // TODO
                                          }
                                          break;

                                      case 'text' :           //处理文本信息
                                          $this->textHandler();
                                          break;

                                      case 'image' :          // 处理图片信息
                                          $this->imageHandler();
                                          break;

                                      default:
                                          echo 'default';
                                  }

                                  echo "";
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
                        "name"=> "签到和天气",
                         "sub_button"=> [
                             [
                              'type'  => 'view',
                              'name'  => '签到',
                              'url'   => 'http://2004shop.yangwenlong.top/test/qiandao'
                             ],
                             [
                              "type"=> "view",
                              "name"=> "查看天气",
                              "url"=> "http://2004shop.yangwenlong.top/test/tianqi"
                             ],
                          ]
                        ],
                        [
                        "name"=> "拍照功能",
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
