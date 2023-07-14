<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/5
 * Time: 14:44
 */

namespace App\Admin\Controllers;
use App\Models\UserPaymentMethod;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class PaymentMethodController extends  AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new UserPaymentMethod(), function (Grid $grid) {
            $grid->id;
            $grid->payment_method;
            $grid->receiving_account;
            $grid->note;
            $grid->payment_image->image('',50,50);

            $grid->created_at;
            $grid->updated_at;

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
        return Show::make($id, new UserPaymentMethod(), function (Show $show) {
            $show->id;
            $show->payment_method;
            $show->receiving_account;
            $show->payment_image;
            $show->note;
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
        return Form::make(new UserPaymentMethod(), function (Form $form) {
            $form->display('id');
            $form->text('payment_method');
            $form->text('note');
            $form->text('receiving_account');
            $form->image('payment_image')->autoUpload();


        });
    }
}