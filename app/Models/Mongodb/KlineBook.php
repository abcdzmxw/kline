<?php

namespace App\Models\Mongodb;

use Jenssegers\Mongodb\Eloquent\Model;

class KlineBook extends Model
{
    // Kline集合

    protected $connection = 'mongodb';          //库名
    protected $collection = 'klineBook';     //文档名
    protected $primaryKey = '_id';               //设置id
    protected $guarded = [];

//    public $timestamps = false;
}
