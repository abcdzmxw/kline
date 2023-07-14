<?php

namespace App\Admin\Controllers;

use App\Models\Coins;
use App\Models\ContractPair;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class ContractPairController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new ContractPair(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('symbol');
//            $grid->column('contract_coin_id');
//            $grid->column('contract_coin_name');
            $grid->column('type');
            $grid->column('unit_amount');
            $grid->column('maker_fee_rate');
            $grid->column('taker_fee_rate');
            $grid->column('lever_rage')->label();
            $grid->column('default_lever')->label('info');
//            $grid->column('min_qty');
//            $grid->column('max_qty');
//            $grid->column('total_max_qty');
            $grid->column('buy_spread');
            $grid->column('sell_spread');
            $grid->column('settle_spread');
            $grid->column('status')->switch();
            $grid->column('trade_status')->switch();
            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('symbol')->width(3);

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
        return Show::make($id, new ContractPair(), function (Show $show) {
            $show->field('id');
            $show->field('symbol');
            $show->field('contract_coin_id');
            $show->field('contract_coin_name');
            $show->field('type');
            $show->field('unit_amount');
            $show->field('maker_fee_rate');
            $show->field('taker_fee_rate');
            $show->field('status');
            $show->field('trade_status');
            $show->field('lever_rage');
            $show->field('min_qty');
            $show->field('max_qty');
            $show->field('total_max_qty');
            $show->field('buy_spread');
            $show->field('sell_spread');
            $show->field('settle_spread');
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
        return Form::make(new ContractPair(), function (Form $form) {
            $form->disableDeleteButton();

            $options = Coins::query()->where('status',1)->orderByDesc('coin_id')->pluck('coin_name','coin_id')->toArray();

            $form->display('id');
            $form->hidden('symbol');
            $form->select('contract_coin_id')->options($options);
            $form->hidden('contract_coin_name');
            $form->text('type')->default('USDT')->readOnly();
            $form->text('unit_amount')->default(1);
            $form->text('maker_fee_rate')->default(0.0005);
            $form->text('taker_fee_rate')->default(0.0005);
            $form->list('lever_rage')->min(1)->saving(function ($v) {
                return json_decode($v);
            });
            $form->text('default_lever');
            $form->text('min_qty')->required();
            $form->text('max_qty')->required();
            $form->text('total_max_qty')->required();
            $form->text('buy_spread');
            $form->text('sell_spread');
            $form->text('settle_spread');
            $form->switch('status')->default(1);
            $form->switch('trade_status')->default(1);

            $form->display('created_at');
            $form->display('updated_at');

            $form->saving(function (Form $form) use ($options){
                if($form->isCreating() || $form->isEditing()){
                    if(!blank($form->contract_coin_id)){
                        $contract_coin_id = $form->contract_coin_id;
                        $contract_coin_name = $options[$contract_coin_id];
                        $form->contract_coin_id = $contract_coin_id;
                        $form->contract_coin_name = $contract_coin_name;
                        $form->symbol = $contract_coin_name;
                    }
                }
            });

        });
    }
}
