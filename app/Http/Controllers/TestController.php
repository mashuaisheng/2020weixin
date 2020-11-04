<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
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
}
