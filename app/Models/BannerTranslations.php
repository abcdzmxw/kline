<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class BannerTranslations extends  Model
{

    protected $table = 'banner_translations';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['locale','imgurl'];

//    public function getImgurlAttribute($value)
//    {
//        if (strpos($value,'http') !== false){
//            return $value;
//        }
//        return getFullPath($value);
//    }

}
