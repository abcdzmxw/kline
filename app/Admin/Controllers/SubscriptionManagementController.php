<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/24
 * Time: 21:23
 */

namespace App\Admin\Controllers;

use App\Models\UserSubscribe;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Controllers\AdminController;

class SubscriptionManagementController  extends  AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new UserSubscribe(), function (Grid $grid) {

            // 这里的字段会自动使用翻译文件
            $grid->id->sortable();
            $grid->coin_name;
            $grid->issue_price;
            $grid->subscribe_currency->display(function($v){
                return explode('/',$v);
            })->label();
            $grid->expected_time_online   ;
            $grid->start_subscription_time;
            $grid->end_subscription_time;
            $grid->announce_time;
            $grid->minimum_purchase;
            $grid->maximum_purchase;
            $grid->project_details->display('详情') // 设置按钮名称
            ->expand(function () {
                // 返回显示的详情//
                // 这里返回 content 字段内容，并用 Card 包裹起来
                $card = new Card("项目详情", $this->project_details);

                return "<div style='padding:10px 10px 0'>$card</div>";
            });;

            $grid->filter(function (Grid\Filter $filter) {
//                $filter->equal('id');

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
        return Show::make($id, new UserSubscribe(), function (Show $show) {
            // 这里的字段会自动使用翻译文件
            $show->id;
            $show->coin_name;
            $show->issue_price;
            $show->subscribe_currency;
            $show->expected_time_online;
            $show->start_subscription_time;
            $show->end_subscription_time;
            $show->announce_time;
            $show->minimum_purchase;
            $show->maximum_purchase;
            $show->project_details;
        });

    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new UserSubscribe(), function (Form $form) {
            // 这里的字段会自动使用翻译文件
            $form->display('id');
            $form->text('coin_name');
            $form->text('issue_price');
            $form->text('subscribe_currency');
            $form->datetime('expected_time_online');
            $form->datetime('start_subscription_time');
            $form->datetime('end_subscription_time');
            $form->datetime('announce_time');
            $form->text('minimum_purchase');
            $form->text('maximum_purchase');
            $form->textarea('project_details');
            $form->textarea('en_project_details');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
