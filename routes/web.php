<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
//Route::get('/test','TestController@test1');

Route::prefix('/test')->group(function(){
    Route::get('/guzzle1','TestController@guzzle1');
    Route::get('/guzzle2','IndexController@guzzle2');
    Route::get('/guzzle3','TestController@guzzle3');
});


Route::prefix('/wxs')->group(function(){
    //Route::any('/','WxController@wxEvent');  //微信接入 接受时间推送
   // Route::any('/index','WxController@index');  //微信接入
    //Route::get('/token','WxController@getAccessToken');        //获取access_token
});

Route::any('/index','WxController@index');
Route::get('/token','WxController@token');
Route::any('/wxx','WxController@checkSignature');
Route::any('/wx','WxController@wx');

