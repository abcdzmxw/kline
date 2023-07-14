<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractWearPositionRecord extends Model
{
    //合约穿仓记录

    protected $table = 'contract_wear_position_record';
    protected $primaryKey = 'id';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

}
