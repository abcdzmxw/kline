<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusLog extends Model
{
    //

    protected $primaryKey = 'id';
    protected $table = 'bonus_logs';
    protected $guarded = [];

    protected $attributes = [
        'status' => 1,
    ];

    const status_cancel = -1;
    const status_wait = 1;
    const status_hand = 2;

    public static $statusMap = [
        self::status_cancel => '已关闭',
        self::status_wait => '待发放',
        self::status_hand => '已发放',
    ];

    public function bonusable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

}
