<?php

namespace App\Admin\Controllers;

use App\Models\OtcOrder;
use App\Models\UserPayment;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class OtcOrderController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new OtcOrder(), function (Grid $grid) {
            $grid->model()->orderByDesc('id');

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
                $actions->disableEdit();
                $actions->disableView();

                if($actions->row->status == OtcOrder::status_appealing){
                    $actions->append(new \App\Admin\Actions\Otc\Check());
                }
            });
            $grid->disableBatchDelete();
            $grid->disableCreateButton();

            $grid->column('id')->sortable();
            $grid->column('trans_type')->using([1=>'购买',2=>'出售']);
            $grid->column('order_sn');
            $grid->column('user_id');
            $grid->column('other_uid');
            $grid->column('entrust_id');
//            $grid->column('coin_id');
            $grid->column('coin_name');
            $grid->column('amount');
            $grid->column('pay_type')->using(UserPayment::$payTypeMap)->label();
            $grid->column('price');
            $grid->column('money');
            $grid->column('order_time')->display(function($v){
                return blank($v) ? '' : date('Y-m-d H:i:s',$v);
            });
            $grid->column('pay_time')->display(function($v){
                return blank($v) ? '' : date('Y-m-d H:i:s',$v);
            });
            $grid->column('deal_time')->display(function($v){
                return blank($v) ? '' : date('Y-m-d H:i:s',$v);
            });
            $grid->column('status')->using(OtcOrder::$statusMap)->dot([0=>'default',1=>'info',2=>'info',3=>'success',4=>'danger']);
//            $grid->column('appeal_status')->using(OtcOrder::$appealStatusMap);
            $grid->column('paid_img')->image('',50,50);
            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

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
        return Show::make($id, new OtcOrder(), function (Show $show) {
            $show->field('id');
            $show->field('trans_type');
            $show->field('order_sn');
            $show->field('user_id');
            $show->field('other_uid');
            $show->field('entrust_id');
            $show->field('coin_id');
            $show->field('coin_name');
            $show->field('amount');
            $show->field('pay_type');
            $show->field('price');
            $show->field('money');
            $show->field('order_time');
            $show->field('pay_time');
            $show->field('deal_time');
            $show->field('status');
            $show->field('appeal_status');
            $show->field('paid_img');
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
        return Form::make(new OtcOrder(), function (Form $form) {
            $form->display('id');
            $form->text('trans_type');
            $form->text('order_sn');
            $form->text('user_id');
            $form->text('other_uid');
            $form->text('entrust_id');
            $form->text('coin_id');
            $form->text('coin_name');
            $form->text('amount');
            $form->text('pay_type');
            $form->text('price');
            $form->text('money');
            $form->text('order_time');
            $form->text('pay_time');
            $form->text('deal_time');
            $form->text('status');
            $form->text('appeal_status');
            $form->text('paid_img');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
