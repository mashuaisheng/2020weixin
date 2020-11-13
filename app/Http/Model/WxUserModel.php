<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserModel extends Model
{
    public $table = 'wx_users';  //声明model使用的表
    protected $primaryKey ='user_id';  //声明表的主键
    public $timestamps = false; //时间戳
    protected $guarded = [];//黑名单
}
