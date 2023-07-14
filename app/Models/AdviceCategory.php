<?php


namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Dcat\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdviceCategory extends Model
{

    use ModelTree,Translatable;

    public $translationModel = adviceCategoryTranslations::class;
    public $translationForeignKey = 'category_id';
    public $translatedAttributes = ['name'];

    protected $table = 'advices_category';



    protected $primaryKey = 'id';

    protected $guarded = [];


/*    // 排序字段名称，默认值为 order
    protected $orderColumn = 'order';*/



}
