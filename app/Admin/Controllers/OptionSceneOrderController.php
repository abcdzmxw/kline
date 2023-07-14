<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\OptionSceneOrder\Control;
use App\Admin\Actions\OptionSceneOrder\Handle;
use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\BonusLog;
use App\Models\OptionSceneOrder;
use App\Models\User;
use Dcat\Admin\Actions\Action;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;

class OptionSceneOrderController extends AdminController
{

    public function statistics()
    {
        $builder1 = $builder2 = $builder3 = OptionSceneOrder::query()->whereHas('user',function ($q){
            $q->where('is_system',0);
        });

        // 矿工佣金
        $builder9 = BonusLog::query()->where('status',BonusLog::status_hand)->whereHas('user',function ($q){
            $q->where('is_system',0);
        });

        $params = request()->only(['user_id','username','order_no','created_at']);

        if(!empty($params)){
            if(!empty($params['user_id'])){
                $builder1->where('user_id',$params['user_id']);
                $builder2->where('user_id',$params['user_id']);
                $builder3->where('user_id',$params['user_id']);
                $builder9->where('user_id',$params['user_id']);
            }
            if(!empty($params['username'])){
                $username = $params['username'];
                $builder1->whereHas('user',function($q)use($username){
                    $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                });
                $builder2->whereHas('user',function($q)use($username){
                    $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                });
                $builder3->whereHas('user',function($q)use($username){
                    $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                });
                $builder9->whereHas('user',function($q)use($username){
                    $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                });
            }
            if(!empty($params['order_no'])){
                $builder1->where('order_no','like',$params['order_no']);
                $builder2->where('order_no','like',$params['order_no']);
                $builder3->where('order_no','like',$params['order_no']);
            }
            if(!empty($params['created_at']) && !empty($params['created_at']['start'])){
                $start = $params['created_at']['start'];
                $end = $params['created_at']['end'];
                $builder1->whereDate('created_at','>=',$start)->whereDate('created_at','<=',$end);
                $builder2->whereDate('created_at','>=',$start)->whereDate('created_at','<=',$end);
                $builder3->whereDate('created_at','>=',$start)->whereDate('created_at','<=',$end);
                $builder9->whereDate('created_at','>=',$start)->whereDate('created_at','<=',$end);
            }

        }

        $res1 = $builder1->where('status',OptionSceneOrder::status_delivered)->where('delivery_amount','>',0)->sum('delivery_amount');
        $res2 = $builder2->where('status',OptionSceneOrder::status_delivered)->where('delivery_amount','>',0)->sum('bet_amount');
        $res3 = $builder3->where('status',OptionSceneOrder::status_delivered)->where('delivery_amount','<',0)->sum('delivery_amount');

        //总盈利 结算为负数即用户亏损平台盈利
        $p = abs($res3);
        //总亏损
        $l = $res1 - $res2;
        $pl = $p - $l;

        $bonus_count = $builder9->sum('amount');

        // $con = '<code>'.'总盈亏：'. $pl .'USDT</code> ' . '<code>'.'矿工佣金：'. $bonus_count .'USDT</code> ' . '<code>'.'实际盈亏：'. PriceCalculate($pl ,'-',$bonus_count,8) .'USDT</code> ';
        $con = '<code>'.'总盈亏：'. $pl .'USDT</code> ';
        return Alert::make($con, '统计')->info();
    }

    /**
     *期权订单
     *
     * @return Grid
     */
    protected $title = "期权订单";

    protected function grid()
    {
        return Grid::make(OptionSceneOrder::with(['user','scene']), function (Grid $grid) {
            $grid->model()->orderByDesc("created_at");

            #统计
            $grid->header(function ($query) {
                return $this->statistics();
            });

//            $grid->fixColumns(1);

//            $grid->disableActions();
//            $grid->disableBatchDelete();
//            $grid->disableCreateButton();

            // TODO 异常订单处理
//            $grid->tools([ new Handle() ]);
            // 作弊控制
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableView();
                $actions->disableQuickEdit();
                $actions->disableEdit();

                if($actions->row->status != 2) {
                    $actions->append(new Control());
                }
            });

            $grid->order_id->sortable();
//            $grid->column("order_no","订单号");
            $grid->user_id;
            $grid->column('user.referrer','代理')->display(function($v){
                return Agent::query()->where('id',$v)->value('name');
            });

            $grid->column("交易对")->display(function(){
                return $this->pair_name . '-' . $this->time_name;
            });

            $grid->bet_amount;
            $grid->bet_coin_name;
            $grid->odds;
            $grid->range;
            $grid->column("涨跌平")->display(function(){
                if( $this->up_down == "1"){
                    return "<span style='color:red'>涨</span>";
                }else if( $this->up_down == "2"){
                    return "<span style='color:green'>跌</span>";
                }elseif($this->up_down == 3){
                    return "<span style='color:dodgerblue'>平</span>";
                }
            });

            $grid->status->using(OptionSceneOrder::$statusMap)->dot([1=>'primary',2=>'success']);
            $grid->fee->display(function($v){
                return $v . ' ' . $this->bet_coin_name;
            });
            $grid->delivery_amount;
            $grid->delivery_time->display(function ($v){
                return blank($v) ? null : date("Y-m-d H:i:s",$v);
            });
            $grid->column('scene.begin_price',"开盘价");
            $grid->column('scene.end_price',"收盘价")->display(function($v){
                return $this->end_price ?: $v;
            });
            $grid->column('scene.delivery_up_down',"<span style='color: red'>涨</span><span style='color: darkgreen'>跌</span><span style='color: #0d77e4'>平</span>")->display(function($d){
                if(!blank($this->end_price)){
                    if ($this->scene['begin_price'] < $this->end_price) {
                        return "<span style='color:red'>涨 </span>";
                    } elseif ($this->scene['begin_price'] == $this->end_price) {
                        return "<span style='color:dodgerblue'>平 </span>";
                    } else {
                        return "<span style='color:green'>跌 </span>";
                    }
                }
                if( $d == 1){
                    return "<span style='color:red'>涨 </span>";
                }else if( $d == 2){
                    return "<span style='color:green'>跌 </span>";
                }elseif($d == 3){
                    return "<span style='color:dodgerblue'>平 </span>";
                }
            });

            $grid->created_at->sortable();

            $grid->filter(function(Grid\Filter $filter){
                $filter->equal('user_id', '用户id')->width(2);
                $filter->where('username',function($q){
                    $username = $this->input;
                    $q->whereHas('user',function($q)use($username){
                        $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                    });
                },"用户名/手机/邮箱")->width(4);
//                $filter->like('order_no', '订单号')->width(3);
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
        return Show::make($id, new OptionSceneOrder(), function (Show $show) {
            $show->order_id;
            $show->order_no;
            $show->user_id;
            $show->bet_amount;
            $show->bet_coin_name;
            $show->odds;
            $show->range;
            $show->up_down;
            $show->status;
            $show->fee;
            $show->delivery_amount;
            $show->delivery_time;
            $show->created_at;
            $show->updated_at;
            $show->panel()
                ->tools(function ($tools) {
                    $tools->disableEdit();
                    //$tools->disableList();
                    $tools->disableDelete();
                });
        });

    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new OptionSceneOrder(), function (Form $form) {
            $form->display('order_id');
            $form->text('order_no');
            $form->text('user_id');
            $form->text('bet_amount');
            $form->text('bet_coin_name');
            $form->text('odds');
            $form->text('range');
            $form->text('up_down');
            $form->text('status');
            $form->text('fee');
            $form->text('delivery_amount');
            $form->text('delivery_time');
            $form->display('created_at');
            $form->display('updated_at');

            if ($form->isCreating()) {

            }

            if ($form->isEditing()) {

                $form->saved(function (Form $form) {

                });
            }


        });
    }
}
