<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/10
 * Time: 16:43
 */

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ContractPair extends Model
{
    #合约交易对
    protected $primaryKey = 'id';
    protected $table = 'contract_pair';
    protected $guarded = [];

    protected $attributes = [
        'margin_coin_id' => 1,
    ];

    protected $casts = [
        'lever_rage' => 'array',
    ];

//    public function getLeverRageAttribute($v)
//    {
//        dd(json_decode(json_decode($v,true)),true);
//        return blank($v) ? [] : json_decode($v,true);
//    }

    public function can_store()
    {
        if($this->trade_status == 0){
            return '交易暂时关闭';
        }
        return true;
    }

    public function coin()
    {
        return $this->belongsTo(Coins::class,'contract_coin_id','coin_id');
    }

}
