<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsideTradeRisk extends Model
{
    // 币币交易风控任务

    protected $table = 'inside_trade_risk';
    protected $primaryKey = 'id';
    protected $guarded = [];

    protected $casts = [
        'range' => 'real',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function setStartTimeAttribute($value)
    {
        $this->attributes['start_time'] = blank($value) ? null : strtotime($value);
    }

    public function setEndTimeAttribute($value)
    {
        $this->attributes['end_time'] = blank($value) ? null : strtotime($value);
    }

    public function pair()
    {
        return $this->belongsTo(InsideTradePair::class,'pair_id','pair_id');
    }

}
