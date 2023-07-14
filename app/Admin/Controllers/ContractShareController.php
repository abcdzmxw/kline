<?php

namespace App\Admin\Controllers;

use App\Models\ContractShare;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class ContractShareController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new ContractShare(), function (Grid $grid) {
            $grid->column('id')->sortable();

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
//                $actions->disableEdit();
                $actions->disableView();
            });

            $grid->column('bg_img')->image();
            $grid->column('text_img')->image();
            $grid->column('peri_img')->image();
            $grid->column('status')->switch();
            $grid->column('created_at')->display(function($v){
                return date('Y-m-d H:i:s',$v);
            });

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
        return Show::make($id, new ContractShare(), function (Show $show) {
            $show->field('id');
            $show->field('data');
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
        return Form::make(new ContractShare(), function (Form $form) {
            $form->display('id');

            $form->image('bg_img','背景图')->uniqueName()->autoUpload()->disableRemove();
            $form->image('text_img','文字图')->uniqueName()->autoUpload()->disableRemove();
            $form->image('peri_img','人物图')->uniqueName()->autoUpload()->disableRemove();
            $form->switch('status')->default(1);

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
