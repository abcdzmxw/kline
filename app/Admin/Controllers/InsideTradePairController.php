<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\InsideTradePair\Close;
use App\Admin\Actions\InsideTradePair\Open;
use App\Models\Coins;
use App\Models\InsideTradePair;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Tab;

class InsideTradePairController extends AdminController
{
//    public function index(Content $content)
//    {
//        $tab = Tab::make();
//
//        $quote_coins = array_unique(InsideTradePair::query()->pluck('quote_coin_name')->toArray());
//
//        $index = 1;
//        foreach ($quote_coins as $k => $quote_coin){
//            $tab_html = Grid::make(InsideTradePair::query()->where('quote_coin_name',$quote_coin), function (Grid $grid) {
//                $grid->model()->orderByDesc('pair_id');
//
//                $grid->disableBatchDelete();
//
//                $grid->tools([
//                    new Open(),
//                    new Close(),
//                ]);
//
//                $grid->pair_id->sortable();
//                $grid->pair_name;
//                $grid->symbol;
//                $grid->quote_coin_id;
//                $grid->quote_coin_name;
//                $grid->base_coin_id;
//                $grid->base_coin_name;
//                $grid->qty_decimals;
//                $grid->price_decimals;
//                $grid->min_qty;
//                $grid->min_total;
//                $grid->status->switch();
//                $grid->trade_status->switch();
//                $grid->created_at->sortable();
//
//                $grid->filter(function (Grid\Filter $filter) {
//                    $filter->equal('pair_id');
//                });
//            })->render();
//
//            // 第一个参数是选项卡标题，第二个参数是内容，第三个参数是是否选中
//            if($index == 1){
//                $tab->add($quote_coin, $tab_html, true);
//            }else{
//                $tab->add($quote_coin, $tab_html);
//            }
//            $index++;
//        }
//
//        return $content->body($tab->withCard());
//    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new InsideTradePair(), function (Grid $grid) {
            $grid->model()->orderBy('sort','asc');

            $grid->disableBatchDelete();
//            $grid->disableCreateButton();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
//                $actions->disableEdit();
                $actions->disableView();
            });

            $grid->tools([
                new Open(),
                new Close(),
            ]);

            $grid->pair_id->sortable();
            $grid->pair_name;
            $grid->symbol;
//            $grid->quote_coin_id;
            $grid->quote_coin_name;
//            $grid->base_coin_id;
            $grid->base_coin_name;
            $grid->qty_decimals;
            $grid->price_decimals;
            $grid->min_qty;
            $grid->min_total;
            $grid->column('buy_restricted','币币买入限制') ->display(function () {
                if($this->buy_restricted == 1){
                    return '限制';
                }else{
                    return '不限制';
                }
            });
            $grid->column('sell_restricted','币币卖出限制') ->display(function () {
                if($this->sell_restricted == 1){
                    return '限制';
                }else{
                    return '不限制';
                }
            });
            $grid->status->switch();
            $grid->trade_status->switch();
            $grid->sort->editable();
            $grid->created_at->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('base_coin_name')->width(2);
                $filter->equal('quote_coin_name')->width(2);

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
        return Show::make($id, new InsideTradePair(), function (Show $show) {
            $show->pair_id;
            $show->pair_name;
            $show->symbol;
            $show->quote_coin_name;
            $show->base_coin_name;
            $show->qty_decimals;
            $show->price_decimals;
            $show->min_qty;
            $show->min_total;
            $show->status;
            $show->sort;
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
        return Form::make(new InsideTradePair(), function (Form $form) {
            $form->disableDeleteButton();

            $options = Coins::query()->where('status',1)->orderByDesc('coin_id')->pluck('coin_name','coin_id')->toArray();

            $form->display('pair_id');
            $form->select('quote_coin_id')->options($options)->default(1);
            $form->select('base_coin_id')->options($options);
            $form->hidden('quote_coin_name');
            $form->hidden('base_coin_name');
            $form->hidden('pair_name');
            $form->hidden('symbol');
            $form->number('qty_decimals')->default(2);
            $form->number('price_decimals')->default(2);
            $form->number('min_qty')->default(1);
            $form->number('min_total')->default(1);
            $form->switch('status')->default(1);
            $form->switch('trade_status')->default(1);
            $form->number('sort')->default(255);
            $form->radio('buy_restricted','币币买入限制')->options(['1'=>'限制','2'=>'不限制'])->default($form->buy_restricted);
            $form->radio('sell_restricted','币币卖出限制')->options(['1'=>'限制','2'=>'不限制'])->default($form->sell_restricted);

            $form->display('created_at');
            $form->display('updated_at');

            $form->saving(function (Form $form) use ($options){
                if($form->isCreating() || $form->isEditing()){
                    if(!blank($form->quote_coin_id)){
                        $quote_coin_id = $form->quote_coin_id;
//                    dd($quote_coin_id);
                        $quote_coin_name = $options[$quote_coin_id];
                        $base_coin_id = $form->base_coin_id;
                        $base_coin_name = $options[$base_coin_id];
                        if($quote_coin_id == $base_coin_id){
                            return $form->error('参数错误~');
                        }
                        $form->quote_coin_id = $quote_coin_id;
                        $form->quote_coin_name = $quote_coin_name;
                        $form->base_coin_id = $base_coin_id;
                        $form->base_coin_name = $base_coin_name;
                        $form->pair_name = $base_coin_name . '/' . $quote_coin_name;
                        $form->symbol = strtolower($base_coin_name . $quote_coin_name);
                    }
                }
            });

        });
    }
}
