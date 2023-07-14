<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class UserLoginLog extends Model
{
    //登陆日志

    protected $primaryKey = 'id';
    protected $table = 'user_login_logs';
    protected $guarded = [];
    public $timestamps = false;

    public $appends = ['login_time_text'];

    public function getLoginTimeTextAttribute()
    {
        return Carbon::createFromTimestamp($this->login_time)->toDateTimeString();
    }

}
