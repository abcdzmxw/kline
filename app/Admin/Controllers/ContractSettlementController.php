<?php

namespace App\Admin\Controllers;

use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\ContractEntrust;
use App\Models\ContractOrder;
use App\Models\Recharge;
use App\Models\SustainableAccount;
use App\Models\UserWallet;
use App\Models\UserWalletLog;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Admin;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;

class ContractSettlementController extends AdminController
{
    protected $title = '合约结算';

    public function statistics()
    {
        $grades = AgentGrade::getCachedGradeOption();
        // 盈亏
        $builder1 = ContractEntrust::query()->where('order_type',2)->whereHas('user',function ($q){
            $q->where('is_system',0);
        });
        // 手续费
        $builder2 = UserWalletLog::query()->where('rich_type','usable_balance')
            ->where('account_type',UserWallet::sustainable_account)
            ->whereHas('user',function ($q){
                $q->where('is_system',0);
            })
            ->whereIn('log_type',['open_position_fee','close_position_fee','system_close_position_fee','cancel_open_position_fee']);
        // 资金费
        $builder3 = UserWalletLog::query()->where('rich_type','usable_balance')
            ->where('account_type',UserWallet::sustainable_account)
            ->where('log_type','position_capital_cost')
            ->whereHas('user',function ($q){
                $q->where('is_system',0);
            });

        $params = request()->only(array_merge($grades,['user_id','created_at']));

        if(!empty($params)){
            if(!empty($params['user_id'])){
                $builder1->where('user_id',$params['user_id']);
                $builder2->where('user_id',$params['user_id']);
                $builder3->where('user_id',$params['user_id']);
            }
            if(!empty($params['created_at']) && !empty($params['created_at']['start'])){
                $builder1->whereDate('created_at','>=',$params['created_at']['start'])->whereDate('created_at','<=',$params['created_at']['end']);
                $builder2->whereDate('created_at','>=',$params['created_at']['start'])->whereDate('created_at','<=',$params['created_at']['end']);
                $builder3->whereDate('created_at','>=',$params['created_at']['start'])->whereDate('created_at','<=',$params['created_at']['end']);
            }

            $lk = last(array_keys($grades));
            foreach ($grades as $k=>$v){
                $key = 'A' . ($k+1);
                if ( $k == $lk && !empty($params[$key]) ){
                    $id = $params[$key];
                    $builder1->whereHas('user',function($q)use($id){
                        $q->where('referrer',$id);
                    });
                    $builder2->whereHas('user',function($q)use($id){
                        $q->where('referrer',$id);
                    });
                    $builder3->whereHas('user',function($q)use($id){
                        $q->where('referrer',$id);
                    });
                }elseif( !empty($params[$key]) ){
                    $ids = Agent::getBaseAgentIds($params[$key]);
                    $builder1->whereHas('user',function($q)use($ids){
                        $q->whereIn('referrer',$ids);
                    });
                    $builder2->whereHas('user',function($q)use($ids){
                        $q->whereIn('referrer',$ids);
                    });
                    $builder3->whereHas('user',function($q)use($ids){
                        $q->whereIn('referrer',$ids);
                    });
                }
            }
        }

        $total_profit = $builder1->where('status',ContractEntrust::status_completed)->sum('profit');
        $total_fee = $builder2->sum('amount');
        $total_cost = $builder3->sum('amount');
        $total_amount = abs($total_fee) + abs($total_cost) - $total_profit;

        $con = '<code>总手续费：'.(real)abs($total_fee).'USDT</code> ' . '<code>总资金费：'.(real)abs($total_cost).'USDT</code> ' . '<code>总盈亏：'.(real)$total_profit.'USDT</code> ' . '<code>总业绩：'.(real)$total_amount.'USDT</code> ';
        return Alert::make($con, '统计')->info();
    }

    /**
     * 代理结算
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(SustainableAccount::with(['user']), function (Grid $grid) {
            $grid->model()->orderByDesc('user_id');

            #统计
            $grid->header(function ($query) {
                return $this->statistics();
            });

//            $grid->column('id')->sortable();

            $grid->disableActions();
            $grid->disableCreateButton();
            $grid->disableRowSelector();

//            $grid->column('user.referrer','A5')->display(function($referrer){
//                $agent = Agent::query()->where('is_agency',1)->where("id",$referrer)->first();
//                if(blank($agent)){
//                    return '--';
//                }else{
//                    $parent = $agent->parent->parent->parent->parent;
//                    return blank($parent) ? "--" : $parent['name'];
//                }
//            });
            $grid->column('user_id','UID');

            $grid->column('user.referrer','代理ID');

            $grid->column('contract_fee','手续费')->display(function(){
                return abs(UserWalletLog::query()
                    ->where('user_id',$this->user_id)
                    ->where('rich_type','usable_balance')
                    ->where('account_type',UserWallet::sustainable_account)
                    ->whereIn('log_type',['open_position_fee','close_position_fee','system_close_position_fee','cancel_open_position_fee'])
                    ->sum('amount'));
            });
            $grid->column('contract_cost','资金费')->display(function(){
                return abs(UserWalletLog::query()
                    ->where('user_id',$this->user_id)
                    ->where('rich_type','usable_balance')
                    ->where('account_type',UserWallet::sustainable_account)
                    ->where('log_type','position_capital_cost')
                    ->sum('amount'));
            });
            $grid->column('contract_profit','盈亏')->display(function(){
                return ContractEntrust::query()
                    ->where('user_id',$this->user_id)
                    ->where('order_type',2)
                    ->where('status',ContractEntrust::status_completed)
                    ->sum('profit');
            });

            $grid->column('total_amount','总业绩')->display(function(){
                $fee = UserWalletLog::query()
                    ->where('user_id',$this->user_id)
                    ->where('rich_type','usable_balance')
                    ->where('account_type',UserWallet::sustainable_account)
                    ->whereIn('log_type',['open_position_fee','close_position_fee','system_close_position_fee','cancel_open_position_fee'])
                    ->sum('amount');
                $cost = UserWalletLog::query()
                    ->where('user_id',$this->user_id)
                    ->where('rich_type','usable_balance')
                    ->where('account_type',UserWallet::sustainable_account)
                    ->where('log_type','position_capital_cost')
                    ->sum('amount');
                $profit = ContractEntrust::query()
                    ->where('user_id',$this->user_id)
                    ->where('order_type',2)
                    ->where('status',ContractEntrust::status_completed)
                    ->sum('profit');

                return abs($fee) + abs($cost) - $profit;
            });

            $grid->filter(function (Grid\Filter $filter) {
                $grades = AgentGrade::getCachedGradeOption();
                $lk = last(array_keys($grades));
                foreach ($grades as $k=>$v){
                    $key = 'A' . ($k+1);
                    $next_key = 'A' . ($k+2);
                    if($k == 0){
                        $options1 = Agent::query()->where(['deep'=>0,'is_agency'=>1])->pluck('username','id');
                        $filter->where($key,function ($q){
                            $ids = Agent::getBaseAgentIds($this->input);
                            $q->whereHas('user',function($q)use($ids){
                                $q->whereIn('referrer',$ids);
                            });
                        },$v)->select($options1)->load($next_key,'api/agents')->placeholder('请选择')->width(2);
                    }elseif($k == $lk){
                        $filter->where($key,function ($q){
                            $id = $this->input;
                            $q->whereHas('user',function($q)use($id){
                                $q->where('referrer',$id);
                            });
                        },$v)->select()->placeholder('请选择')->width(2);
                    }else{
                        $filter->where($key,function ($q){
                            $ids = Agent::getBaseAgentIds($this->input);
                            $q->whereHas('user',function($q)use($ids){
                                $q->whereIn('referrer',$ids);
                            });
                        },$v)->select()->load($next_key,'api/agents')->placeholder('请选择')->width(2);
                    }
                }

                $filter->equal('user_id','UID')->width(2);
                $filter->whereBetween('created_at',function($q){},"时间")->date()->width(4);
            });
        });
    }

}
