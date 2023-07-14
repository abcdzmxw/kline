<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class FlashExchange extends Model
{
    // 闪兑记录

    protected $primaryKey = 'id';
    protected $table = 'flash_exchange';
    protected $guarded = [];
}
