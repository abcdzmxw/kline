<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recharge extends Model
{
    //充币

    protected $primaryKey = 'id';
    protected $table = 'user_wallet_recharge';
    protected $guarded = [];

    protected $attributes = [
        'type' => 1,
        'account_type' => 1,
    ];

    protected $casts = [
        'amount' => 'real',
    ];

    public static $typeMap = [
        1 => '在线',
        2 => '后台',
    ];

    //状态
    const status_wait = 0;//待审核
    const status_pass = 1;//审核通过
    const status_reject = 2;//审核拒绝
    public static $statusMap = [
        self::status_wait => '待审核',
        self::status_pass => '审核通过',
        self::status_reject => '审核拒绝',
    ];

    public static function getRechargeCoins()
    {
        return Coins::query()->where(['status'=>1,'can_recharge'=>1])->pluck('coin_name','coin_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

    public function user_auth()
    {
        return $this->belongsTo(UserAuth::class,'user_id','user_id');
    }

}
