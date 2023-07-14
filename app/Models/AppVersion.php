<?php
/*
 * @Author: your name
 * @Date: 2021-05-22 11:42:46
 * @LastEditTime: 2021-06-05 16:01:34
 * @LastEditors: your name
 * @Description: In User Settings Edit
 * @FilePath: \server\app\Models\AppVersion.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    protected $table = 'app_version';

    protected $primaryKey = 'id';

    protected $guarded = [];

    const client_type_android = 1;
    const client_type_ios = 2;

    public static function getNewestVersion($type = self::client_type_android)
    {
        return self::query()
            ->where(['client_type' => $type])
            ->select('version', 'is_must', 'update_log', 'url', 'updated_at')
            ->latest()->first();
    }
}
