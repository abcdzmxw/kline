<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class ContractShare extends Model
{

    protected $table = 'contract_share';
    protected $guarded = [];
    protected $dateFormat = 'U';

    protected $casts = [
        'data' => 'array',
    ];

}
