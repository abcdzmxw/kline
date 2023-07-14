<?php

namespace App\Admin\Controllers;

use App\Models\InsideTradePair;
use App\Models\InsideTradeRisk;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class InsideTradeRiskController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new InsideTradeRisk(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('pair_id');
//            $grid->column('pair_name');
            $grid->column('symbol');
            $grid->column('up_or_down','涨跌')->using([0=>'跌',1=>'涨'])->dot([0=>'danger',1=>'success']);
            $grid->column('range','幅度')->label();
            $grid->column('start_time');
            $grid->column('end_time');
            $grid->column('status')->switch();
//            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('symbol')->width(3);

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
        return Show::make($id, new InsideTradeRisk(), function (Show $show) {
            $show->field('id');
            $show->field('pair_id');
            $show->field('pair_name');
            $show->field('symbol');
            $show->field('status');
            $show->field('start_time');
            $show->field('end_time');
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
        return Form::make(new InsideTradeRisk(), function (Form $form) {
            $options = InsideTradePair::query()->where('status',1)->pluck('symbol','pair_id')->toArray();

            $form->display('id');
            if($form->isCreating()){
                $form->select('pair_id','交易对')->options($options);
            }else{
                $form->select('pair_id','交易对')->options($options)->readOnly();
            }
            $form->hidden('symbol');

            $form->radio('up_or_down')->options([0=>'跌',1=>'涨'])->value(1);
            $form->text('range');

            $form->switch('status')->default(0);

            $form->datetime('start_time')->help('任务运行开始时间，开启状态时必填');
            $form->datetime('end_time')->help('任务运行结束时间，不填表示一直运行');

            $form->display('created_at');
            $form->display('updated_at');

            $form->saving(function (Form $form) use ($options){
                if($form->isCreating()){
                    if(!blank($form->pair_id)){
                        $symbol = $options[$form->pair_id];
                        $is_exist = InsideTradeRisk::query()->where('symbol',$symbol)->first();
                        if($is_exist) return $form->error('该交易对已存在任务~');

                        $form->symbol = $symbol;
                    }
                }
            });

        });
    }
}
