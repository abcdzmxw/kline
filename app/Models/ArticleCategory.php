<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Dcat\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArticleCategory extends Model
{
    //文章分类表

    use ModelTree,SoftDeletes,Translatable;

    public $translationModel = CategoryTranslations::class;
    public $translationForeignKey = 'category_id';
    public $translatedAttributes = ['name'];

    protected $table = 'article_category';

    protected $primaryKey = 'id';

    protected $guarded = [];

    // 父级ID字段名称，默认值为 parent_id
    protected $parentColumn = 'pid';

    // 排序字段名称，默认值为 order
    protected $orderColumn = 'order';

    // 标题字段名称，默认值为 title
    protected $titleColumn = 'name';

    public static $typeMap = [
        'notice' => 4, //公告
        'help_center' => 2, //帮助中心
        'agreement'   => 3, //协议
        'information' => 1, //资讯
        'college'     => 5, //学院
        'grade_remark'=> 35, //用户等级说明
        'clause'      => 11, //隐私条款
        'marketAction'=> 32,//市场动态

    ];

    public function sub_categorys()
    {
        return $this->hasMany(ArticleCategory::class, 'pid','id');
    }

    //取出无限级子级
    public static function getSubChildren($id,$subIds = [])
    {
        $categorys = ArticleCategory::query()->where('pid',$id)->select(['id','pid'])->get();

        if(blank($categorys)){
            return [];
        }else{
            $categorys = $categorys->toArray();
        }

        $subIds = get_tree_child($categorys,$id);

        return $subIds;
//        $categorys = ArticleCategory::query()->where('pid',$id)->select(['id','pid'])->get();
//        foreach ($categorys as $key=>$value){
//            $subIds[] = $value['id'];
//            $category = ArticleCategory::query()->where('pid',$value['id'])->select(['id','pid'])->get();
//            if($category){
//                $subIds = self::getSubChildren($value['id'],$subIds);
//            }
//        }
//        return $subIds;
    }

    //系统公告
    public function category_translations()
    {
        return $this->belongsTo(CategoryTranslations::class,'id','category_id');
    }

}
