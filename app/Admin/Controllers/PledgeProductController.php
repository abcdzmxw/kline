<?php

namespace App\Admin\Controllers;

use App\Models\Coins;
use App\Models\PledgeProduct;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class PledgeProductController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new PledgeProduct(), function (Grid $grid) {
            $grid->disableBatchDelete();
//            $grid->actions(function (Grid\Displayers\Actions $actions) {
//                $actions->disableDelete();
//            });
            $grid->model()->orderByDesc("id");

            $grid->id->sortable();
            $grid->name;
            $grid->cover->image('',50,50);
            $grid->spread_img->image('',50,50);
            $grid->coin_name;
            $grid->cycle;
            $grid->rate;
            $grid->min_amount;
            $grid->max_amount;
            $grid->can_buy_num;
            //$grid->column('status','状态')->using(PledgeProduct::$statusMap)->dot([0=>'danger',1=>'success']);
            $grid->special->switch();
            $grid->status->switch();
            //$grid->switch('status', '是否上架');
            $grid->created_at->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('name')->width(2);
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
        return Show::make($id, new PledgeProduct(), function (Show $show) {
            $show->id;
            $show->coin_name;
            $show->cover;
            $show->spread_img;
            $show->name;
            $show->cycle;
            $show->rate;
            $show->min_amount;
            $show->max_amount;
            $show->can_buy_num;
            $show->content;
             $show->special;
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
        return Form::make(new PledgeProduct(), function (Form $form) {
            $form->disableDeleteButton();

            $options = Coins::query()->where('status', 1)->orderByDesc('coin_id')->pluck('coin_name',
                'coin_id')->toArray();

            $form->display('id');
            $form->select('coin_id')->options($options)->rules("required");;
            $form->hidden('coin_name');
            $form->image('cover')->uniqueName()->autoUpload();
            $form->image('spread_img')->uniqueName()->autoUpload();
            $form->text('name')->rules("required");
            $form->number('cycle')->rules("required|gte:1");
            $form->number('rate')->rules("required|gte:1");
            $form->decimal('min_amount')->rules("required|gt:0");
            $form->decimal('max_amount')->rules("required|max:9");
            $form->number('can_buy_num')->rules("required|gte:1");
            $form->switch('special')->default(0);
            $form->switch('status')->default(1);
            $form->textarea('content');
            $form->display('created_at');
            $form->display('updated_at');

            $form->saving(function (Form $form) use ($options) {
                if ($form->isCreating() || $form->isEditing()) {
                    if (!blank($form->coin_id)) {
                        $coin_id         = $form->coin_id;
                        $coin_name       = $options[$coin_id];
                        $form->coin_id   = $coin_id;
                        $form->coin_name = $coin_name;
                    }
                }
            });
        });
    }
}
