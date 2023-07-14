<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model
{
    //提币

    protected $primaryKey = 'id';
    protected $table = 'user_wallet_withdraw';
    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'real',
        'amount' => 'real',
        'withdrawal_fee' => 'real',
         'exchange_rate' => 'real',
        'net_receipts' => 'real',
    ];

    public $appends = ['status_text'];

    //状态
    const status_wait = 0;//待审核
    const status_pass = 1;//审核通过
    const status_reject = 2;//审核拒绝
    const status_success = 3;//交易成功
    const status_failed = 4;//交易失败
    const status_canceled = 9;//撤销
    public static $statusMap = [
        self::status_wait => '待审核',
        self::status_pass => '审核通过',
        self::status_reject => '审核拒绝',
        self::status_success => '交易成功',
        self::status_failed => '交易失败',
        self::status_canceled => '撤销',
    ];

    public function getStatusTextAttribute()
    {
        return __(self::$statusMap[$this->status]);
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
