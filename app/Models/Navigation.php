<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Dcat\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Navigation extends Model
{

    protected $guarded = [];

    use ModelTree,SoftDeletes,Translatable;

    public $translationModel = NavigateTranslations::class;
    public $translationForeignKey = 'n_id';
    public $translatedAttributes = ['name'];

    protected $table = 'navigation';

    protected $primaryKey = 'id';
    static $type = [
        1 => '<h4>顶部</h4>',
        2 => '<h4 style="color:cornflowerblue">服务</h4>',
        3 => '<h4 style="color: green">学院</h4>',

    ];

    static $status = [
        0 => '否',
        1 => "是"
    ];

}
