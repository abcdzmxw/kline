<?php
/**
 * Created by PhpStorm.
 * ListingApplication: Administrator
 * Date: 2020/7/23
 * Time: 11:12
 */

namespace App\Admin\Controllers;
use App\Admin\Actions\Application\ApplicationCheck;
use App\Models\ListingApplication;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class ListingApplicationController extends  AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new ListingApplication(), function (Grid $grid) {
            $grid->model()->orderByDesc('id');

            $grid->setActionClass(Grid\Displayers\Actions::class);

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
                $actions->disableEdit();
                $actions->disableView();

                if($actions->row->status == ListingApplication::status_wait){
                    $actions->append(new ApplicationCheck());
                }
            });

            $grid->disableCreateButton();
            $grid->disableDeleteButton();
            $grid->disableEditButton();

            // 这里的字段会自动使用翻译文件
            $grid->id->sortable();
            $grid->contact_phone;
            $grid->contact_position  ;
            $grid->coin_name;
            $grid->listing_fee_budget   ;
            $grid->referrer_mechanism_code;
            $grid->status->using([0=>'等待审核',1=>'审核成功',2=>'审核失败'])->dot([0=>'danger',1=>'success',2=>'error'])->filter(
                Grid\Column\Filter\In::make(ListingApplication::$statusMap));
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

            });
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
        return Show::make($id, new ListingApplication(), function (Show $show) {
            // 这里的字段会自动使用翻译文件
            $show->id;
            $show->contact_phone;
            $show->contact_position;
            $show->coin_name;
            $show->listing_fee_budget;
            $show->referrer_mechanism_code;
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
        return Form::make(new ListingApplication(), function (Form $form) {
            // 这里的字段会自动使用翻译文件
            $form->display('id');
            $form->text('contact_phone');
            $form->text('contact_position');
            $form->text('coin_name');
            $form->text('listing_fee_budget');
            $form->text('referrer_mechanism_code');
            $form->display('created_at');
            $form->display('updated_at');
        });
    }

}