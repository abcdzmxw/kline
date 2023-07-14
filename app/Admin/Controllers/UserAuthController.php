<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\User\CheckAuth;
use App\Admin\Actions\User\CheckPrimaryAuth;
use App\Admin\Actions\User\PassUserAuth;
use App\Admin\Actions\User\RejectUserAuth;
use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\UserAuth;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Illuminate\Support\Arr;

class UserAuthController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(UserAuth::with(['user']), function (Grid $grid) {
            $grid->model()->orderByRaw("FIELD(status," . implode(",", [1, 0, 3, 2]) . ")")->orderByDesc('created_at');
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
                $actions->disableEdit();
                $actions->disableView();

                if($actions->row->primary_status == UserAuth::STATUS_WAIT){
                    $actions->append(new CheckPrimaryAuth());
                }
                if($actions->row->status == UserAuth::STATUS_WAIT){
                    $actions->append(new CheckAuth());
                }
            });

            $grid->disableCreateButton();
            $grid->disableDeleteButton();
            $grid->disableEditButton();

            $grid->tools([
                new PassUserAuth(),
                new RejectUserAuth(),
            ]);

            $grid->toolsWithOutline(false);

            $grid->id->sortable();

            $grid->user_id;
            $grid->realname;
            $grid->column('chu_country_code', '验证号码')->width('200')->display(function(){
                 if($this->chu_phone){
                    $data = "<h4 width='300px'>+".$this->chu_country_code." -".$this->chu_phone."</h4>";
                    $data .= "<h4 width='300px'>+".$this->gao_country_code." -".$this->gao_phone."</h4>";
                }else{
                    $data = "<h4 width='300px'>+".Arr::get($this->user,'country_code')." -".Arr::get($this->user,'phone')."</h4>";
                }
                return $data;
            });
            // $grid->column('chu_phone', '初级手机号码');
            // $grid->country_code;
            // $grid->phone;
            $grid->id_card;
            $grid->front_img->image('',50,50);
            $grid->back_img->image('',50,50);
            $grid->hand_img->image('',50,50);
            $grid->check_time;
            $grid->column('primary_remark', '初级拒绝原因');
            $grid->column('remark', '拒绝原因');
            $grid->primary_status->using(UserAuth::$primaryStatusMap)->dot([0=>'default',1=>'success',2=>'primary']);
            $grid->status->using(UserAuth::$statusMap)->dot([0=>'default',1=>'danger',2=>'success',3=>'primary'])->filter(
                Grid\Column\Filter\In::make(UserAuth::$statusMap)
            );
            $grid->created_at->sortable();
//            $grid->updated_at->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id', '会员ID')->width(3);
                $filter->where('username',function($q){
                    $username = $this->input;
                    $q->whereHas('user',function($q)use($username){
                        $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                    });
                },"用户名/手机/邮箱")->width(3);
                 $filter->equal('status', '高级认证')->select(UserAuth::$statusMap)->width(3);

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
        return Show::make($id, new UserAuth(), function (Show $show) {
            $show->id;
            $show->user_id;
            $show->realname;
            $show->country_code;
            $show->id_card;
            $show->front_img;
            $show->back_img;
            $show->hand_img;
            $show->check_time;
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
        return Form::make(new UserAuth(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('realname');
            $form->text('country_code');
            $form->text('id_card');
            $form->text('front_img');
            $form->text('back_img');
            $form->text('hand_img');
            $form->text('check_time');
            $form->text('status');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
