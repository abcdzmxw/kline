<?php

namespace App\Admin\Controllers;

use App\Models\Agent;
use App\Models\AgentGrade;
use App\Admin\Repositories\BonusLog as BonusLogRepository;
use App\Models\User;
use App\Models\UserWallet;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;

class BonusLogStatisticsController extends AdminController
{

    protected $title = '期权佣金统计';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new BonusLogRepository(), function (Grid $grid) {

            $grid->disableBatchDelete();
            $grid->disableActions();
            $grid->disableCreateButton();
            $grid->disableRowSelector();

            $grid->column('user_id','UID');
            $grid->column('user.username','用户名');
            $grid->column('user.user_grade_name','等级');
            $grid->column('user.referrer','代理')->display(function ($v){
                return User::query()->where("id",$v)->value("name") ?? '--';
            });
//            $grid->column('coin_name');
            $grid->column('amount_sum','佣金统计');
//
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

                $filter->equal('user_id', '用户id')->width(2);
                $filter->where('username',function($q){
                    $username = $this->input;
                    $q->whereHas('user',function($q)use($username){
                        $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                    });
                },"用户名/手机/邮箱")->width(4);
                $filter->between('created_at',"时间")->date()->width(4);
            });
        });
    }
}
