<?php

namespace App\Models;

use App\Events\TestTradeOrderEvent;
use Illuminate\Database\Eloquent\Model;

class TestTradeOrder extends Model
{
    protected $table = 'test_trade_order';
    protected $primaryKey = 'order_id';
    protected $guarded = [];

    protected $attributes = [
        'trade_buy_fee' => 0,
        'trade_sell_fee' => 0,
    ];

    protected $casts = [
        'unit_price' => 'real',
        'trade_amount' => 'real',
        'trade_money' => 'real',
        'trade_buy_fee' => 'real',
        'trade_sell_fee' => 'real',
    ];

    /**
     * 模型的事件映射
     * @var array
     */
//    protected $dispatchesEvents = [
//        'created' => TestTradeOrderEvent::class,
//    ];

}
