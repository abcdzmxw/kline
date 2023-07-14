<?php

namespace App\Admin\Controllers;

use App\Models\Navigation;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class NavigationController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected $title = "导航配置";
    protected function grid()
    {
        return Grid::make(new Navigation(), function (Grid $grid) {
            $grid->id->sortable();
            $grid->column('type',"位置")->using(Navigation::$type);
            $grid->name;
            $grid->img;
            $grid->link_type;
            $grid->link_data;
            $grid->desc;
            $grid->order;
            $grid->column("status","是否首页显示")->using(Navigation::$status);
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
        return Show::make($id, new Navigation(), function (Show $show) {
            $show->id;
            $show->type;
            $show->name;
            $show->img;
            $show->link_type;
            $show->link_data;
            $show->desc;
            $show->order;
            $show->status;
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
        return Form::make(new Navigation(), function (Form $form) {
            $form->display('id');
            $form->select('type',"顶部|底部")->options(Navigation::$type)->default(1);
            $form->image('img')->autoUpload();
            $form->text('link_type',"链接类型");
            $form->text('link_data',"链接数据");
            $form->text('desc',"描述");
            $form->text('order')->default(1);
            $form->select('status',"是否首页展示")->options(Navigation::$status)->default(1);
            $form->hasMany('translations','语言', function (Form\NestedForm $form){
                $form->select('locale')->options(['en'=>'英','zh-CN'=>'中',"zh-TW"=>"繁体"])->default('zh-CN')->label();

                $form->text('name');
                // $form->text('imgurl');

            });



        });
    }
}
