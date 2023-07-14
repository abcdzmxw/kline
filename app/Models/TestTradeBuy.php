<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestTradeBuy extends Model
{
    //
    protected $table = 'test_trade_buy';
    protected $primaryKey = 'id';
    protected $guarded = [];

    protected $casts = [
        'entrust_price' => 'real',
        'trigger_price' => 'real',
        'amount' => 'real',
        'traded_amount' => 'real',
        'traded_money' => 'real',
        'money' => 'real',
    ];

    protected $attributes = [
        'amount' => null,
        'traded_amount' => 0,
        'money' => 0,
        'traded_money' => 0,
        'entrust_type' => 1,
    ];

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

    public $appends = ['surplus_amount'];

    public function getSurplusAmountAttribute()
    {
        if($this->type == 1){
            return ($surplus_amount = $this->amount - $this->traded_amount) < 0 ? 0 : $surplus_amount;
        }else{
            return null;
        }
    }

    public function can_trade()
    {
        if( $this->status == self::status_wait || $this->status == self::status_trading ){
            return true;
        }
        return false;
    }

    public static function getBuyTradeList($type,array $where_data)
    {
        $where = [
            ['base_coin_id','=',$where_data['base_coin_id']],
            ['quote_coin_id','=',$where_data['quote_coin_id']],
        ];
        if($type == 1 || $type == 3){
            //限价交易 市价挂单优先 然后取符合条件的按价格排序
            return self::query()->where($where)
                ->where('entrust_price','>=',$where_data['entrust_price'])
                ->whereIn('status',[1,2])
                ->orderBy('entrust_price','desc')->orderBy('created_at')->get();
        }else{
            return [];
        }
    }

}
