<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAuth extends Model
{
    //用户认证

    protected $primaryKey = 'id';
    protected $table = 'user_auth';
    protected $guarded = [];

    protected $appends = ['primary_status_text','status_text'];

    protected $attributes = [
        'status' => 0,
    ];

    const STATUS_UNAUTH = 0;
    const STATUS_WAIT = 1;
    const STATUS_AUTH = 2;
    const STATUS_REJECT = 3;

    public static $statusMap = [
        self::STATUS_UNAUTH => '高级未认证',
        self::STATUS_WAIT => '待审核',
        self::STATUS_AUTH => '已通过',
        self::STATUS_REJECT => '已驳回',
    ];

    public static $primaryStatusMap = [
        0 => '未认证',
        1 => '待审核',
        2 => '已通过',
        3 => '已驳回',
    ];

    public function getStatusTextAttribute()
    {
        return __(self::$statusMap[$this->status]);
    }

    public function getPrimaryStatusTextAttribute()
    {
        return __(self::$primaryStatusMap[$this->primary_status]);
    }

    public function getFrontImgAttribute($value)
    {
        return getFullPath($value);
    }

    public function getBackImgAttribute($value)
    {
        return getFullPath($value);
    }

    public function getHandImgAttribute($value)
    {
        return getFullPath($value);
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id','user_id');
    }

}
