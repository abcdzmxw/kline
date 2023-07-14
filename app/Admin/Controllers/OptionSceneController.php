<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\OptionSceneOrder\Handle;
use App\Models\OptionScene;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class OptionSceneController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new OptionScene(), function (Grid $grid) {
            $grid->model()->orderByDesc('scene_id');

            $grid->disableActions();
            $grid->disableBatchDelete();
            $grid->disableCreateButton();

//            $grid->scene_id->sortable();
            $grid->scene_sn;
//            $grid->time_id;
//            $grid->pair_id;
            $grid->pair_time_name;
            $grid->begin_time->display(function($v){
                return date("Y-m-d H:i:s",$v);
            });
            $grid->end_time->display(function($v){
                return date("Y-m-d H:i:s",$v);
            });
            $grid->begin_price;
            $grid->end_price;
            $grid->delivery_up_down;
            $grid->delivery_range;
            $grid->delivery_time;
            $grid->status->using(OptionScene::$statusMap)->dot();
            $grid->created_at;
//            $grid->updated_at->sortable();

            $grid->filter(function (Grid\Filter $filter) {
//                $filter->equal('scene_id');

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
        return Show::make($id, new OptionScene(), function (Show $show) {
            $show->scene_id;
            $show->scene_sn;
            $show->time_id;
            $show->pair_id;
            $show->pair_time_name;
            $show->begin_time;
            $show->end_time;
            $show->begin_price;
            $show->end_price;
            $show->delivery_up_down;
            $show->delivery_range;
            $show->delivery_time;
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
        return Form::make(new OptionScene(), function (Form $form) {
            $form->display('scene_id');
            $form->text('scene_sn');
            $form->text('time_id');
            $form->text('pair_id');
            $form->text('pair_time_name');
            $form->text('begin_time');
            $form->text('end_time');
            $form->text('begin_price');
            $form->text('end_price');
            $form->text('delivery_up_down');
            $form->text('delivery_range');
            $form->text('delivery_time');
            $form->text('status');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
