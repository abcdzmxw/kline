<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/4
 * Time: 16:31
 */

namespace App\Admin\Controllers;
use App\Models\UserDepositAddress;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class DepositAddressController extends  AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new UserDepositAddress(), function (Grid $grid) {
            $grid->id;
            $grid->coin_name;
            $grid->wallet_address;
            $grid->wallet_address_image->image('',50,50);
            $grid->status->using([0=>'禁用',1=>'启用'])->dot([0=>'danger',1=>'success']);

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
        return Show::make($id, new UserDepositAddress(), function (Show $show) {
            $show->id;
            $show->coin_name;
            $show->wallet_address;
            $show->wallet_address_image;
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
        return Form::make(new UserDepositAddress(), function (Form $form) {
            $form->display('id');
            $form->text('coin_name');
            $form->text('wallet_address');
            $form->image('wallet_address_image');
            $form->switch('status')->default(0);
        });
    }
}