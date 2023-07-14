<?php

namespace App\Admin\Controllers;

use App\Models\ContactInfo;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class ContactInfoController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected $title='联系我们信息';
    protected function grid()
    {
        return Grid::make(new ContactInfo(), function (Grid $grid) {
            $grid->id->sortable();
            $grid->name;
            $grid->url;

            $grid->disableDeleteButton();
            $grid->disableViewButton();
            $grid->disableRowSelector();


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
        return Show::make($id, new ContactInfo(), function (Show $show) {
            $show->id;
            $show->name;
            $show->url;//show 方法隐藏按钮

        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new ContactInfo(), function (Form $form) {
            $form->display('id');
            $form->text('name');
            $form->text('url');

        });
    }
}
