<?php

namespace App\Admin\Controllers;

use App\Models\AppVersion;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class AppVersionController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new AppVersion(), function (Grid $grid) {

            $grid->disableDeleteButton();
            $grid->disableRowSelector();
            $grid->disableCreateButton();

            $grid->column('id')->sortable();
            $grid->column('client_type')->using([1=>'Android',2=>'IOS'])->badge([1=>'info',2=>'success']);
            $grid->column('version');
            $grid->column('is_must')->using([0=>'否',1=>'是'])->label([0=>'default',1=>'success']);
            $grid->column('url');
            $grid->column('update_log');
            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
//                $filter->equal('id');

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
        return Show::make($id, new AppVersion(), function (Show $show) {
            $show->field('id');
            $show->field('client_type');
            $show->field('version');
            $show->field('is_must');
            $show->field('url');
            $show->field('update_log');
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
        return Form::make(new AppVersion(), function (Form $form) {
            $form->display('id');
            $form->hidden('client_type');
            $form->text('version');
            $form->switch('is_must');
            $form->text('url');
            $form->editor('update_log');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
