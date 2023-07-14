<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardLevel extends Model
{
    // 奖励级别

    protected $table = 'reward_level';
    protected $primaryKey = 'id';
    protected $guarded = [];

    //状态
    const status_freeze = 0;
    const status_normal = 1;
    public static $statusMap = [
        self::status_freeze => '无效',
        self::status_normal => '有效',
    ];

//    public function PledgeProduct()
//    {
//        return $this->belongsTo('Models\PledgeProduct','product_id');
//    }
}
