<?php

namespace App\Admin\Controllers;

use App\Models\Article;
use App\Models\ArticleCategory;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Card;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class ArticleController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Article(), function (Grid $grid) {
            $grid->model()->orderByDesc('id');
            $grid->id->sortable();
            $grid->column("admin_user_id","文章作者(id)")->display(function(){
                $res = DB::table("admin_users")->where("id",$this->admin_user_id)->select("name","id")->first();
                $name = $res->name;
                $id = $res->id;
                return $name."(".$id.")";
            });

            $grid->title;
            $grid->cover->image('',50,50);
            $grid->body->display('详情') // 设置按钮名称
            ->expand(function () {
                // 返回显示的详情
                // 这里返回 content 字段内容，并用 Card 包裹起来
                $card = new Card(null, $this->body);

                return "<div style='padding:10px 10px 0'>$card</div>";
            });
            $grid->category_id->display(function($id){
                return blank($cat = ArticleCategory::query()->where('id',$id)->first()) ? '':$cat->name;
            });
            $grid->view_count;
            $grid->is_recommend;

            $grid->status->using([0=>'不显示',1=>'显示'])->label([0=>'default',1=>'success']);
            $grid->order;
            $grid->created_at;
//            $grid->updated_at->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();
//                $filter->equal('id')->width(2)->placeholder("请输入ID");
//                $filter->in('category_id')->select(ArticleCategory::selectOptions());

                $filter->where('category_id', function ($query) {

                    $subIds = ArticleCategory::getSubChildren($this->input);

                    if(blank($subIds)){
                        $query->where('category_id',$this->input);
                    }else{
                        $subIds[] = $this->input;
                        $query->whereIn('category_id',$subIds);
                    }

                }, '分类')->select(ArticleCategory::selectOptions());


            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new Article(), function (Show $show) {
            $show->id;
            $show->admin_user_id;
            $show->body;
            $show->title;
            $show->category_id;
            $show->view_count;
            $show->cover;
            $show->is_recommend;
            $show->status;
            $show->order;
            $show->created_at;
            $show->updated_at;
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $builder = Article::with('translations');

        return Form::make($builder, function (Form $form) {

            $form->tab('基本信息', function (Form $form) {

                $form->display('id');
                $form->select('category_id')->options(ArticleCategory::selectOptions());
                $form->file('cover')->accept('jpg,png,gif,jpeg,mp4')->maxSize("20480")->autoUpload();
                $form->switch('status')->default(1);
                $form->number('order')->default(1);
                $form->select("is_recommend",'是否推荐')->options([0=>"不推荐",1=>"推荐"])->default(0);
//                $form->number('view_count');
                if($form->isCreating()){
                    $form->display('created_at');
                }else{
                    $form->datetime('created_at');
                }
//                $form->display('updated_at');

            })->tab('内容编辑', function (Form $form) {
                $form->hasMany('translations','内容编辑', function (Form\NestedForm $form){
                    
                    $lang = [
                        'en'=>'英',
                        'zh-CN'=>'中',
                        "zh-TW"=>"繁体",
                        'kor' => '韩文',
                        'jp' => '日文',
                        'de' => '德文',
                        'it' => '意大利文',
                        // 'nl' => '荷兰文',
                        'pl' => '波兰文',
                        'pt' => '葡萄牙文',
                        'spa' => '西班牙文',
                        'swe' => '瑞典文',
                        'tr' => '土耳其文',
                        'uk' => '乌克兰文',
                        'fin' => '芬兰文',
                        'fra'  => '法国文',
                    ];
                    
                    
                    $form->select('locale')->options($lang)->default('zh-CN');

                    $form->text('title');
                    $form->textarea('excerpt');
                    $form->editor('body');

                });
            });
        });
    }


}
