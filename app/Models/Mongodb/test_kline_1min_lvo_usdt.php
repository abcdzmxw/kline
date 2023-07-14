<?php

namespace App\Models\Mongodb;

use Jenssegers\Mongodb\Eloquent\Model;

class test_kline_1min_lvo_usdt extends Model
{
    protected $connection = 'mongodb';          //库名
    protected $collection = 'test_kline_1min_lvo_usdt';     //文档名
    protected $primaryKey = '_id';               //设置id
    protected $guarded = [];

    public $timestamps = false;
}
