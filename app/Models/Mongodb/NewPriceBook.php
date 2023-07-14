<?php

namespace App\Models\Mongodb;

use Jenssegers\Mongodb\Eloquent\Model;

class NewPriceBook extends Model
{
    // 最新价格集合

    protected $connection = 'mongodb';          //库名
    protected $collection = 'newPriceBook';     //文档名
    protected $primaryKey = 'id';               //设置id
    protected $guarded = [];

//    public $timestamps = false;

}
