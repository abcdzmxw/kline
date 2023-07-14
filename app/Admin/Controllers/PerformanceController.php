<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Performance\ChangeStatus;
use App\Models\Performance;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class PerformanceController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(Performance::with('agent'), function (Grid $grid) {

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
                $actions->disableEdit();
                $actions->disableView();

                if($actions->row->status == Performance::status_wait_settle){
                    $actions->append(new ChangeStatus());
                }
            });
            $grid->disableCreateButton();
            $grid->disableBatchActions();
            $grid->disableBatchDelete();
//            $grid->disableRowSelector();

//            $grid->column('id')->sortable();
            $grid->column('aid');
            $grid->column('agent.name','代理名称');
            $grid->column('date','日期')->display(function(){
                return date('Y-m-d',strtotime($this->start_time)) . ' ~ ' . date('Y-m-d',strtotime($this->end_time));
            });
//            $grid->column('start_time');
//            $grid->column('end_time');
            $grid->column('subscribe_performance')->sortable();
            $grid->column('contract_performance')->sortable();
            $grid->column('option_performance')->sortable();
            $grid->column('subscribe_rebate_rate');
            $grid->column('contract_rebate_rate');
            $grid->column('option_rebate_rate');
            $grid->column('subscribe_rebate');
            $grid->column('contract_rebate');
            $grid->column('option_rebate');
            $grid->column('sum','总和')->display(function(){
                return $this->subscribe_rebate + $this->contract_rebate + $this->option_rebate;
            });
            $grid->column('status')->using(Performance::$statusMap)->dot([1=>'default',2=>'success']);
//            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('aid','代理ID')->width(2);

                $filter->where('datetime',function ($q){
                    $date = $this->input;
                    $q->whereDate('start_time','<=',$date)->whereDate('end_time','>=',$date);
                },'日期')->date()->width(3);
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
        return Show::make($id, new Performance(), function (Show $show) {
            $show->field('id');
            $show->field('start_time');
            $show->field('end_time');
            $show->field('subscribe_rebate');
            $show->field('contract_rebate');
            $show->field('option_rebate');
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
        return Form::make(new Performance(), function (Form $form) {
            $form->display('id');
            $form->text('start_time');
            $form->text('end_time');
            $form->text('subscribe_rebate');
            $form->text('contract_rebate');
            $form->text('option_rebate');
            $form->text('status');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
