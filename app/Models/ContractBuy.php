<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/9
 * Time: 16:15
 */

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ContractBuy extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'contract_buy';
    protected $guarded = [];


    public $appends = ['surplus_amount','status_text','entrust_type_text'];

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

    public function getStatusTextAttribute()
    {
        return self::$statusMap[$this->status];
    }

    public function getEntrustTypeTextAttribute()
    {
        $map = [1 => '买入', 2 => '卖出'];
        return $map[$this->entrust_type];
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
        $where = [
            ['base_coin_id','=',$where_data['base_coin_id']],
            ['quote_coin_id','=',$where_data['quote_coin_id']],
            ['user_id','!=',$where_data['user_id']],
        ];
        if($type == 1){
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
