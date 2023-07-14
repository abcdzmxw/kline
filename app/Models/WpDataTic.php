<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class WpDataTic extends Model{
    public $timestamps = false;
    protected $table = 'data_stai';
    protected $fillable = [
        'pid','Symbol','Date','datetime','Name','Open','High','Low','Close','LastClose','Price2','Price3','Open_Int',
        'Volume','Amount','is_1min','is_5min','is_15min','is_30min','is_1h','is_2h','is_4h','is_6h','is_12h','is_day','is_week'
    ];

    protected $hidden = ['id'];


}
