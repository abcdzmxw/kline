<?php

namespace App\Models\Mongodb;

use Jenssegers\Mongodb\Eloquent\Model;

class USDTTransactions extends Model
{
    protected $connection = 'mongodb';          //库名
    protected $collection = 'erc20usdt_transactions';     //文档名
    protected $primaryKey = '_id';               //设置id
    protected $guarded = [];
}
