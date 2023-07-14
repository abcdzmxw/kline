<?php

namespace App\Models;

use App\Events\ExchangeBuyEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InsideTradeBuy extends Model
{
    // 币币交易买单委托

    protected $table = 'inside_trade_buy';
    protected $primaryKey = 'id';
    protected $guarded = [];

    protected $casts = [
        'entrust_price' => 'float',
        'trigger_price' => 'real',
        'amount' => 'real',
        'traded_amount' => 'real',
        'traded_money' => 'real',
        'money' => 'real',
    ];

    /**
     * 模型的事件映射
     * 触发止盈止损委托
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => ExchangeBuyEvent::class,
    ];

    protected $attributes = [
        'amount' => null,
        'traded_amount' => 0,
        'money' => 0,
        'traded_money' => 0,
        'entrust_type' => 1,
    ];

    public $appends = ['is_traded','surplus_amount','status_text','entrust_type_text'];

    const status_cancel = 0;
    const status_wait = 1;
    const status_trading = 2;
    const status_completed = 3;
    public static $statusMap = [
        self::status_cancel => '已撤单',
        self::status_wait => '未成交',
        self::status_trading => '部分成交',
        self::status_completed => '全部成交',
    ];

    public static $typeMap = [
        // 1限价交易 2市价交易 3止盈止损
        1 => '限价交易',
        2 => '市价交易',
        3 => '止盈止损',
    ];

    public function getStatusTextAttribute()
    {
        return __(self::$statusMap[$this->status]);
    }

    public function getEntrustTypeTextAttribute()
    {
        $map = [1 => '买入', 2 => '卖出'];
        return __($map[$this->entrust_type]);
    }

    public function getIsTradedAttribute()
    {
        return $this->traded_amount == 0 ? 0 : 1;
    }

    public function getSurplusAmountAttribute()
    {
        if($this->type == 1){
            return ($surplus_amount = $this->amount - $this->traded_amount) < 0 ? 0 : $surplus_amount;
        }else{
            return null;
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

    public function order_details()
    {
        return $this->hasMany(InsideTradeOrder::class,'buy_id','id');
    }

    public function can_trade()
    {
        if( $this->status == self::status_wait || $this->status == self::status_trading ){
            return true;
        }
        return false;
    }

    public function can_cancel()
    {
        if( $this->status == self::status_wait || $this->status == self::status_trading ){
            return true;
        }
        return false;
    }

    public static function getBuyTradeList($type,array $where_data)
    {
        return [];
        // $where = [
        //     ['base_coin_id','=',$where_data['base_coin_id']],
        //     ['quote_coin_id','=',$where_data['quote_coin_id']],
        //     ['user_id','!=',$where_data['user_id']],
        // ];
        // if($type == 1 || $type == 3){
        //     //限价交易 市价挂单优先 然后取符合条件的按价格排序
        //     $market_entrust = DB::table('inside_trade_buy')->where($where)
        //         ->whereIn('status',[InsideTradeBuy::status_wait,InsideTradeBuy::status_trading])
        //         ->where('type',2)->orderBy('created_at');
        //     return self::query()->where($where)
        //         ->whereIn('status',[InsideTradeBuy::status_wait,InsideTradeBuy::status_trading])
        //         ->where('entrust_price','>=',$where_data['entrust_price'])
        //         ->union($market_entrust)
        //         ->orderBy('entrust_price','desc')->orderBy('created_at')->get();
        // }else{
        //     //市价交易 则买单得有价格
        //     return [];
        //     // return self::query()->where($where)
        //     //     ->whereIn('status',[InsideTradeBuy::status_wait,InsideTradeBuy::status_trading])
        //     //     ->where('type',1)->orderBy('entrust_price','desc')->orderBy('created_at')->get();
        // }
    }

}
