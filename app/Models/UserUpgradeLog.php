<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserUpgradeLog extends Model
{
    // 用户升级日志

    protected $primaryKey = 'id';
    protected $table = 'user_upgrade_logs';
    protected $guarded = [];

}
