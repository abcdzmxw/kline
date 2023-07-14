<?php

namespace App\Models;

use App\Events\TriggerEntrustEvent;
use Illuminate\Database\Eloquent\Model;

class InsideTradeOrder extends Model
{
    // 币币交易成交记录

    protected $table = 'inside_trade_order';
    protected $primaryKey = 'order_id';
    protected $guarded = [];

    protected $casts = [
        'unit_price' => 'real',
        'trade_amount' => 'real',
        'trade_money' => 'real',
        'trade_buy_fee' => 'real',
        'trade_sell_fee' => 'real',
    ];

    /**
     * 模型的事件映射
     * 触发止盈止损委托
     * @var array
     */
//    protected $dispatchesEvents = [
//        'created' => TriggerEntrustEvent::class,
//    ];

    public function buy_user()
    {
        return $this->belongsTo(User::class,'buy_user_id','user_id');
    }

    public function sell_user()
    {
        return $this->belongsTo(User::class,'sell_user_id','user_id');
    }

}
