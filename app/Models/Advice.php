<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Advice extends Model
{
    //用户意见反馈
    protected $primaryKey = 'id';
    /*表名称*/
    protected $table = 'advices';
    protected $guarded = [];


    protected $appends = ['is_process_text'];

    protected $attributes = [
        'is_process' => 0,
    ];

    const STATUS_WAIT = 0;
    const STATUS_PROCESSED = 1;

    public static $statusMap = [
        self::STATUS_WAIT => '未处理',
        self::STATUS_PROCESSED => '已处理',
    ];
    public static $status = [
        "0" => '未处理',
        "1" => '已处理',
    ];

    public function user(){
        return $this->belongsTo(User::class,'user_id','user_id');
    }

    public function getIsProcessTextAttribute()
    {
        return self::$statusMap[$this->is_process];
    }

    public function getImgsAttribute($imgs)
    {
        $data = json_decode($imgs, true);
        if(is_array($data)){
            $data = array_map(function ($value){
                return getFullPath($value);
            },$data);
        }
        return $data;
    }
}
