<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Coins extends Model
{
    //
    protected $table = 'coins';
    protected $primaryKey = 'coin_id';
    protected $guarded = [];

    protected $casts = [
        'withdrawal_fee' => 'real',
        'withdrawal_min' => 'real',
        'withdrawal_max' => 'real',
    ];

    // 上传图片问题 注释后解决
    /*public function getCoinIconAttribute($value)
    {
        return getFullPath($value);
    }*/

    public static function icon($symbol)
    {
        return self::query()->where('coin_name',$symbol)->value('coin_icon');
    }

    public static function getCachedCoins()
    {
        return Cache::remember('coins', 60, function () {
            return self::query()->where('status',1)->get()->toArray();
        });
    }

    public static function getCachedCoinOption()
    {
        return Cache::remember('coinOption', 60, function () {
            return self::query()->where('status',1)->pluck('coin_name','coin_id')->toArray();
        });
    }

    public static function is_recharge($coin_id)
    {
        return self::query()->where('coin_id',$coin_id)->value('is_recharge');
    }

}
