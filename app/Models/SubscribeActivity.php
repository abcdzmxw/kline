<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class SubscribeActivity extends Model
{
    protected $table = 'subscribe_activity';
    protected $guarded = [];

    protected $casts = [
        'params' => 'array'
    ];
}
