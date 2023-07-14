<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/10
 * Time: 9:38
 */

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class ContractSell extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'contract_sell';
    protected $guarded = [];

    protected $attributes = [
        'traded_amount' => 0,
        'traded_money' => 0,
        'entrust_type' => 2,
    ];

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
        return ($surplus_amount = $this->amount - $this->traded_amount) < 0 ? 0 : $surplus_amount;
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

    public static function getSellTradeList($type,array $where_data)
    {
        $where = [
            ['base_coin_id','=',$where_data['base_coin_id']],
            ['quote_coin_id','=',$where_data['quote_coin_id']],
            ['user_id','!=',$where_data['user_id']],
        ];
        if($type == 1){
            //限价交易 市价挂单优先 然后取符合条件的按价格排序
            $market_entrust = DB::table('inside_list_sell')->where($where)->where('type',2)->orderBy('created_at');
            return self::query()->where($where)
                ->where('entrust_price','<=',$where_data['entrust_price'])
                ->union($market_entrust)
                ->orderBy('entrust_price','asc')->orderBy('created_at')->get();
        }else{
            //市价交易 则卖单得有价格
            return self::query()->where($where)->where('type',1)->orderBy('entrust_price','asc')->orderBy('created_at')->get();
        }
    }

}
