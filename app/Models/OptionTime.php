<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionTime extends Model
{
    //期权场次

    protected $table = 'option_time';
    protected $primaryKey = 'time_id';
    protected $guarded = [];

    protected $casts = [
        'odds_up_range' => 'array',
        'odds_down_range' => 'array',
        'odds_draw_range' => 'array',
    ];

}
