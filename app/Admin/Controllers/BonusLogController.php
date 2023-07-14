<?php

namespace App\Admin\Controllers;

use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\BonusLog;
use App\Models\UserWallet;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;

class BonusLogController extends AdminController
{

    public function statistics()
    {
        $grades = AgentGrade::getCachedGradeOption();
        $builder1 = BonusLog::query();
        $params = request()->only(array_merge($grades,['user_id','username','order_id','created_at']));

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
            if(!empty($params['order_id'])){
                $builder1->where('bonusable_id',$params['order_id']);
            }
            if(!empty($params['created_at']) && !empty($params['created_at']['start'])){
                $start = $params['created_at']['start'];
                $end = $params['created_at']['end'];
                $builder1->whereDate('created_at','>=',$start)->whereDate('created_at','<=',$end);
            }

            $lk = last(array_keys($grades));
            foreach ($grades as $k=>$v){
                $key = 'A' . ($k+1);
                if ( $k == $lk && !empty($params[$key]) ){
                    $id = $params[$key];
                    $builder1->whereHas('user',function($q)use($id){
                        $q->where('referrer',$id);
                    });
                }elseif( !empty($params[$key]) ){
                    $ids = Agent::getBaseAgentIds($params[$key]);
                    $builder1->whereHas('user',function($q)use($ids){
                        $q->whereIn('referrer',$ids);
                    });
                }
            }
        }

        $total_amount = $builder1->where('status',BonusLog::status_hand)->sum('amount');

        $con = '<code>'.'总金额：'.(real)$total_amount.'USDT</code> ';
        return Alert::make($con, '统计')->info();
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(BonusLog::with(['user']), function (Grid $grid) {
            $grid->model()->orderByDesc('id');

            #统计
            $grid->header(function ($query) {
                return $this->statistics();
            });

            $grid->disableBatchDelete();
            $grid->disableActions();
            $grid->disableCreateButton();
            $grid->disableRowSelector();

            $grid->column('id')->sortable();
            $grid->column('user_id');
            $grid->column('user.username','用户名');
//            $grid->column('coin_id');
            $grid->column('coin_name');
            $grid->column('account_type')->display(function($v){
                $account = array_first(UserWallet::$accountMap,function ($value, $key) use ($v) {
                    return $value['id'] == $v;
                });
                return $account['name'] ?? '--';
            });
//            $grid->column('rich_type');
            $grid->column('amount');
//            $grid->column('log_type');
            $grid->column('status')->using(BonusLog::$statusMap)->dot([-1=>'default',1=>'primary',2=>'success']);
            $grid->column('hand_time');
//            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();

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
                $filter->equal('order_id', '订单ID')->width(2);
                $filter->between('created_at',"时间")->datetime()->width(4);

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
        return Show::make($id, new BonusLog(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('coin_id');
            $show->field('coin_name');
            $show->field('account_type');
            $show->field('rich_type');
            $show->field('amount');
            $show->field('log_type');
            $show->field('status');
            $show->field('hand_time');
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
        return Form::make(new BonusLog(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('coin_id');
            $form->text('coin_name');
            $form->text('account_type');
            $form->text('rich_type');
            $form->text('amount');
            $form->text('log_type');
            $form->text('status');
            $form->text('hand_time');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
