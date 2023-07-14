<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class UserWalletLog extends Model
{
    //

    protected $primaryKey = 'id';
    protected $table = 'user_wallet_logs';
    protected $guarded = [];

    protected $appends = ['log_type_text','coin_name'];

    protected $casts = [
        'before_balance' => 'real',
        'after_balance' => 'real',
        'amount' => 'real',
    ];

//    protected $dateFormat = 'U';

    public static $logType = [
        'store_otc_sell_entrust' => "发布OTC出售广告委托",
        'cancelOtcEntrust' => "撤销OTC委托",
        'confirmOtcOrder' => "OTC订单交易完成",
        'store_otc_order' => "我要出售",
        'cancel_otc_order' => "订单关闭",

        'register_send_stai' => "注册赠送",
        'admin_recharge' => "后台充值",
        'recharge' => "充币",
        'recharge_audit' => "充币审核",
        'withdraw' => "提币",
        'cancel_withdraw' => "撤销提币",
        'withdraw_fee' => "提币手续费",
        'reject_withdraw' => "提币审核拒绝",
        'fund_transfer' => "资金划转",
        "bet_option" => "买入期权",
        "bet_option_fee" => "期权手续费",
        "option_order_delivery" => "期权交割",
        "option_order_cancel" => "期权订单流局",
        "store_buy_entrust" => "发布币币交易买入委托",
        "store_sell_entrust" => "发布币币交易卖出委托",
        "entrust_exchange" => "币币交易成交",
        "cancel_entrust" => "撤单",
        "dividend" => "分红",
        "subscribe" => "申购",
        "subscribe_activity" => "申购活动赠送",
        "open_position" => "合约开仓冻结",
        "open_position_fee" => "合约开仓手续费",
        "contract_deal" => "合约成交",
        "close_position" => "合约平仓保证金释放",
        "close_position_fee" => "合约平仓手续费",
        "close_long_position" => "合约平多盈亏结算",
        "close_short_position" => "合约平空盈亏结算",
        "system_flat_position" => "系统强平",
        "system_close_position" => "爆仓保证金释放",
        "system_close_position_fee" => "爆仓手续费",
        "system_close_long_position" => "爆仓平多盈亏结算",
        "system_close_short_position" => "爆仓平空盈亏结算",
        "cancel_open_position" => "撤销合约，保证金回退",
        "cancel_open_position_fee" => "撤销合约，手续费回退",
        "position_capital_cost" => "合约交易资金费",

        "buy_pledge_product" => "购买质押挖矿",
        "sell_pledge_product" => "解锁质押挖矿",
        "sell_pledge_product_special" => "解锁质押挖矿",
        "buy_pledge_product_reward" => "购买质押挖矿奖励",
        "dividendPledgePromotion" => "质押挖矿推广奖励",
        "pledge_mining_interest_daily_return" => "质押挖矿利息日返",

        "flash_exchange"    => '闪兑数量',
        "flash_exchange_fee"    => '闪兑手续费',
    ];


    public static function getLogadminTypeText($log_type)
    {
        return isset(self::$logType[$log_type]) ? self::$logType[$log_type] : '';
    }
    // 转换语言包
    public static function getLogTypeText($log_type)
    {
        return isset(self::$logType[$log_type]) ? __(self::$logType[$log_type]) : '';
    }

    public function getLogTypeTextAttribute()
    {
        return isset(self::$logType[$this->log_type]) ? __(self::$logType[$this->log_type]) : '';
    }

    public function getCoinNameAttribute()
    {
        $coin_name = Coins::query()->where('coin_id',$this->coin_id)->value('coin_name');
        return blank($coin_name) ? '' : $coin_name;
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::createFromTimestamp(strtotime($value))
            ->timezone(config('app.timezone'))
            ->toDateTimeString();
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::createFromTimestamp(strtotime($value))
            ->timezone(config('app.timezone'))
            ->toDateTimeString();
    }

}
