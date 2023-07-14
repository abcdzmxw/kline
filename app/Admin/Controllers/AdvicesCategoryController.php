<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\AdvicesCategory;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class AdvicesCategoryController extends AdminController
{
    protected $title = "咨询项目";

    protected function grid()
    {
        return Grid::make(new AdvicesCategory(), function (Grid $grid) {
            $grid->model()->orderBy("order");
            //$grid->id;
            $grid->name;
            $grid->column('status',"状态")->switch();
            $grid->column("order","排序");
            // $grid->model()->orderByDesc('created_at');
              $grid->actions(function (Grid\Displayers\Actions $actions) {


                  $actions->disableView();
              });
            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();
                $filter->like('name',"问题类型")->width(2)->placeholder("请输入问题类型");

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
        return Show::make($id, new AdvicesCategory(), function (Show $show) {
            $show->id;
            $show->name;
            $show->order;
            $show->status;


        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new AdvicesCategory(), function (Form $form) {
            $form->display('id');
            $form->switch('status',"状态")->default(1);
            $form->number('order',"排序");
            $form->hasMany('translations','语言', function (Form\NestedForm $form){
                $form->select('locale')->options(['en'=>'英','zh-CN'=>'中',"zh-TW"=>"繁体"])->default('zh-CN')->label();
                $form->text('name',"名称")->required();

            });

        });
    }
}
