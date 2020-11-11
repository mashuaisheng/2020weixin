<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Redis;
class TestController extends Controller
{
    public function test1(){
    	$key='aaa_';
    	$list= Redis::incr($key);
    	dd($list);
    	// $res=DB::table('p_users')->limit(5)->get();
    	// dd($res);
    }
    //guzzle
    public function guzzle1()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('wx_APPID')."&secret=".env('wx_APPSEC');
        //使用guzzle发起get请求
        $client = new Client();//实例化 客户端
        $response = $client->request('GET',$url,['verify'=>false]);//发起请求并接受响应
        $json_str = $response->getBody();   //服务器的响应数据
        echo $json_str;
    }
    //上传素材
    public function guzzle2(){
        $access_token = "";
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

     public function qiandao(){
        return view('qiandao.index');
     }



}
