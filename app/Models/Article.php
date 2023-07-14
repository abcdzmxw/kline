<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Article extends Model
{
    //文章表
    use SoftDeletes,Translatable;

    public $translationModel = ArticleTranslation::class;
    public $translationForeignKey = 'article_id';
    public $translatedAttributes = ['title', 'body','excerpt'];

    protected $table = 'articles';

    protected $primaryKey = 'id';

    protected $guarded = [];

//    protected $hidden = ['translations'];

    //配置软删除属性
    protected $dates = ['deleted_at'];

    protected $appends = ['category_name','full_cover'];

    public function getCategoryNameAttribute()
    {
       $cat =  ArticleCategory::query()->where('id',$this->category_id)->first();
       return blank($cat) ? '':$cat->name;
    }

    // 定义一个public方法访问图片或文件
    public function getFullCoverAttribute()
    {
        return getFullPath($this->cover,'admin');
    }

    public function category()
    {
        return $this->belongsTo(ArticleCategory::class,'category_id','id');
    }
}
