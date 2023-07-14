<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestTradeSell extends Model
{
    //
    protected $table = 'test_trade_sell';
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
        'traded_amount' => 0,
        'traded_money' => 0,
        'entrust_type' => 2,
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
        return ($surplus_amount = $this->amount - $this->traded_amount) < 0 ? 0 : $surplus_amount;
    }

    public function can_trade()
    {
        if( $this->status == self::status_wait || $this->status == self::status_trading ){
            return true;
        }
        return false;
    }

    public static function getSellTradeList($type,array $where_data)
    {
        $where = [
            ['base_coin_id','=',$where_data['base_coin_id']],
            ['quote_coin_id','=',$where_data['quote_coin_id']],
        ];
        if($type == 1 || $type == 3){
            //限价交易 市价挂单优先 然后取符合条件的按价格排序
            return self::query()->where($where)
                ->where('entrust_price','<=',$where_data['entrust_price'])
                ->whereIn('status',[1,2])
                ->orderBy('entrust_price','asc')->orderBy('created_at')->get();
        }else{
            return [];
        }
    }

}
