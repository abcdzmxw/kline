<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class PledgeProduct extends Model
{
    use SoftDeletes;
    // 币币交易交易对

    protected $table = 'pledge_product';
    protected $primaryKey = 'id';
    protected $guarded = [];

    //配置软删除属性
    protected $dates = ['deleted_at'];

    //状态
//    const status_freeze = 0;
//    const status_normal = 1;
//    public static $statusMap = [
//        self::status_freeze => '关闭',
//        self::status_normal => '开启',
//    ];

}
