<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCoinName extends Model
{
    //充币

    protected $primaryKey = 'id';
    protected $table = 'user_coin_name';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

}
