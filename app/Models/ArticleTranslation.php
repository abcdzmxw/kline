<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleTranslation extends Model
{
    //
    protected $table = 'article_translations';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['locale','title', 'body','excerpt'];

}
