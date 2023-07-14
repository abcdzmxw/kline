<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Translate extends Model
{
    //

    protected $primaryKey = 'id';
    protected $table = 'translate';
    protected $guarded = [];

    protected $casts = [
        'json_content' => 'json',
//        'json_content' => 'array',
    ];

    public static function getTranslate($lang = 'en')
    {
        $data = self::query()->where('lang',$lang)->select('json_content','file')->first();
        $data['file'] = getFullPath($data['file']);
        return $data;
    }

}
