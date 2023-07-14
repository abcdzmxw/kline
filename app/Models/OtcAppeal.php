<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtcAppeal extends Model
{
    //

    protected $table = 'otc_appeal';
    protected $primaryKey = 'id';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

}
