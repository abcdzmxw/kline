<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class FlashExchangeConfig extends Model
{
    // 闪兑记录

    protected $primaryKey = 'id';
    protected $table = 'flash_exchange_config';
    protected $guarded = [];
}
