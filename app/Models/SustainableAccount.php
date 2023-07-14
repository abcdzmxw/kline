<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SustainableAccount extends Model
{
    //永续账户

    protected $primaryKey = 'id';
    protected $table = 'contract_account';
    protected $guarded = [];
    public $timestamps = false;

    protected $casts = [
        'usable_balance' => 'real',
        'used_balance' => 'real',
        'freeze_balance' => 'real',
    ];

    protected $attributes = [
        'usable_balance' => 0,
        'used_balance' => 0,
        'freeze_balance' => 0,
    ];

    public static $richMap = [
        'usable_balance' => '可用保证金',
        'used_balance' => '已用保证金',
        'freeze_balance' => '冻结保证金',
    ];

    public function getRichMap()
    {
        return self::$richMap;
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

    public static function getContractAccount($user_id,$params = [])
    {
        return self::query()->where('user_id',$user_id)->first();
    }

}
