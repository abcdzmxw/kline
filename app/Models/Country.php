<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class Country extends Model
{
    //国家

    protected $table = 'country';
    protected $primaryKey = 'id';
    protected $guarded = [];

    public static function getCachedCountry()
    {
        return Cache::remember('country', 600, function () {
            return self::query()->orderBy('order','desc')->get()->toArray();
        });
    }

    public static function getForeverCachedCountry()
    {
        $app_locale = App::getLocale();
        if($app_locale == 'zh-CN' || $app_locale == 'zh-TW'){
            return Cache::rememberForever('foreverCountry', function () {
                return self::query()->orderBy('order','desc')->select(['id','code','name','country_code'])->get()->toArray();
            });
        }else{
            return Cache::rememberForever('enForeverCountry', function () {
                return self::query()->orderBy('order','desc')->select(['id','code','en_name as name','country_code'])->get()->toArray();
            });
        }
    }

}
