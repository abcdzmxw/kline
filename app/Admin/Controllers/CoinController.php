<?php

namespace App\Admin\Controllers;

use App\Models\Coins;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Card;

class CoinController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Coins(), function (Grid $grid) {
            $grid->model()->orderByRaw("FIELD(status," . implode(",", [1,0]) . ")")->orderByDesc('coin_id');

            $grid->disableDeleteButton();
            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->disableViewButton();

            $grid->coin_id->sortable();
            $grid->coin_name;
            // $grid->full_name;
            // $grid->qty_decimals;
            // $grid->price_decimals;
            $grid->withdrawal_fee;
            $grid->withdrawal_min;
            $grid->withdrawal_max;

            $grid->publish_time;
            $grid->total_issuance;
            $grid->total_circulation;

            $grid->column('buy_restricted','闪兑买入限制') ->display(function () {
                if($this->buy_restricted == 1){
                    return '限制';
                }else{
                    return '不限制';
                }

                return "<div style='padding:10px 10px 0'>$card</div>";
            });
            $grid->column('sell_restricted','闪兑卖出限制') ->display(function () {
                if($this->sell_restricted == 1){
                    return '限制';
                }else{
                    return '不限制';
                }
                return "<div style='padding:10px 10px 0'>$card</div>";
            });

            // $grid->coin_withdraw_message;
            // $grid->coin_recharge_message;
            // $grid->coin_transfer_message;
            $grid->coin_content->display('详情') // 设置按钮名称
            ->expand(function () {
                // 返回显示的详情
                // 这里返回 content 字段内容，并用 Card 包裹起来
                $card = new Card(null, $this->coin_content);

                return "<div style='padding:10px 10px 0'>$card</div>";
            });
            $grid->coin_icon->image('',50,50);
            $grid->status->using([0=>'禁用',1=>'启用'])->dot([0=>'danger',1=>'success']);
            $grid->created_at->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('coin_id')->width(3);
                $filter->like('coin_name')->width(3);

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
        return Show::make($id, new Coins(), function (Show $show) {
            $show->coin_id;
            $show->coin_name;
            $show->qty_decimals;
            $show->price_decimals;
            $show->full_name;
            $show->withdrawal_fee;
            $show->coin_withdraw_message;
            $show->coin_recharge_message;
            $show->coin_transfer_message;
            $show->coin_content;
            $show->coin_icon;
            $show->status;
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
        return Form::make(new Coins(), function (Form $form) {
            $form->display('coin_id');
            $form->text('coin_name');
            $form->text('full_name');
            $form->number('qty_decimals')->default(2);
            $form->number('price_decimals')->default(2);
            $form->text('withdrawal_fee');
            $form->text('withdrawal_min');
            $form->text('withdrawal_max');

            $form->date('publish_time');
            $form->text('total_issuance');
            $form->text('total_circulation');

            $form->text('coin_withdraw_message');
            $form->text('coin_recharge_message');
            $form->text('coin_transfer_message');
            $form->editor('coin_content');
            $form->image('coin_icon');
            $form->switch('status')->default(1);

            $form->radio('buy_restricted','闪兑买入限制')->options(['1'=>'限制','2'=>'不限制'])->default($form->buy_restricted);
            $form->radio('sell_restricted','闪兑卖出限制')->options(['1'=>'限制','2'=>'不限制'])->default($form->sell_restricted);

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
