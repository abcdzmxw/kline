<?php

namespace App\Admin\Controllers;

use App\Models\SubscribeActivity;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class SubscribeActivityController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new SubscribeActivity(), function (Grid $grid) {
            $grid->model()->orderByDesc('id');

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
//                $actions->disableEdit();
                $actions->disableView();
            });
            $grid->disableBatchDelete();
            $grid->disableCreateButton();
            $grid->disableBatchActions();

            $grid->column('id')->sortable();
            $grid->column('name');
            $grid->column('start_time');
            $grid->column('end_time');
            $grid->column('params')->pluck('amount')->label('info');
            $grid->column('status')->using([0=>'已结束',1=>'活动中'])->dot([0=>'danger',1=>'success']);
            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();

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
        return Show::make($id, new SubscribeActivity(), function (Show $show) {
            $show->field('id');
            $show->field('name');
            $show->field('start_time');
            $show->field('end_time');
            $show->field('params');
            $show->field('status');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new SubscribeActivity(), function (Form $form) {
            $form->disableDeleteButton();

            $form->display('id');
            $form->text('name');
            $form->datetime('start_time');
            $form->datetime('end_time');

            $form->table('params', function ($table) {
                $table->text('amount','申购数量');
                $table->text('rate','奖励比率')->help('小数0.16表示16%');
            });

            $form->switch('status')->default(1);

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
