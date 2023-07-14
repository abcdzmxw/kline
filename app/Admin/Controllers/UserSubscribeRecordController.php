<?php

namespace App\Admin\Controllers;

use App\Models\UserSubscribeRecord;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class UserSubscribeRecordController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new UserSubscribeRecord(), function (Grid $grid) {
            $grid->model()->orderByDesc('id');

            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->disableBatchDelete();
            $grid->disableActions();

            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('payment_amount');
            $grid->column('payment_currency');
            $grid->column('subscription_time')->display(function($v){
                return date('Y-m-d H:i:s',$v);
            });
            $grid->column('subscription_currency_name');
            $grid->column('subscription_currency_amount');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->where('search', function ($query) {
                    $query->whereHas('user', function ($q) {
                        $q->where('username', '=', "$this->input")
                            ->orWhere('phone', '=', "$this->input")
                            ->orWhere('account', '=', "$this->input");
                    });
                },'用户名/手机')->width(3);

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
        return Show::make($id, new UserSubscribeRecord(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('payment_amount');
            $show->field('payment_currency');
            $show->field('subscription_time');
            $show->field('subscription_currency_name');
            $show->field('subscription_currency_amount');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new UserSubscribeRecord(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('payment_amount');
            $form->text('payment_currency');
            $form->text('subscription_time');
            $form->text('subscription_currency_name');
            $form->text('subscription_currency_amount');
        });
    }
}
