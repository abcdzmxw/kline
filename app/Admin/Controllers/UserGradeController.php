<?php

namespace App\Admin\Controllers;

use App\Models\UserGrade;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class UserGradeController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new UserGrade(), function (Grid $grid) {

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
                $actions->disableView();
            });
            $grid->disableDeleteButton();
            $grid->disableBatchDelete();
            $grid->disableCreateButton();
            $grid->disableRowSelector();

            $grid->grade_id->sortable();
            $grid->grade_name->badge();
            $grid->grade_img->image('',50,50);
//            $grid->ug_self_vol;
//            $grid->ug_recommend_grade;
//            $grid->ug_recommend_num;
//            $grid->ug_total_vol;
//            $grid->ug_direct_vol;
//            $grid->ug_direct_vol_num;
//            $grid->ug_direct_recharge;
//            $grid->ug_direct_recharge_num;
            $grid->bonus->label();
//            $grid->status;
//            $grid->created_at;
//            $grid->updated_at->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('grade_id');

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
        return Show::make($id, new UserGrade(), function (Show $show) {
            $show->grade_id;
            $show->grade_name;
            $show->grade_img;
            $show->ug_self_vol;
            $show->ug_recommend_grade;
            $show->ug_recommend_num;
            $show->ug_total_vol;
            $show->ug_direct_vol;
            $show->ug_direct_vol_num;
            $show->ug_direct_recharge;
            $show->ug_direct_recharge_num;
            $show->bonus;
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
        return Form::make(new UserGrade(), function (Form $form) {

            $form->row(function (Form\Row $form) {
                $form->display('grade_id');
                $form->text('grade_name');
                $form->image('grade_img')->uniqueName()->autoUpload();
            });

            $form->row(function (Form\Row $form) {
                $form->width(4)->select('ug_recommend_grade')->options(UserGrade::query()->pluck('grade_name','grade_id'));
                $form->width(4)->number('ug_recommend_num');
            });

            $form->row(function (Form\Row $form) {
                $form->width(4)->number('ug_direct_vol');
                $form->width(4)->number('ug_direct_vol_num');
            });

            $form->row(function (Form\Row $form) {
                $form->width(4)->number('ug_direct_recharge');
                $form->width(4)->number('ug_direct_recharge_num');
            });

            $form->row(function (Form\Row $form) {
                $form->width(4)->number('ug_self_vol');
                $form->width(4)->number('ug_total_vol');
            });

//            $form->text('ug_self_vol');
//            $form->text('ug_total_vol');
//            $form->text('ug_recommend_grade');
//            $form->text('ug_recommend_num');
//            $form->text('ug_direct_vol');
//            $form->text('ug_direct_vol_num');
//            $form->text('ug_direct_recharge');
//            $form->text('ug_direct_recharge_num');

            $form->row(function (Form\Row $form) {
                $form->text('bonus');
//                $form->switch('status');
                $form->display('created_at');
                $form->display('updated_at');
            });

        });
    }
}
