<?php

namespace App\Admin\Controllers;

use App\Models\ArticleCategory;
use App\Models\Banner;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Tree;

class ArticleCategoryController extends AdminController
{
    public function index(Content $content)
    {
        return $content->header('文章分类')
            ->body(function (Row $row) {
                $tree = new Tree(new ArticleCategory);

                $row->column(12, $tree);
            });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new ArticleCategory(), function (Grid $grid) {
            $grid->id->sortable();
            $grid->pid;
//            $grid->names;
            $grid->order;
            $grid->created_at;
            $grid->updated_at->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

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
        return Show::make($id, new ArticleCategory(), function (Show $show) {
            $show->id;
//            $show->names;
            $show->pid;
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
        $builder = ArticleCategory::with('translations');
        return Form::make($builder, function (Form $form) {
           // $form->tab('基本信息', function (Form $form) {
                $form->display('id');
                $form->select('pid')->options(ArticleCategory::selectOptions())->default(0);
//                $form->text('names');
                $form->text('order');
                $form->text("url");
                $form->display('created_at');
                $form->display('updated_at');

                $form->hasMany('translations','语言', function (Form\NestedForm $form){
                    $form->select('locale')->options(
                        [
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
                    ]

                    )->default('zh-CN')->label();
                    $form->text('name');

                });

          /*  })->tab('内容编辑', function (Form $form) {

            });*/

        });
    }
}
