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
Route::any('/wx','IndexController@wxEvent');  //微信接入 接受时间推送
Route::get('/wx/token','IndexController@getAccessToken');        //获取access_token
