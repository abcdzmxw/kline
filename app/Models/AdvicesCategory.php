<?php

namespace App\Models;


use Astrotomic\Translatable\Translatable;
use Dcat\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;


class AdvicesCategory extends Model
{
    use ModelTree,Translatable;

    public $translationModel = adviceCategoryTranslations::class;
    public $translationForeignKey = 'category_id';
    public $translatedAttributes = ['name'];
    protected $table = 'advices_category';
    public $timestamps = false;
}
