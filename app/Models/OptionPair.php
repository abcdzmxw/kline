<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionPair extends Model
{
    //期权交易对

    protected $table = 'option_pair';
    protected $primaryKey = 'pair_id';
    protected $guarded = [];

//    public $appends = ['coin_icon'];
//    public function getCoinIconAttribute()
//    {
//        $coin_icon = Coins::query()->where('coin_name',$this->base_coin_name)->value('coin_icon');
//        return getFullPath($coin_icon);
//    }

}
