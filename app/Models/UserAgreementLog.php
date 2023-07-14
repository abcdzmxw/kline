<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAgreementLog extends Model
{
    //

    protected $table = 'user_agreement_logs';
    protected $primaryKey = 'id';
    protected $guarded = [];

    public $timestamps = false;

    public static $typeMap = [
        'contract' => '永续合约',
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

}
