<?php

namespace App\Admin\Controllers;

use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\InsideTradeBuy;
use App\Models\InsideTradePair;
use App\Models\InsideTradeSell;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Widgets\Table;

class InsideTradeSellController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(InsideTradeSell::with(['user','order_details']), function (Grid $grid) {
            $grid->model()->orderByDesc("id");

            $grid->disableActions();
            $grid->disableCreateButton();
            $grid->disableBatchDelete();
            $grid->disableDeleteButton();

            $grid->id->sortable();
            $grid->order_no;
            $grid->column('user.username','用户');
            $grid->symbol;
            $grid->type->using(InsideTradeSell::$typeMap)->label();
            $grid->entrust_price->display(function($v){
                return number_format($v, 8, '.', '');
            });
            $grid->trigger_price;
//            $grid->quote_coin_id;
//            $grid->base_coin_id;
            $grid->amount;
            $grid->traded_amount;
            $grid->money;
            $grid->traded_money;
            $grid->status->using(InsideTradeSell::$statusMap)->dot([
                1 => 'primary',
                2 => 'danger',
                3 => 'success',
                4 => 'info',
            ],'primary');
            $grid->column('order_details','明细')->display('明细') // 设置按钮名称
            ->expand(function () {
                // 返回显示的详情
                // 这里返回 content 字段内容，并用 Card 包裹起来
//                dd($this->order_details);
                $data = [];
                foreach ($this->order_details as $k=>$detail){
//                    $data[] = array_only($detail,['symbol','unit_price','trade_amount','trade_money','trade_buy_fee','trade_sell_fee']);
                    $data[$k]['symbol'] = $detail['symbol'];
                    $data[$k]['buy_order_no'] = $detail['buy_order_no'];
                    $data[$k]['sell_order_no'] = $detail['sell_order_no'];
                    $data[$k]['unit_price'] = $detail['unit_price'];
                    $data[$k]['trade_amount'] = $detail['trade_amount'];
                    $data[$k]['trade_money'] = $detail['trade_money'];
                    $data[$k]['trade_buy_fee'] = $detail['trade_buy_fee'];
                    $data[$k]['trade_sell_fee'] = $detail['trade_sell_fee'];
                }
                return Table::make(['币对','买单','卖单', '成交价', '交易量','交易额','买手续费','卖手续费'], $data);
            });
//            $grid->hang_status;
//            $grid->cancel_time;
            $grid->created_at;
//            $grid->updated_at->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id','UID')->width(2);
                $filter->where('username',function($q){
                    $username = $this->input;
                    $q->whereHas('user',function($q)use($username){
                        $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                    });
                },"用户名/手机/邮箱")->width(3);

                $filter->between('created_at',"时间")->datetime()->width(4);
                $filter->where('symbol',function ($q){
                    $q->where('symbol',$this->input);
                },'币对')->select(InsideTradePair::query()->where(['status'=>1,'trade_status'=>1])->pluck('pair_name','pair_name')->toArray())->width(3);
                $filter->where('status',function ($q){
                    $q->where('status',$this->input);
                },'状态')->select(InsideTradeSell::$statusMap)->width(3);
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
        return Show::make($id, new InsideTradeSell(), function (Show $show) {
            $show->id;
            $show->order_no;
            $show->user_id;
            $show->symbol;
            $show->type;
            $show->entrust_price;
            $show->trigger_price;
            $show->quote_coin_id;
            $show->base_coin_id;
            $show->amount;
            $show->traded_amount;
            $show->money;
            $show->traded_money;
            $show->status;
            $show->hang_status;
            $show->cancel_time;
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
        return Form::make(new InsideTradeSell(), function (Form $form) {
            $form->display('id');
            $form->text('order_no');
            $form->text('user_id');
            $form->text('symbol');
            $form->text('type');
            $form->text('entrust_price');
            $form->text('trigger_price');
            $form->text('quote_coin_id');
            $form->text('base_coin_id');
            $form->text('amount');
            $form->text('traded_amount');
            $form->text('money');
            $form->text('traded_money');
            $form->text('status');
            $form->text('hang_status');
            $form->text('cancel_time');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
