<?php

namespace App\Admin\Controllers;

use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\OptionSceneOrder;
use App\Models\User;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Admin;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;

class OptionSettlementController extends AdminController
{
    protected $title = '期权结算';

    public function statistics()
    {
        $grades = AgentGrade::getCachedGradeOption();
        // 期权
        $option = OptionSceneOrder::query()->whereHas('user',function ($q){
            $q->where('is_system',0);
        });

        $params = request()->only(array_merge($grades,['user_id','created_at']));

        if(!empty($params)){
            if(!empty($params['user_id'])){
                $option->where('user_id',$params['user_id']);
            }
            if(!empty($params['created_at']) && !empty($params['created_at']['start'])){
                $option->whereDate('created_at','>=',$params['created_at']['start'])->whereDate('created_at','<=',$params['created_at']['end']);
            }

            $lk = last(array_keys($grades));
            foreach ($grades as $k=>$v){
                $key = 'A' . ($k+1);
                if ( $k == $lk && !empty($params[$key]) ){
                    $id = $params[$key];
                    $option->whereHas('user',function($q)use($id){
                        $q->where('referrer',$id);
                    });
                }elseif( !empty($params[$key]) ){
                    $ids = Agent::getBaseAgentIds($params[$key]);
                    $option->whereHas('user',function($q)use($ids){
                        $q->whereIn('referrer',$ids);
                    });
                }
            }
        }

        $usdt_amount    = (clone $option)->where('bet_coin_name','USDT')->sum('delivery_amount');
        $con = '<code>金额：'.(real)$usdt_amount.'USDT</code> ';

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

            $grid->column('delivery_amount_usdt','金额(USDT)')->display(function (){
                return OptionSceneOrder::query()->where("user_id",$this->user_id)->where('bet_coin_name','USDT')->sum("delivery_amount");
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
