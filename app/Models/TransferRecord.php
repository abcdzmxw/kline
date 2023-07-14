<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferRecord  extends Model
{
    //划转记录

    protected $primaryKey = 'id';
    protected $table = 'user_transfer_record';
    protected $guarded = [];

    protected $casts = [
        'amount' => 'real',
    ];

    public static $statusMap = [
        1=>'划转成功',
        2=>'划转失败',
    ];

    public static $accountMap = [
        'UserWallet' => '账户资产',
        'ContractAccount' => '合约账户',
        'OtcAccount' => '法币账户',
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

}
