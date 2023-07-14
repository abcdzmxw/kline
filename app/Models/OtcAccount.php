<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtcAccount extends Model
{
    //

    protected $table = 'otc_account';
    protected $primaryKey = 'id';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

}
