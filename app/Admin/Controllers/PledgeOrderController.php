<?php

namespace App\Admin\Controllers;

use App\Models\Coins;
use App\Models\PledgeOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class PledgeOrderController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new PledgeOrder(), function (Grid $grid) {
            $grid->disableBatchDelete();
            $grid->disableActions();
            $grid->disableCreateButton();
//            $grid->actions(function (Grid\Displayers\Actions $actions) {
//                $actions->disableDelete();
//                $actions->disableEdit();
//                $actions->disableQuickEdit();
//            });
            $grid->model()->orderByDesc("id");

            $grid->id->sortable();
            $grid->order_no;
            $grid->user_id;
            $grid->coin_name;
            $grid->cycle;
            $grid->rate;
            $grid->num;
            $grid->reward;
            $grid->total;
            $grid->column('status', '状态')->using(PledgeOrder::$statusMap)->dot([0 => 'danger', 1 => 'success']);
//            $grid->status->switch();
            //$grid->switch('status', '是否上架');
            $grid->created_at->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id')->width(2);
                $filter->where('status',function ($q){
                    $q->where('status',$this->input);
                },'状态')->select(PledgeOrder::$statusMap)->width(2);
            });
        });
    }
}
