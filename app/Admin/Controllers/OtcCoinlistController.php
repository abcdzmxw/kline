<?php

namespace App\Admin\Controllers;

use App\Models\Coins;
use App\Models\OtcCoinlist;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class OtcCoinlistController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new OtcCoinlist(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('coin_id');
            $grid->column('coin_name');
            $grid->column('limit_amount');
            $grid->column('max_register_time');
            $grid->column('max_register_num');
            $grid->column('status')->switch();
            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('coin_name')->width(2);

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
        return Show::make($id, new OtcCoinlist(), function (Show $show) {
            $show->field('id');
            $show->field('coin_id');
            $show->field('coin_name');
            $show->field('limit_amount');
            $show->field('max_register_time');
            $show->field('max_register_num');
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
        return Form::make(new OtcCoinlist(), function (Form $form) {

            $options = Coins::query()->where('status',1)->orderByDesc('coin_id')->pluck('coin_name','coin_id')->toArray();

            $form->display('id');
            $form->select('coin_id','币种')->options($options)->required();
            $form->hidden('coin_name');
            $form->text('limit_amount')->default(1)->required();
            $form->text('max_register_time')->default(10)->required();
            $form->text('max_register_num')->default(5)->required();
            $form->switch('status')->default(1);

            $form->display('created_at');
            $form->display('updated_at');

            $form->saving(function (Form $form) use ($options){
                if($form->isCreating() || $form->isEditing()){
                    if(!blank($form->coin_id)){
                        $form->coin_name = $options[$form->coin_id];
                    }
                }
            });
        });
    }
}
