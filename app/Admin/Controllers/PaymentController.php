<?php

namespace App\Admin\Controllers;

use App\Models\Payment;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class PaymentController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Payment(), function (Grid $grid) {

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
//                $actions->disableEdit();
                $actions->disableView();
            });
            $grid->disableBatchDelete();
            $grid->disableRowSelector();

            $grid->column('id')->sortable();
            $grid->column('currency');
            $grid->column('exchange_rate');
            $grid->column('pay_type')->using(Payment::$payTypeMap);
            $grid->column('bank_name');
            $grid->column('real_name');
            $grid->column('card_no');
//            $grid->column('open_bank');
            $grid->column('remark');
            $grid->column('status')->switch();
            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {

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
        return Show::make($id, new Payment(), function (Show $show) {
            $show->field('id');
            $show->field('pay_type');
            $show->field('bank_name');
            $show->field('real_name');
            $show->field('card_no');
            $show->field('open_bank');
            $show->field('remark');
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
        return Form::make(new Payment(), function (Form $form) {
            $form->display('id');
            $form->text('currency');
            $form->text('exchange_rate');
            $form->select('pay_type')->options(Payment::$payTypeMap)->default(Payment::PAY_TYPE_BANK)->readOnly();
            $form->text('bank_name');
            $form->text('real_name');
            $form->text('card_no');
//            $form->text('open_bank');
            $form->text('remark');
            $form->switch('status')->default(1);

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
