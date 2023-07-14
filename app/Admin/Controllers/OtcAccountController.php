<?php

namespace App\Admin\Controllers;

use App\Models\OtcAccount;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class OtcAccountController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new OtcAccount(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('coin_id');
            $grid->column('coin_name');
            $grid->column('usable_balance');
            $grid->column('freeze_balance');
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();
        
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
        return Show::make($id, new OtcAccount(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('coin_id');
            $show->field('coin_name');
            $show->field('usable_balance');
            $show->field('freeze_balance');
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
        return Form::make(new OtcAccount(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('coin_id');
            $form->text('coin_name');
            $form->text('usable_balance');
            $form->text('freeze_balance');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
