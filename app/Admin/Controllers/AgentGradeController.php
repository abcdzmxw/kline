<?php

namespace App\Admin\Controllers;

use App\Models\AgentGrade;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class AgentGradeController extends AdminController
{
    protected $title = "代理级别";

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new AgentGrade(), function (Grid $grid) {
            $grid->id->sortable();
//            $grid->key;
            $grid->column('value','名称')->editable(true)->help('点击编辑');

            $grid->disableActions();
            $grid->disableCreateButton();
            $grid->disableBatchDelete();

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
        return Show::make($id, new AgentGrade(), function (Show $show) {
            $show->id;
            $show->key;
            $show->value;
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new AgentGrade(), function (Form $form) {
            $form->display('id');
            $form->text('key');
            $form->text('value','名称');
        });
    }
}
