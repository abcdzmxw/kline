<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class NavigateTranslations extends Model
{
    protected $table = 'navigation_translations';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['locale','name'];

}
