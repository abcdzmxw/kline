<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/29
 * Time: 19:11
 */

namespace App\Admin\Controllers;

use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\Coins;
use App\Models\User;
use App\Models\UserWallet;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;

class UserAssetsController extends AdminController
{

    public function statistics()
    {

        $builder = UserWallet::query();
        $params = request()->only(['coin_name','user_id','username','created_at']);

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
            if(!empty($params['coin_name'])){
                $builder->where('coin_name',$params['coin_name']);
            }
            if(!empty($params['created_at']) && !empty($params['created_at']['start'])){
                $start = $params['created_at']['start'];
                $end = $params['created_at']['end'];
                $builder->whereDate('created_at','>=',$start)->whereDate('created_at','<=',$end);
            }

        }

        $records = $builder->groupBy('coin_name')->selectRaw('sum(usable_balance) as total_usable_balance, coin_name')->get();
        $records = $records->sortByDesc('total_usable_balance');
        $con = '';
        foreach ($records as $record){
            $coin = Coins::query()->where('coin_name',$record['coin_name'])->first();
            if($coin['is_recharge'] == 1 || $coin['can_recharge'] == 1){
                $con .= '<code>'.$record['coin_name'].'金额：'.$record['total_usable_balance'].'</code> ';
            }
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
        return Grid::make(UserWallet::with(['user']), function (Grid $grid) {
            $grid->model()->orderByDesc('user_id');

            #统计
            $grid->header(function ($query) {
                return $this->statistics();
            });

            $grid->disableActions();
            $grid->disableCreateButton();
            $grid->disableDeleteButton();
            $grid->disableRowSelector();

            $grid->column('user_id','UID');
            $grid->column('user.username','用户名');
            $grid->coin_name;
            $grid->usable_balance->display(function ($v){
                return custom_number_format($v,8);
            })->sortable();
            $grid->freeze_balance->display(function ($v){
                return custom_number_format($v,8);
            })->sortable();
            $grid->disableCreateButton();
            $grid->filter(function(Grid\Filter $filter){
                $filter->equal('user_id', '会员ID')->width(2);
                $filter->where('username',function($q){
                    $username = $this->input;
                    $q->whereHas('user',function($q)use($username){
                        $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                    });
                },"用户名/手机/邮箱")->width(3);
                $filter->like('coin_name', '币种名字')->width(3);
                $filter->between('created_at',"时间")->datetime()->width(4);
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
        return Show::make($id, new UserWallet(), function (Show $show) {
            // 这里的字段会自动使用翻译文件
            $show->id;
            $show->coin_name;
            $show->address;
            $show->usable_balance;
            $show->freeze_balance;
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
        return Form::make(new UserWallet(), function (Form $form) {
            // 这里的字段会自动使用翻译文件
            $form->display('id');
            $form->text('coin_name');
            $form->text('usable_balance');
            $form->text('freeze_balance');


            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
