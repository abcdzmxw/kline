<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FakeKlineData extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'data_stai';
    protected $guarded = [];
    public $timestamps = false;

    public $attributes = [
        'pid' => 0,
        'Symbol' => 'LVO',
        'Name' => 'LVO',
        'Price2' => 0,
        'Price3' => 0,
        'Open_Int' => 0,
        'is_1min' => 0,
        'is_5min' => 0,
        'is_15min' => 0,
        'is_30min' => 0,
        'is_1h' => 0,
        'is_2h' => 0,
        'is_4h' => 0,
        'is_6h' => 0,
        'is_12h' => 0,
        'is_day' => 0,
        'is_week' => 0,
        'is_month' => 0,
    ];

}
