<?php

namespace App\Admin\Controllers;

use App\Models\OptionTime;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class OptionTimeController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new OptionTime(), function (Grid $grid) {
            $grid->time_id->sortable();
            $grid->time_name;
            $grid->seconds;
            $grid->fee_rate;

            $grid->odds_up_range('涨幅赔率')->pluck('range')->label('success');
            $grid->odds_down_range('跌幅赔率')->pluck('range')->label('danger');
            $grid->odds_draw_range('平幅赔率')->pluck('range')->label('primary');

            $grid->status->switch();
            $grid->created_at->sortable();
//            $grid->updated_at->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('time_id');

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
        return Show::make($id, new OptionTime(), function (Show $show) {
            $show->time_id;
            $show->time_name;
            $show->seconds;
            $show->fee_rate;
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
        return Form::make(new OptionTime(), function (Form $form) {
            $form->display('time_id');
            $form->text('time_name');
            $form->number('seconds');
            $form->text('fee_rate');
            $form->switch('status')->default(1);

            $form->table('odds_up_range', function ($table) {
                $table->text('range');
                $table->text('odds');
                $table->switch('is_default')->default(0);
            });

            $form->table('odds_down_range', function ($table) {
                $table->text('range');
                $table->text('odds');
                $table->switch('is_default')->default(0);
            });

            $form->table('odds_draw_range', function ($table) {
                $table->text('range');
                $table->text('odds');
                $table->switch('is_default')->default(0);
            });

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
