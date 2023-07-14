<?php

namespace App\Admin\Controllers;

use App\Models\InsideTradeDealRobot;
use App\Models\InsideTradePair;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Illuminate\Support\Facades\Cache;

class InsideTradeDealRobotController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new InsideTradeDealRobot(), function (Grid $grid) {

            $grid->column('id')->sortable();
//            $grid->column('pair_id');
            $grid->column('symbol')->badge();

            $grid->column('bid_price_range','买单成交价格区间')->display(function(){
                $key = 'market:' . strtolower(str_before($this->symbol,'/') . str_after($this->symbol,'/')) . '_newPrice';
                $data = Cache::store('redis')->get($key);
                if(blank($data)){
                    return '--';
                }else{
                    $min = $data['price'] - ($this->bid_minus_unit * $this->bid_minus_count);
                    $max = $data['price'] + ($this->bid_plus_unit * $this->bid_plus_count);
                    return $min . '　~　' . $max;
                }
            })->label('success');
            $grid->column('ask_price_range','卖单成交价格区间')->display(function(){
                $key = 'market:' . strtolower(str_before($this->symbol,'/') . str_after($this->symbol,'/')) . '_newPrice';
                $data = Cache::store('redis')->get($key);
                if(blank($data)){
                    return '--';
                }else{
                    $min = $data['price'] - ($this->ask_minus_unit * $this->ask_minus_count);
                    $max = $data['price'] + ($this->ask_plus_unit * $this->ask_plus_count);
                    return $min . '　~　' . $max;
                }
            })->label('danger');

//            $grid->column('bid_plus_unit');
//            $grid->column('bid_plus_count');
//            $grid->column('bid_minus_unit');
//            $grid->column('bid_minus_count');
//            $grid->column('ask_plus_unit');
//            $grid->column('ask_plus_count');
//            $grid->column('ask_minus_unit');
//            $grid->column('ask_minus_count');

            $grid->column('status')->switch();

            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('symbol')->width(3);

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
        return Show::make($id, new InsideTradeDealRobot(), function (Show $show) {
            $show->field('id');
            $show->field('pair_id');
            $show->field('symbol');
            $show->field('bid_plus_unit');
            $show->field('bid_plus_count');
            $show->field('bid_minus_unit');
            $show->field('bid_minus_count');
            $show->field('ask_plus_unit');
            $show->field('ask_plus_count');
            $show->field('ask_minus_unit');
            $show->field('ask_minus_count');
            $show->field('status');
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
        return Form::make(new InsideTradeDealRobot(), function (Form $form) {
            $options = InsideTradePair::query()->where('status',1)->pluck('pair_name','pair_id')->toArray();

            if($form->isCreating()){
                $form->row(function (Form\Row $form) use ($options){
                    $form->hidden('id');
                    $form->width(6)->select('pair_id','交易对')->options($options);
                    $form->hidden('symbol');
                });
            }else{
                $form->row(function (Form\Row $form) use ($options){
                    $form->hidden('id');
                    $form->width(6)->select('pair_id','交易对')->options($options)->readOnly();
                    $form->hidden('symbol');
                });
            }

            $form->row(function (Form\Row $form) {
                $form->width(3)->text('bid_plus_unit');
                $form->width(3)->text('bid_plus_count');
            });

            $form->row(function (Form\Row $form) {
                $form->width(3)->text('bid_minus_unit');
                $form->width(3)->text('bid_minus_count');
            });

            $form->row(function (Form\Row $form) {
                $form->width(3)->text('ask_plus_unit');
                $form->width(3)->text('ask_plus_count');
            });

            $form->row(function (Form\Row $form) {
                $form->width(3)->text('ask_minus_unit');
                $form->width(3)->text('ask_minus_count');
            });

            $form->row(function (Form\Row $form) {
                $form->switch('status')->default(0);

                $form->hidden('created_at');
                $form->hidden('updated_at');
            });

            $form->saving(function (Form $form) use ($options){
                if($form->isCreating()){
                    if(!blank($form->pair_id)){
                        $symbol = $options[$form->pair_id];
                        $is_exist = InsideTradeDealRobot::query()->where('symbol',$symbol)->first();
                        if($is_exist) return $form->error('该交易对已存在任务~');

                        $form->symbol = $symbol;
                    }
                }
            });

        });
    }
}
