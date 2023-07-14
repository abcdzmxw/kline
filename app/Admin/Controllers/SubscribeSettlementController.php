<?php

namespace App\Admin\Controllers;

use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\Recharge as RechargeModel;
use App\Models\UserSubscribeRecord;
use App\Models\Withdraw as WithdrawModel;
use App\Models\User;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Admin;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;

class SubscribeSettlementController extends AdminController
{
    protected $title = '申购结算';

    public function statistics()
    {
        $grades = AgentGrade::getCachedGradeOption();
        // 申购
        $subscribe = UserSubscribeRecord::query()->whereHas('user',function ($q){
            $q->where('is_system',0);
        });

        $params = request()->only(array_merge($grades,['user_id','created_at']));

        if(!empty($params)){
            if(!empty($params['user_id'])){
                $subscribe->where('user_id',$params['user_id']);
            }
            if(!empty($params['created_at']) && !empty($params['created_at']['start'])){
                $start = $params['created_at']['start'] ? strtotime($params['created_at']['start']) : null;
                $end = $params['created_at']['end'] ? strtotime($params['created_at']['end']) : null;
                $subscribe->whereBetween('subscription_time',[$start,$end+86399]);
            }

            $lk = last(array_keys($grades));
            foreach ($grades as $k=>$v){
                $key = 'A' . ($k+1);
                if ( $k == $lk && !empty($params[$key]) ){
                    $id = $params[$key];
                    $subscribe->whereHas('user',function($q)use($id){
                        $q->where('referrer',$id);
                    });
                }elseif( !empty($params[$key]) ){
                    $ids = Agent::getBaseAgentIds($params[$key]);
                    $subscribe->whereHas('user',function($q)use($ids){
                        $q->whereIn('referrer',$ids);
                    });
                }
            }
        }

        $usdt_amount    = (clone $subscribe)->where('payment_currency','USDT')->sum('payment_amount');
        $con = '<code>申购金额：'.(real)$usdt_amount.'USDT</code> ';

        return Alert::make($con, '统计')->info();
    }

    protected function grid()
    {
        $builder = User::query()->where('is_system',0)->where('is_agency',0);
        return Grid::make($builder, function (Grid $grid) {
            $grid->model()->orderByDesc('user_id');

            #统计
            $grid->header(function ($query) {
                return $this->statistics();
            });

            $grid->disableActions();
            $grid->disableCreateButton();
            $grid->disableRowSelector();

            $grid->column('referrer','代理ID');
            $grid->column('user_id','UID');

            $grid->column('pay_usdt_amount','申购金额(USDT)')->display(function (){
                return UserSubscribeRecord::query()->where("user_id",$this->user_id)->where('payment_currency','USDT')->sum("payment_amount");
            });
            $grid->column('subscribe_amount','申购数量('. config('coin.coin_symbol') .')')->display(function (){
                return UserSubscribeRecord::query()->where("user_id",$this->user_id)->sum("subscription_currency_amount");
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
                            $q->whereIn('referrer',$ids);
                        },$v)->select($options1)->load($next_key,'api/agents')->placeholder('请选择')->width(2);
                    }elseif($k == $lk){
                        $filter->where($key,function ($q){
                            $id = $this->input;
                            $q->where('referrer',$id);
                        },$v)->select()->placeholder('请选择')->width(2);
                    }else{
                        $filter->where($key,function ($q){
                            $ids = Agent::getBaseAgentIds($this->input);
                            $q->whereIn('referrer',$ids);
                        },$v)->select()->load($next_key,'api/agents')->placeholder('请选择')->width(2);
                    }
                }

                $filter->equal('user_id','UID')->width(2);
                $filter->whereBetween('created_at',function($q){},"时间")->date()->width(4);
            });
        });
    }

}
