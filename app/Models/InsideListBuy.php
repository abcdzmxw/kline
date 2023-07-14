<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InsideListBuy extends Model
{
    // 币币交易买单挂单盘口

    protected $table = 'inside_list_buy';
    protected $primaryKey = 'id';
    protected $guarded = [];

    protected $casts = [
        'entrust_price' => 'real',
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

    public static function getBuyTradeList($type,array $where_data)
    {
        $where = [
            ['base_coin_id','=',$where_data['base_coin_id']],
            ['quote_coin_id','=',$where_data['quote_coin_id']],
//            ['user_id','!=',$where_data['user_id']],
        ];
        if($type == 1 || $type == 3){
            //限价交易 市价挂单优先 然后取符合条件的按价格排序
            $market_entrust = DB::table('inside_list_buy')->where($where)->where('type',2)->orderBy('created_at');
            return self::query()->where($where)
                ->where('entrust_price','>=',$where_data['entrust_price'])
                ->union($market_entrust)
                ->orderBy('entrust_price','desc')->orderBy('created_at')->get();
        }else{
            //市价交易 则买单得有价格
            return self::query()->where($where)->where('type',1)->orderBy('entrust_price','desc')->orderBy('created_at')->get();
        }
    }

}
