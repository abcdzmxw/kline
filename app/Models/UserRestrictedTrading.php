<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRestrictedTrading extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'user_restricted_trading';
    protected $guarded = [];

}
