<?php

namespace App\Admin\Controllers;

use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\ContractWearPositionRecord;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;

class ContractWearPositionRecordController extends AdminController
{
    public function statistics()
    {
        $builder = ContractWearPositionRecord::query()->whereHas('user',function ($q){
            $q->where('is_system',0);
        });
        $params = request()->only(['user_id','symbol']);

        if(!empty($params)){
            if(!empty($params['user_id'])){
                $builder->where('user_id',$params['user_id']);
            }
            if(!empty($params['symbol'])){
                $builder->where('symbol',$params['symbol']);
            }

        }

        $total = $builder->count();
        $total_loss = $builder->sum('loss');

        $con = '<code>总记录数：'.$total.'</code> '.'<code>穿仓损失：'.$total_loss. ' USDT' . '</code> ';
        return Alert::make($con, '统计')->info();
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new ContractWearPositionRecord(), function (Grid $grid) {

            #统计
            $grid->header(function ($query) {
                return $this->statistics();
            });

            $grid->model()->orderByDesc('id');

            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->disableActions();

            $grid->column('id')->sortable();
            $grid->column('user_id');
//            $grid->column('contract_id');
            $grid->column('symbol');
            $grid->column('position_side')->using([1=>'多',2=>'空'])->label([1=>'info',2=>'danger']);
            $grid->column('open_position_price');
            $grid->column('close_position_price');
            $grid->column('profit');
            $grid->column('settle_profit');
            $grid->column('loss');
            $grid->column('ts')->display(function ($v){
                return date('Y-m-d H:i:s',$v);
            });
//            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id')->width(3);
                $filter->equal('symbol')->width(3);
                $filter->whereBetween('ts',function ($q){
                    $start = $this->input['start'] ? strtotime($this->input['start']) : null;
                    $end = $this->input['end'] ? strtotime($this->input['end']) : null;
                    $q->whereBetween('ts',[$start,$end+86399]);
                },'时间')->date()->width(4);
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
        return Show::make($id, new ContractWearPositionRecord(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('contract_id');
            $show->field('symbol');
            $show->field('position_side');
            $show->field('open_position_price');
            $show->field('close_position_price');
            $show->field('profit');
            $show->field('settle_profit');
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
        return Form::make(new ContractWearPositionRecord(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('contract_id');
            $form->text('symbol');
            $form->text('position_side');
            $form->text('open_position_price');
            $form->text('close_position_price');
            $form->text('profit');
            $form->text('settle_profit');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
