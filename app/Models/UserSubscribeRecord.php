<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class UserSubscribeRecord extends Model
{

    protected $table = 'user_subscribe_record';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

}
