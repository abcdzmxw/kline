<?php

namespace App\Admin\Controllers;

use App\Models\FlashExchange;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
// 小白  购买订单
class FlashOrderController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new FlashExchange(), function (Grid $grid) {

            $grid->disableDeleteButton();
            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->disableEditButton();

            $grid->model()->orderByDesc('id');
            $grid->column('id')->sortable();
            $grid->column('user_id','用户ID');
            $grid->column('from_coin_id','从币种 ID');
            $grid->column('from_coin_name','从币种 名称');
            $grid->column('to_coin_id','至币种 ID');
            $grid->column('to_coin_name','至币种 名称');
            $grid->column('amount','转移数量');
            $grid->column('hang_price','汇率');
            $grid->column('to_amount','转移后数量');
            $grid->column('to_amount_rate','手续费比例');
            $grid->column('to_amount_fee','手续费');
            $grid->column('created_at');


            $grid->filter(function (Grid\Filter $filter) {

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
        return Show::make($id, new FlashExchange(), function (Show $show) {

            $show->field('id')->sortable('desc');
            $show->field('user_id','用户ID');
            $show->field('from_coin_id','从币种 ID');
            $show->field('from_coin_name','从币种 名称');
            $show->field('to_coin_id','至币种 ID');
            $show->field('to_coin_name','至币种 名称');
            $show->field('amount','转移数量');
            $show->field('hang_price','汇率');
            $show->field('to_amount','转移后数量');
            $show->field('to_amount_rate','手续费比例');
            $show->field('to_amount_fee','手续费');
            $show->field('created_at');
        });
    }

}
