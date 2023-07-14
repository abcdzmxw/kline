<?php

namespace App\Admin\Controllers;

use App\Models\Advice;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use function foo\func;

class AdviceController extends AdminController
{
    /**
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Advice(), function (Grid $grid) {
            $grid->model()->orderByDesc("created_at");
            $grid->id->sortable();
            $grid->column("user_id","用户id");
            $grid->column("phone","手机号");
            $grid->column("Email","邮箱");
            $grid->column("realname","真实姓名");
            $grid->column("contents","内容");

            $grid->column("is_process","处理结果")->display(function (){
                if( $this->is_process== 0 ){
                    return "<label style='color: red'>未处理</label>";
                }
                return "<label style='color:dodgerblue'>已处理</label>";
            });
            $grid->process_note;
            $grid->process_time;
            $grid->created_at;
            $grid->updated_at->sortable();

            $grid->disableCreateButton();
            //$grid->disableDeleteButton();
            //$grid->disableEditButton();
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
        return Show::make($id, new Advice(), function (Show $show) {
            $show->id;
            $show->user_id;
            $show->phone;

            $show->realname;
            $show->contents;

            $show->is_process->using(Advice::$statusMap);
            $show->process_note;
            $show->process_time;
            $show->created_at;
           // $show->updated_at;
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Advice(), function (Form $form) {
            $form->text('id')->display(false);
           /* $form->text('user_id',"用户id")->readOnly();
            $form->text('phone',"手机号");
            $form->text('email',"邮箱");
            $form->text('realname',"真实姓名");*/
            $form->text('contents',"内容")->readOnly();

            $form->select('is_process',"处理结果")->options([
                "0"=>"未处理",
                "1"=>"已处理"
            ]);
            $form->textarea('process_note');

            //$form->display('created_at');
            $form->text('updated_at')->display(false);

            $form->saved(function (Form $form) {
                if ($form->isEditing()) {
                    $form->process_time = date("Y-m-d H:i:s");
                }

            });


        });
    }
}
