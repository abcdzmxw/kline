<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsideTradeDealRobot extends Model
{
    // 场内交易自动成交机器人

    protected $table = 'inside_trade_deal_robot';
    protected $primaryKey = 'id';
    protected $guarded = [];

    protected $casts = [
        'bid_plus_unit' => 'real',
        'bid_minus_unit' => 'real',
        'ask_plus_unit' => 'real',
        'ask_minus_unit' => 'real',
    ];

    public function pair()
    {
        return $this->belongsTo(InsideTradePair::class,'pair_id','pair_id');
    }

}
