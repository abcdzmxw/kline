<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class InsideTradePair extends Model
{
    // 币币交易交易对

    protected $table = 'inside_trade_pair';
    protected $primaryKey = 'pair_id';
    protected $guarded = [];

    public static function getCachedPairs()
    {
        return Cache::remember('pairs', 60, function () {
            return self::query()->where('status',1)->orderBy('sort','asc')->get()->groupBy('quote_coin_name')->toArray();
        });
    }

    public function can_store()
    {
        if($this->trade_status == 0){
            return '交易暂时关闭';
        }
        return true;
    }

}
