<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractStrategy extends Model
{
    //合约策略(止盈止损)

    protected $table = 'contract_strategy';
    protected $primaryKey = 'id';
    protected $guarded = [];

    protected $casts = [
        'current_price' => 'real',
        'tp_trigger_price' => 'real',
        'sl_trigger_price' => 'real',
    ];

    protected $attributes = [
        'status' => 1,
    ];

}
