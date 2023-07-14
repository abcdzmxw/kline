<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\OptionPair\Close;
use App\Admin\Actions\OptionPair\Open;
use App\Models\OptionPair;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class OptionPairController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new OptionPair(), function (Grid $grid) {

            $grid->disableRowSelector();
            $grid->disableBatchDelete();

            $grid->tools([
                new Open(),
                new Close(),
            ]);

            $grid->pair_id->sortable();
            $grid->pair_name;
            $grid->coin_name;
            $grid->base_coin_name;
            $grid->status->switch();
            $grid->trade_status->switch();
            $grid->created_at->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('pair_id');

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
        return Show::make($id, new OptionPair(), function (Show $show) {
            $show->pair_id;
            $show->pair_name;
            $show->base_coin_name;
            $show->coin_name;
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
        return Form::make(new OptionPair(), function (Form $form) {
            $form->display('pair_id');
            $form->text('pair_name');
            $form->text('quote_coin_id');
            $form->text('quote_coin_name');
            $form->text('base_coin_id');
            $form->text('base_coin_name');
            $form->switch('status')->default(1);
            $form->switch('trade_status')->default(1);


            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
