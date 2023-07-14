<?php

namespace App\Models\Mongodb;

use Jenssegers\Mongodb\Eloquent\Model;

class UdunTrade extends Model
{
    // 优盾交易回调记录

    protected $connection = 'mongodb';          //库名
    protected $collection = 'udun_trade';     //文档名
    protected $primaryKey = '_id';               //设置id
    protected $guarded = [];
}
