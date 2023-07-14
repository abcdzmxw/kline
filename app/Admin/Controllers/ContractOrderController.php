<?php

namespace App\Admin\Controllers;

use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\ContractEntrust;
use App\Models\ContractOrder;
use App\Models\ContractPair;
use App\Models\UserWallet;
use App\Models\UserWalletLog;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;

class ContractOrderController extends AdminController
{
    public function statistics()
    {
        $builder1 = ContractOrder::query();
        $builder11 = ContractOrder::query();
        $params = request()->only(['user_id','username','symbol','ts']);

        $builder2 = ContractEntrust::query()->whereHas('user',function ($q){
            $q->where('is_system',0);
        });
        $builder3 = UserWalletLog::query()->where('rich_type','usable_balance')
            ->where('account_type',UserWallet::sustainable_account)
            ->where('log_type','position_capital_cost')
            ->whereHas('user',function($q){
               $q->where('is_system',0);
            });

        if(!empty($params)){
            if(!empty($params['user_id'])){
                $builder1->where('buy_user_id',$params['user_id']);
                $builder11->where('sell_user_id',$params['user_id']);
                $builder2->where('user_id',$params['user_id']);
                $builder3->where('user_id',$params['user_id']);
            }
            if(!empty($params['username'])){
                $username = $params['username'];
                $builder1->whereHas('buy_user',function($q)use($username){
                    $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                });
                $builder11->whereHas('sell_user',function($q)use($username){
                    $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                });
                $builder2->whereHas('user',function($q)use($username){
                    $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                });
                $builder3->whereHas('user',function($q)use($username){
                    $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                });
            }
            if(!empty($params['symbol'])){
                $builder1->where('symbol',$params['symbol']);
                $builder11->where('symbol',$params['symbol']);
                $builder2->where('symbol',$params['symbol']);
                $pair = ContractPair::query()->where('symbol',$params['symbol'])->select('id','symbol')->first();
                if(!blank($pair)){
                    $builder3->where('sub_account',$pair['id']);
                }
            }
            if(!empty($params['ts']) && !empty($params['ts']['start'])){
                $start = $params['ts']['start'] ? strtotime($params['ts']['start']) : null;
                $end = $params['ts']['end'] ? strtotime($params['ts']['end']) : null;
                $builder1->whereBetween('ts',[$start,$end+86399]);
                $builder11->whereBetween('ts',[$start,$end+86399]);
                $builder2->whereBetween('ts',[$start,$end+86399]);
                $builder3->whereDate('created_at','>=',$params['ts']['start'])->whereDate('created_at','<=',$params['ts']['end']);
            }

        }

        $total_buy_fee = $builder1->whereHas('buy_user',function ($q){
            $q->where('is_system',0);
        })->sum('trade_buy_fee');
        $total_sell_fee = $builder11->whereHas('sell_user',function ($q){
            $q->where('is_system',0);
        })->sum('trade_sell_fee');
        $total_fee = $total_buy_fee + $total_sell_fee; // 总手续费
        $total_profit = $builder2->where('status',ContractEntrust::status_completed)->sum('profit');//总盈亏
        $total_cost = $builder3->sum('amount'); //总资金费

        $con = '<code>总手续费：'.abs($total_fee).'USDT</code> ' . '<code>总资金费：'.(real)abs($total_cost).'USDT</code> ' . '<code>总盈亏：'.(real)$total_profit.'USDT</code> ';
        return Alert::make($con, '统计')->info();
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $builder = ContractOrder::query()->where(function($q){
            $q->whereHas('buy_user',function ($q){
                $q->where('is_system',0);
            })->orWhereHas('sell_user',function ($q){
                $q->where('is_system',0);
            });
        });
        return Grid::make($builder, function (Grid $grid) {
        // return Grid::make(new ContractOrder(), function (Grid $grid) {
            $grid->model()->orderByDesc('id');

            #统计
            $grid->header(function ($query) {
                return $this->statistics();
            });

            $grid->disableActions();
            $grid->disableCreateButton();
            $grid->disableRowSelector();

            $grid->column('id')->sortable();
//            $grid->column('contract_id');
            $grid->column('symbol');
            $grid->column('order_type')->using([1=>'开仓',2=>'平仓'])->label();
            $grid->column('lever_rate');
            $grid->column('buy_id');
            $grid->column('sell_id');
            $grid->column('buy_user_id');
            $grid->column('sell_user_id');
            $grid->column('unit_price');
            $grid->column('trade_amount');
            $grid->column('trade_buy_fee');
            $grid->column('trade_sell_fee');
            $grid->column('ts','时间')->display(function($v){
                return date('Y-m-d H:i:s',$v);
            });
//            $grid->column('created_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->where('user_id',function($q){
                    $q->where('buy_user_id',$this->input)->orWhere('sell_user_id',$this->input);
                },'UID')->width(2);
                $filter->where('username',function($q){
                    $username = $this->input;
                    $q->whereHas('buy_user',function($q)use($username){
                        $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                    })->orWhereHas('sell_user', function ($q) use ($username){
                        $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                    });
                },"用户名/手机/邮箱")->width(3);
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
        return Show::make($id, new ContractOrder(), function (Show $show) {
            $show->field('id');
            $show->field('contract_id');
            $show->field('symbol');
            $show->field('lever_rate');
            $show->field('order_type');
            $show->field('buy_id');
            $show->field('sell_id');
            $show->field('buy_user_id');
            $show->field('sell_user_id');
            $show->field('unit_price');
            $show->field('trade_amount');
            $show->field('trade_buy_fee');
            $show->field('trade_sell_fee');
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
        return Form::make(new ContractOrder(), function (Form $form) {
            $form->display('id');
            $form->text('contract_id');
            $form->text('symbol');
            $form->text('lever_rate');
            $form->text('order_type');
            $form->text('buy_id');
            $form->text('sell_id');
            $form->text('buy_user_id');
            $form->text('sell_user_id');
            $form->text('unit_price');
            $form->text('trade_amount');
            $form->text('trade_buy_fee');
            $form->text('trade_sell_fee');
            $form->text('ts');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
