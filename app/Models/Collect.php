<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Collect extends Model
{
    protected  $table="collect";
    protected $fillable = [
        "user_id","pair_id","pair_name"
    ];

}
