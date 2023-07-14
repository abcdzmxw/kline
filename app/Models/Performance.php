<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Performance extends Model
{
    protected $table = 'performance';
    protected $guarded = [];

    const status_wait_settle = 1;
    const status_settled = 2;
    public static $statusMap = [
        self::status_wait_settle => '结算中',
        self::status_settled => '已结算',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class,'aid','id');
    }

}
