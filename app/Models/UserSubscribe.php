<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class UserSubscribe extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'user_subscribe';
    protected $guarded = [];
    public $timestamps = false;

}
