<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionBetCoin extends Model
{
    //可用于期权交易币种列表

    protected $table = 'option_bet_coin';
    protected $primaryKey = 'id';
    protected $guarded = [];
}
