<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPayment extends Model
{
    //用户收款账号

    protected $primaryKey = 'id';
    protected $table = 'user_payments';
    protected $guarded = [];

    protected $appends = ['pay_type_text'];

    const PAY_TYPE_BANK = 'bank_card';
    const PAY_TYPE_ALIPAY = 'alipay';
    const PAY_TYPE_WECHAT = 'wechat';

    public static $payTypeMap = [
        self::PAY_TYPE_BANK => '银行卡',
        self::PAY_TYPE_ALIPAY => '支付宝',
        self::PAY_TYPE_WECHAT => '微信',
    ];

    public function getPayTypeTextAttribute()
    {
        return self::$payTypeMap[$this->pay_type];
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','user_id');
    }

}
