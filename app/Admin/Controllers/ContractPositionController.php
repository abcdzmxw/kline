<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\ContractPosition\Flat;
use App\Admin\Actions\ContractPosition\OnekeyFlatPosition;
use App\Handlers\ContractTool;
use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\ContractPair;
use App\Models\ContractPosition;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Illuminate\Support\Facades\Cache;

class ContractPositionController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $builder = ContractPosition::query()->where('avail_position','>',0);
        return Grid::make($builder, function (Grid $grid) {
            $grid->model()->orderByDesc('id');

            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
                $actions->disableEdit();
                $actions->disableView();

                $actions->append(new Flat());
            });


            $grid->tools([new OnekeyFlatPosition()]);

            


            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('symbol');
            $grid->column('side')->using([1=>'多',2=>'空'])->label([1=>'info',2=>'danger']);
//            $grid->column('contract_id');
//            $grid->column('unit_amount');
            $grid->column('lever_rate');
            $grid->column('hold_position');
            $grid->column('avail_position');
            $grid->column('freeze_position');
            $grid->column('position_margin');
            $grid->column('avg_price');
            $grid->column('unRealProfit','未实现盈亏')->display(function(){
                $contract = ContractPair::query()->find($this->contract_id);
                
                $realtime_price = Cache::store('redis')->get('swap:' . 'trade_detail_' . $this->symbol)['price'] ?? null;
                // return ContractTool::unRealProfit($this,['unit_amount'=>$this->unit_amount],$realtime_price);
                return ContractTool::unRealProfit($this,$contract,$realtime_price);
            });
//            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id')->width(3);
                $filter->where('username',function($q){
                    $username = $this->input;
                    $q->whereHas('user',function($q)use($username){
                        $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                    });
                },"用户名/手机/邮箱")->width(3);
                $filter->equal('symbol')->width(3);
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
        return Show::make($id, new ContractPosition(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('side');
            $show->field('contract_id');
            $show->field('symbol');
            $show->field('unit_amount');
            $show->field('lever_rate');
            $show->field('hold_position');
            $show->field('avail_position');
            $show->field('freeze_position');
            $show->field('position_margin');
            $show->field('avg_price');
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
        return Form::make(new ContractPosition(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('side');
            $form->text('contract_id');
            $form->text('symbol');
            $form->text('unit_amount');
            $form->text('lever_rate');
            $form->text('hold_position');
            $form->text('avail_position');
            $form->text('freeze_position');
            $form->text('position_margin');
            $form->text('avg_price');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
