<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class WithdrawalManagement extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'user_withdrawal_management';
    protected $guarded = [];
}
