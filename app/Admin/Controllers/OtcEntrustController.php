<?php

namespace App\Admin\Controllers;

use App\Models\OtcEntrust;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class OtcEntrustController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new OtcEntrust(), function (Grid $grid) {
            $grid->model()->orderByDesc('id');

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
                $actions->disableEdit();
                $actions->disableView();
            });
            $grid->disableBatchDelete();
            $grid->disableCreateButton();

            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('side')->using([1=>'购买',2=>'出售']);
            $grid->column('order_sn');
//            $grid->column('coin_id');
            $grid->column('coin_name');
            $grid->column('min_num');
            $grid->column('max_num');
            $grid->column('note');
            $grid->column('pay_type')->label();
            $grid->column('publish_time')->display(function($v){
                return date('Y-m-d H:i:s',$v);
            });
            $grid->column('price');
            $grid->column('amount');
            $grid->column('cur_amount');
            $grid->column('lock_amount');
            $grid->column('order_count');
            $grid->column('deal_count');
            $grid->column('deal_rate');
            $grid->column('status')->using(OtcEntrust::$statusMap)->dot([0=>'default',1=>'info',2=>'success']);
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
        return Show::make($id, new OtcEntrust(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('side');
            $show->field('order_sn');
            $show->field('coin_id');
            $show->field('coin_name');
            $show->field('min_num');
            $show->field('max_num');
            $show->field('note');
            $show->field('pay_type');
            $show->field('publish_time');
            $show->field('price');
            $show->field('amount');
            $show->field('cur_amount');
            $show->field('lock_amount');
            $show->field('order_count');
            $show->field('deal_count');
            $show->field('deal_rate');
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
        return Form::make(new OtcEntrust(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('side');
            $form->text('order_sn');
            $form->text('coin_id');
            $form->text('coin_name');
            $form->text('min_num');
            $form->text('max_num');
            $form->text('note');
            $form->text('pay_type');
            $form->text('publish_time');
            $form->text('price');
            $form->text('amount');
            $form->text('cur_amount');
            $form->text('lock_amount');
            $form->text('order_count');
            $form->text('deal_count');
            $form->text('deal_rate');
            $form->text('status');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
