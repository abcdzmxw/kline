<?php

namespace App\Admin\Controllers;

use App\Models\FlashExchangeConfig;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
// 小白  购买配置
class FlashConfigController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new FlashExchangeConfig(), function (Grid $grid) {

            $grid->disableDeleteButton();
            $grid->disableRowSelector();
            $grid->disableCreateButton();

            $grid->column('id')->sortable();
            $grid->column('flash_exchange_rate','手续费比例');
            $grid->status->switch('状态');
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
        return Show::make($id, new FlashExchangeConfig(), function (Show $show) {
            $show->field('id');
            $show->field('flash_exchange_rate','手续费比例');
            $show->field('status','状态');
            $show->field('created_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new FlashExchangeConfig(), function (Form $form) {
            $form->display('id');
            $form->text('flash_exchange_rate','手续费比例');
            $form->switch('status','状态');
        });
    }
}
