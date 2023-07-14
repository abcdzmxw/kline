<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    // 平台收款账户

    protected $primaryKey = 'id';
    protected $table = 'payments';
    protected $guarded = [];

    protected $attributes = [
        'currency' => 'CNY',
        'pay_type' => 'bank_card',
        'status' => 1,
    ];

    protected $appends = ['pay_type_text'];

    const PAY_TYPE_BANK = 'bank_card';
    const PAY_TYPE_ALIPAY = 'alipay';
    const PAY_TYPE_WECHAT = 'wechat';

    public static $payTypeMap = [
        self::PAY_TYPE_BANK => '银行卡',
//        self::PAY_TYPE_ALIPAY => '支付宝',
//        self::PAY_TYPE_WECHAT => '微信',
    ];

    public static $currencyMap = [
        'CNY' => '人民币',
        'USD' => '美金',
    ];

    public function getPayTypeTextAttribute()
    {
        return self::$payTypeMap[$this->pay_type];
    }

    public static function getRechargePayments()
    {
        return self::query()->where('status',1)->get();
    }

}
