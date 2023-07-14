<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryTranslations extends Model
{
    //
    protected $table = 'category_translations';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['locale','name'];

}
