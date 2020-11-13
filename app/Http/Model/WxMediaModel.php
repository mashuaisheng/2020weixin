<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class WxMediaModel extends Model
{
    public $table = 'wx_media';  //声明model使用的表
    protected $primaryKey ='id';  //声明表的主键
    public $timestamps = false; //时间戳
    protected $guarded = [];//黑名单
}
