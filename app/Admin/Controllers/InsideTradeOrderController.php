<?php

namespace App\Admin\Controllers;

use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\Coins;
use App\Models\InsideTradeOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;

class InsideTradeOrderController extends AdminController
{

    public function statistics()
    {
        $builder1 = InsideTradeOrder::query();
        $params = request()->only(['user_id','username','created_at']);

        if(!empty($params)){
            if(!empty($params['user_id'])){
                $user_id = $params['user_id'];
                $builder1->where(function($q)use($user_id){
                    $q->where('buy_user_id',$user_id)->orWhere('sell_user_id',$user_id);
                });
            }
            if(!empty($params['username'])){
                $username = $params['username'];
                $builder1->where(function($q)use($username){
                    $q->whereHas('buy_user',function($q)use($username){
                        $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                    })->orWhereHas('sell_user',function($q)use($username){
                        $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                    });
                });
            }
            if(!empty($params['created_at']) && !empty($params['created_at']['start'])){
                $start = $params['created_at']['start'];
                $end = $params['created_at']['end'];
                $builder1->whereDate('created_at','>=',$start)->whereDate('created_at','<=',$end);
            }

        }
        $builder2 = $builder1;

// \DB::enableQueryLog();
        $records1 = $builder1->groupBy('base_coin_id')->selectRaw('sum(trade_buy_fee) as total_fee, base_coin_id as coin_id')->get();
        $records2 = $builder2->groupBy('quote_coin_id')->selectRaw('sum(trade_sell_fee) as total_fee, quote_coin_id as coin_id')->get();
        $tmp = $records1->concat($records2);
        // dd(\DB::getQueryLog());
        $records = [];
        foreach ($tmp as $k=>$v){
            if(!isset($records[$v['coin_id']])){
                $records[$v['coin_id']] = $v;
            }else{
                $records[$v['coin_id']]['total_fee'] += $v['total_fee'];
            }
        }
//        dd($records);
        $con = '';
        foreach ($records as $record){
            $coin_name = Coins::query()->where('coin_id',$record['coin_id'])->value('coin_name');
            $con .= '<code>'.$coin_name.'手续费：'.$record['total_fee'].'</code> ';
        }
        return Alert::make($con, '统计')->info();
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(InsideTradeOrder::with(['buy_user','sell_user']), function (Grid $grid) {
            $grid->model()->orderByDesc("order_id");

            #统计
            $grid->header(function ($query) {
                return $this->statistics();
            });

            $grid->disableActions();
            $grid->disableCreateButton();
            $grid->disableBatchDelete();
            $grid->disableDeleteButton();

            $grid->order_id->sortable();
            $grid->buy_order_no;
            $grid->sell_order_no;
            $grid->column('buy_user.username','买家');
            $grid->column('sell_user.username','卖家');
            $grid->unit_price;
            $grid->symbol;
            $grid->trade_amount;
            $grid->trade_money;
            $grid->trade_buy_fee->display(function($v){
                return $v . ' ' .str_before($this->symbol,'/');
            });
            $grid->trade_sell_fee->display(function($v){
                return $v . ' ' .str_after($this->symbol,'/');
            });
            $grid->created_at;
//            $grid->updated_at->sortable();

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
                },"用户名/手机/邮箱")->width(4);

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
        return Show::make($id, new InsideTradeOrder(), function (Show $show) {
            $show->order_id;
            $show->buy_order_no;
            $show->sell_order_no;
            $show->buy_user_id;
            $show->sell_user_id;
            $show->unit_price;
            $show->symbol;
            $show->trade_amount;
            $show->trade_money;
            $show->trade_buy_fee;
            $show->trade_sell_fee;
            $show->created_at;
            $show->updated_at;
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new InsideTradeOrder(), function (Form $form) {
            $form->display('order_id');
            $form->text('buy_order_no');
            $form->text('sell_order_no');
            $form->text('buy_user_id');
            $form->text('sell_user_id');
            $form->text('unit_price');
            $form->text('symbol');
            $form->text('trade_amount');
            $form->text('trade_money');
            $form->text('trade_buy_fee');
            $form->text('trade_sell_fee');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
