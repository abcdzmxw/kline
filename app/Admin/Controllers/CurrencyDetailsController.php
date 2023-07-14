<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/6
 * Time: 15:14
 */

namespace App\Admin\Controllers;
use App\Models\Coins;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class CurrencyDetailsController extends  AdminController
{
    protected function grid()
    {
        return Grid::make(new Coins(), function (Grid $grid) {
            $grid->id->sortable();
            $grid->coin_name;
            $grid->symbol;
            $grid->full_name;
            $grid->withdrawal_fee;
            $grid->official_website_link->display('详情') // 设置按钮名称
            ->expand(function () {
                // 返回显示的详情//
                // 这里返回 content 字段内容，并用 Card 包裹起来
                $card = new Card("官网地址", $this->official_website_link);

                return "<div style='padding:10px 10px 0'>$card</div>";
            });
            $grid->white_paper_link->display('详情') // 设置按钮名称
            ->expand(function () {
                // 返回显示的详情//
                // 这里返回 content 字段内容，并用 Card 包裹起来
                $card = new Card("白皮书地址", $this->white_paper_link);

                return "<div style='padding:10px 10px 0'>$card</div>";
            });
            $grid->block_query_link->display('详情') // 设置按钮名称
            ->expand(function () {
                // 返回显示的详情//
                // 这里返回 content 字段内容，并用 Card 包裹起来
                $card = new Card("区块查询地址", $this->block_query_link);

                return "<div style='padding:10px 10px 0'>$card</div>";
            });
            $grid->publish_time;
            $grid->total_issuance;
            $grid->total_circulation;
            $grid->crowdfunding_price;
            $grid->coin_content->display('详情') // 设置按钮名称
            ->expand(function () {
                // 返回显示的详情//
                // 这里返回 content 字段内容，并用 Card 包裹起来
                $card = new Card("项目详情", $this->coin_content);

                return "<div style='padding:10px 10px 0'>$card</div>";
            });;
//            $grid->coin_content;
            $grid->disableDeleteButton();
            $grid->disableCreateButton();
            $grid->filter(function($filter){
                $filter->equal('contact_phone', '联系人电话');
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
        return Show::make($id, new Coins(), function (Show $show) {
            // 这里的字段会自动使用翻译文件
            $show->full_name;
            $show->withdrawal_fee;
            $show->official_website_link;
            $show->coin_content;
            $show->white_paper_link;
            $show->block_query_link;
            $show->publish_time;
            $show->total_issuance;
            $show->total_circulation;
            $show->crowdfunding_price;
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
        return Form::make(new Coins(), function (Form $form) {
            // 这里的字段会自动使用翻译文件
            $form->text('full_name');
            $form->text('coin_content');
            $form->text('withdrawal_fee');
            $form->text('official_website_link');
            $form->text('white_paper_link');
            $form->text('block_query_link');
            $form->text('publish_time');
            $form->text('total_issuance');
            $form->text('total_circulation');
            $form->text('crowdfunding_price');
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}