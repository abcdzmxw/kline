<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class AdminSetting extends Model
{
    protected $table = 'admin_setting';
    protected $primaryKey = 'id';
    protected $guarded = [];

    public static $modules = [
        'website' => '网站配置',
        'exchange' => '币币交易',
        // 'otc' => '法币交易',
        'option' => '期权交易',
        'contract' => '合约交易',
        'common' => '通用',
        // 'paypal' => 'Paypal配置',
        // 'coin1' => 'COIN-BT',
        // 'coin2' => 'COIN-TKB',
    ];

    //获取配置
//    public static function getSettingByName($name)
//    {
//        return self::query()->where('name',$name)->first()->value;
//    }

//    public function getValueAttribute($v)
//    {
//        if ($v && $this->type === 'image'){
//            if (strpos($v,'http') === false) return getFullPath($v);
//        }
//        return  $v;
//    }
//
//    public function setValueAttribute($v)
//    {
//        if (strpos($v,env('IMG_URL')) !== false){
//            $v = str_replace(env('IMG_URL'),'',$v);
//        }
//        $this->attributes['value'] = $v;
//    }
}
