<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/29
 * Time: 15:12
 */

namespace App\Admin\Controllers;
use App\Models\UserRechargeEth;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class AutomaticRechargeController extends  AdminController
{
    protected function grid()
    {
        return Grid::make(new UserRechargeEth(), function (Grid $grid) {
            $grid->model()->orderByDesc('id');
            // 这里的字段会自动使用翻译文件
            $grid->id->sortable();
            $grid->user_id;
            $grid->coin_name;
            $grid->address;
            $grid->txid;
            $grid->amount;
            $grid->amount_u;
            $grid->status->using([1=>'充值失败',2=>'充值成功'])->dot([1=>'error',2=>'success']);
            $grid->disableCreateButton();
            $grid->filter(function($filter){
                $filter->equal('user_id', '会员ID');
                $filter->like('status', '审核状态');
                $filter->like('coin_name', '币种名字');

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
        return Show::make($id, new UserRechargeEth(), function (Show $show) {
            // 这里的字段会自动使用翻译文件
            $show->id;
            $show->user_id;
            $show->coin_name;
            $show->address;
            $show->txid;
            $show->amount;
            $show->amount_u;
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
        return Form::make(new UserRechargeEth(), function (Form $form) {
            // 这里的字段会自动使用翻译文件
            $form->display('id');
            $form->text('user_id');
            $form->text('coin_name');
            $form->text('address');
            $form->text('txid');
            $form->text('amount');
            $form->text('amount_u');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}