<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractDealRobot extends Model
{
    // 合约交易自动成交机器人

    protected $table = 'contract_deal_robot';
    protected $primaryKey = 'id';
    protected $guarded = [];

    protected $casts = [
        'bid_plus_unit' => 'real',
        'bid_minus_unit' => 'real',
        'ask_plus_unit' => 'real',
        'ask_minus_unit' => 'real',
    ];

    public function contract()
    {
        return $this->belongsTo(ContractPair::class,'contract_id','id');
    }
}
