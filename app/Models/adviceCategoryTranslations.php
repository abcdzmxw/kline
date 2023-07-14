<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class adviceCategoryTranslations extends Model
{
    protected $table = 'advice_category_translations';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['locale','name'];

}
