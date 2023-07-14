<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Translate;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class TranslatesController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Translate(), function (Grid $grid) {
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
//                $actions->disableQuickEdit();
//                $actions->disableEdit();
                $actions->disableView();
            });
            $grid->disableCreateButton();
            $grid->disableDeleteButton();
//            $grid->disableEditButton();
            $grid->disableRowSelector();

            $grid->id->sortable();
            $grid->lang;
//            $grid->json_content;
            $grid->file;
//            $grid->created_at;
            $grid->updated_at->sortable();

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
        return Show::make($id, new Translate(), function (Show $show) {
            $show->id;
            $show->lang;
            $show->json_content;
            $show->file;
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
        return Form::make(new Translate(), function (Form $form) {
            $form->display('id');
            $form->text('lang')->readOnly();
//            $form->text('json_content');
//            $form->table('json_content', function ($table) {
//                $table->text('key');
//                $table->text('value');
//            })->saving(function ($v) {
//                return json_encode($v);
//            });
            $form->keyValue('json_content');
            $form->file('file')->autoUpload();

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
