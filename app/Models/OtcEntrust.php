<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtcEntrust extends Model
{
    //

    protected $table = 'otc_entrust';
    protected $primaryKey = 'id';
    protected $guarded = [];

    public $casts = [
        'price' => 'real',
        'amount' => 'real',
        'cur_amount' => 'real',
        'lock_amount' => 'real',
        'min_num' => 'real',
        'max_num' => 'real',
        'deal_rate' => 'real',
    ];

    public $appends = ['status_text'];

    const status_canceled = 0;
    const status_normal = 1;
    const status_completed = 2;
    public static $statusMap = [
        self::status_canceled => '已撤销',
        self::status_normal => '正常',
        self::status_completed => '已完成',
    ];

    public function getPayTypeAttribute($v)
    {
        return blank($v) ? [] : explode(',',$v);
    }

    public function getStatusTextAttribute()
    {
        return self::$statusMap[$this->status];
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

    public function orders()
    {
        return $this->hasMany(OtcOrder::class,'entrust_id','id');
    }

    public function canCancel()
    {
        if ($this->status != self::status_normal) {
            return false;
        }

        // 买卖交易还有未交易完的
        if ($this->orders->count() > 0) {
            $wait_trade = $this->orders()->where('entrust_id',$this->id)
                ->where(function ($query){
                    $query->where('status',OtcOrder::status_wait_pay)
                        ->orWhere(function($query){
                            $query->where('status', OtcOrder::status_wait_confirm);
                        });
                })->get();
            if ($wait_trade->count() > 0) {
                return false;
            }
        }

        return true;
    }

}
