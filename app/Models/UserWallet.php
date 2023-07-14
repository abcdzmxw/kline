<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWallet extends Model
{
    //

    protected $primaryKey = 'wallet_id';
    protected $table = 'user_wallet';
    protected $guarded = [];

    protected $casts = [
        'usable_balance' => 'real',
        'freeze_balance' => 'real',
    ];

    protected $attributes = [
        'usable_balance' => 0,
        'freeze_balance' => 0,
    ];

    public static $richMap = [
        'usable_balance' => '可用余额',
        'freeze_balance' => '冻结余额',
    ];

    const asset_account = 1;
    const sustainable_account = 2;
    const otc_account = 3;
    public static $accountMap = [
        ['id' => self::asset_account, 'name'=>'账户资产', 'account'=>'UserWallet', 'is_need_pair'=> 0 , 'pair_key' => '' , 'model'=> UserWallet::class],
        ['id' => self::sustainable_account, 'name'=>'合约账户', 'account'=>'ContractAccount', 'is_need_pair'=> 0 , 'pair_key' => 'contract_id' ,'model'=> SustainableAccount::class],
    //    ['id' => self::otc_account, 'name'=>'法币账户', 'account'=>'OtcAccount', 'is_need_pair'=> 0 , 'pair_key' => '' ,'model'=> OtcAccount::class],
    ];
    public static $accountOptions = [
        UserWallet::asset_account => '账户资产',
        UserWallet::sustainable_account => '合约账户',
     //   UserWallet::otc_account => '法币账户',
    ];

    public function getRichMap()
    {
        return self::$richMap;
    }

    public function coin()
    {
        return $this->belongsTo(Coins::class,'coin_id','coin_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

}
