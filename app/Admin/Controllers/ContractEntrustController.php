<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\ContractEntrust\BatchCancel;
use App\Admin\Actions\ContractEntrust\cancel;
use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\ContractEntrust;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;

class ContractEntrustController extends AdminController
{
    public function statistics()
    {
        $builder1 = ContractEntrust::query()->whereHas('user',function ($q){
            $q->where('is_system',0);
        });
        $params = request()->only(['user_id','username','symbol','type','ts']);

        if(!empty($params)){
            if(!empty($params['user_id'])){
                $builder1->where('user_id',$params['user_id']);
            }
            if(!empty($params['username'])){
                $username = $params['username'];
                $builder1->whereHas('user',function($q)use($username){
                    $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                });
            }
            if(!empty($params['symbol'])){
                $builder1->where('symbol',$params['symbol']);
            }
            if(!empty($params['type'])){
                if($params['type'] == 1){
                    $builder1->where('order_type',1)->where('side',1);
                }elseif($params['type'] == 2){
                    $builder1->where('order_type',1)->where('side',2);
                }elseif($params['type'] == 3){
                    $builder1->where('order_type',2)->where('side',2);
                }else{
                    $builder1->where('order_type',2)->where('side',1);
                }
            }
            if(!empty($params['ts']) && !empty($params['ts']['start'])){
                $start = $params['ts']['start'] ? strtotime($params['ts']['start']) : null;
                $end = $params['ts']['end'] ? strtotime($params['ts']['end']) : null;
                $builder1->whereBetween('ts',[$start,$end+86399]);
            }

        }

        $res1 = $builder1->where('status',ContractEntrust::status_completed)->sum('profit');

        $con = '<code>'.'总盈亏：'.$res1.'USDT</code> ';
        return Alert::make($con, '统计')->info();
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $data = ContractEntrust::query()->whereHas('user',function ($q){
            $q->where('is_system',0);
        });
        return Grid::make($data, function (Grid $grid) {
            $grid->model()->orderByDesc('id');

            #统计
            $grid->header(function ($query) {
                return $this->statistics();
            });

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
                $actions->disableEdit();
                $actions->disableView();

                if(in_array($actions->row->status,[ContractEntrust::status_wait,ContractEntrust::status_trading])) {
                    $actions->append(new cancel());
                }
            });
            $grid->disableCreateButton();
            $grid->disableBatchDelete();
//            $grid->disableRowSelector();

            $grid->tools([new BatchCancel()]);

            $grid->column('id')->sortable();
            $grid->column('order_no');
            $grid->column('user_id');
            $grid->column('order_type_side','交易类型')->display(function(){
                if($this->order_type == 1 && $this->side == 1){
                    return '买入开多';
                }elseif($this->order_type == 1 && $this->side == 2){
                    return '卖出开空';
                }elseif ($this->order_type == 2 && $this->side == 1){
                    return '买入平空';
                }else{
                    return '卖出平多';
                }
            })->label();
//            $grid->column('order_type');
//            $grid->column('side');
//            $grid->column('contract_id');
//            $grid->column('contract_coin_id');
            $grid->column('symbol');
            $grid->column('type')->using(ContractEntrust::$typeMap);
            $grid->column('lever_rate');
            $grid->column('entrust_price');
//            $grid->column('trigger_price');
            $grid->column('amount');
            $grid->column('traded_amount');
//            $grid->column('margin');
            $grid->column('avg_price');
            $grid->column('fee');
            $grid->column('profit');
            $grid->column('settle_profit');
            $grid->column('status')->using(ContractEntrust::$statusMap)->dot([
                1 => 'primary',
                2 => 'danger',
                3 => 'success',
                4 => 'info',
            ],'primary')->filter(
                Grid\Column\Filter\In::make(ContractEntrust::$statusMap)
            );
//            $grid->column('hang_status');
//            $grid->column('cancel_time');
//            $grid->column('ts');
            $grid->column('created_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {

                $filter->equal('user_id','UID')->width(2);
                $filter->where('username',function($q){
                    $username = $this->input;
                    $q->whereHas('user',function($q)use($username){
                        $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                    });
                },"用户名/手机/邮箱")->width(3);
                $filter->equal('symbol')->width(2);
                $filter->where('type',function ($q){
                    if($this->input == 1){
                        $q->where('order_type',1)->where('side',1);
                    }elseif($this->input == 2){
                        $q->where('order_type',1)->where('side',2);
                    }elseif($this->input == 3){
                        $q->where('order_type',2)->where('side',2);
                    }else{
                        $q->where('order_type',2)->where('side',1);
                    }
                },'交易类型')->select([1=>'开多',2=>'开空',3=>'平多',4=>'平空'])->width(3);
                $filter->whereBetween('ts',function ($q){
                    $start = $this->input['start'] ? strtotime($this->input['start']) : null;
                    $end = $this->input['end'] ? strtotime($this->input['end']) : null;
                    $q->whereBetween('ts',[$start,$end+86399]);
                },'时间')->date()->width(4);

//                $filter->where('referrer', function ($query) {
//                    $query->whereHas('user', function ($q) {
//                        $q->where('referrer', '=', "$this->input");
//                    });
//                },'代理ID')->width(3);

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
        return Show::make($id, new ContractEntrust(), function (Show $show) {
            $show->field('id');
            $show->field('order_no');
            $show->field('order_type');
            $show->field('user_id');
            $show->field('side');
            $show->field('contract_id');
            $show->field('contract_coin_id');
            $show->field('symbol');
            $show->field('type');
            $show->field('entrust_price');
            $show->field('trigger_price');
            $show->field('amount');
            $show->field('traded_amount');
            $show->field('lever_rate');
            $show->field('margin');
            $show->field('fee');
            $show->field('status');
            $show->field('hang_status');
            $show->field('cancel_time');
            $show->field('ts');
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
        return Form::make(new ContractEntrust(), function (Form $form) {
            $form->display('id');
            $form->text('order_no');
            $form->text('order_type');
            $form->text('user_id');
            $form->text('side');
            $form->text('contract_id');
            $form->text('contract_coin_id');
            $form->text('symbol');
            $form->text('type');
            $form->text('entrust_price');
            $form->text('trigger_price');
            $form->text('amount');
            $form->text('traded_amount');
            $form->text('lever_rate');
            $form->text('margin');
            $form->text('fee');
            $form->text('status');
            $form->text('hang_status');
            $form->text('cancel_time');
            $form->text('ts');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
