<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\ContractAccount\Recharge;
use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\SustainableAccount;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Admin;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;

class ContractAccountController extends AdminController
{
    public function statistics()
    {
        $builder = SustainableAccount::query();
        $params = request()->only(['user_id','username']);

        if(!empty($params)){
            if(!empty($params['user_id'])){
                $builder->where('user_id',$params['user_id']);
            }
            if(!empty($params['username'])){
                $username = $params['username'];
                $builder->whereHas('user',function($q)use($username){
                    $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                });
            }

        }

        $records = $builder->groupBy('coin_name')->selectRaw('sum(usable_balance) as total_usable_balance, coin_name')->get();
        $records = $records->sortByDesc('total_usable_balance');
        $con = '';
        foreach ($records as $record){
            $con .= '<code>'.$record['coin_name'].'金额：'.$record['total_usable_balance'].'</code> ';
        }
        return Alert::make($con, '统计')->info();
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new SustainableAccount(), function (Grid $grid) {
            $grid->model()->orderByDesc('user_id');
            $grid->column('id')->sortable();

            #统计
            $grid->header(function ($query) {
                return $this->statistics();
            });

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
                $actions->disableEdit();
                $actions->disableView();

//                if (Admin::user()->can('user-recharge')) {
//                    $actions->append(new Recharge());
//                }
            });
            $grid->disableCreateButton();
            $grid->disableRowSelector();

            $grid->column('user_id');
//            $grid->column('coin_id');
//            $grid->column('coin_name');
            $grid->column('margin_name');
            $grid->column('usable_balance')->sortable();
            $grid->column('used_balance');
            $grid->column('freeze_balance');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id','UID')->width(2);
                $filter->where('username',function($q){
                    $username = $this->input;
                    $q->whereHas('user',function($q)use($username){
                        $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                    });
                },"用户名/手机/邮箱")->width(3);
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
        return Show::make($id, new SustainableAccount(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('coin_id');
            $show->field('coin_name');
            $show->field('margin_name');
            $show->field('usable_balance');
            $show->field('used_balance');
            $show->field('freeze_balance');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new SustainableAccount(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('coin_id');
            $form->text('coin_name');
            $form->text('margin_name');
            $form->text('usable_balance');
            $form->text('used_balance');
            $form->text('freeze_balance');
        });
    }
}
