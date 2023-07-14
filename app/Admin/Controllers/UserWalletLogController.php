<?php

namespace App\Admin\Controllers;

use App\Models\Agent;
use App\Models\AgentGrade;
use App\Models\Coins;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\UserWalletLog;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;

class UserWalletLogController extends AdminController
{
    public function statistics()
    {
        $builder = UserWalletLog::query()->with(['user'])->where('user_wallet_logs.rich_type','usable_balance');
        $params = request()->only(['user_id','username','log_type','coin_id','created_at']);
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
            if(!empty($params['log_type'])){
                $builder->whereIn('log_type',$params['log_type']);
            }
            if(!empty($params['coin_id'])){
                $builder->where('coin_id',$params['coin_id']);
            }
            if(!empty($params['created_at']) && !empty($params['created_at']['start'])){
                $builder->whereDate('created_at','>=',$params['created_at']['start'])->whereDate('created_at','<=',$params['created_at']['end']);
            }

        }

        $res1 = $builder->sum('amount');

        $con = '<code>'.'总金额：'.$res1.'</code> ';
        return Alert::make($con, '统计')->info();
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $builder = UserWalletLog::query()->with(['user'])->where('user_wallet_logs.rich_type','usable_balance');
        return Grid::make($builder, function (Grid $grid) {
            $grid->model()->orderByDesc('id');

//            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $grid->disableBatchDelete();
            $grid->disableActions();

            #统计
            $grid->header(function ($query) {
                return $this->statistics();
            });

            // xlsx
            $titles = ['id' => 'ID', 'user_id'=>'UID','username'=>'用户名','account_type'=>'账户类型','log_type'=>'流水类型','coin_name' => '币种', 'amount' => '金额','before_balance'=>'原资产','after_balance'=>'现资产','created_at'=>'时间'];
            $grid->export()->titles($titles)->rows(function (array $rows) use ($titles){
                foreach ($rows as $index => &$row) {
                    $row['username'] = $row['user']['username'];
                    $account_type = $row['account_type'];
                    $account = array_first(UserWallet::$accountMap,function ($value, $key) use ($account_type){
                        return $value['id'] == $account_type;
                    });
                    $row['account_type'] = blank($account) ? '--' : $account['name'];
                    $row['log_type'] = UserWalletLog::getLogadminTypeText($row['log_type']);
                }
                return $rows;
            })->xlsx();

            $grid->id->sortable();
            $grid->user_id;
            $grid->column('user.username','用户名');
            $grid->account_type->display(function($v){
                $item = array_first(UserWallet::$accountMap,function ($value, $key) use ($v) {
                    return $value['id'] == $v;
                });
                return $item['name'];
            });
            $grid->log_type->display(function($v){
                return UserWalletLog::getLogadminTypeText($v);
            });
            $grid->coin_name;
//            $grid->rich_type;
            $grid->amount->display(function($amount){
                if( $amount < 0 ){
                    return "<span style='color:red'>$amount</span>";
                }else{
                    return "<span style='color:green'>$amount</span>";
                }
            });
//            $grid->log_note;
            $grid->before_balance;
            $grid->after_balance;
            $grid->created_at;
//            $grid->updated_at->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id','UID')->width(2);
                $filter->where('username',function($q){
                    $username = $this->input;
                    $q->whereHas('user',function($q)use($username){
                        $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                    });
                },"用户名/手机/邮箱")->width(3);
                $filter->in('log_type')->multipleSelect(UserWalletLog::$logType)->width(3);
                $filter->in('coin_id','币种')->multipleSelect(Coins::getCachedCoinOption())->width(3);
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
        return Show::make($id, new UserWalletLog(), function (Show $show) {
            $show->id;
            $show->user_id;
            $show->account_type;
            $show->coin_name;
            $show->rich_type;
            $show->amount;
            $show->log_type;
            $show->log_note;
            $show->before_balance;
            $show->after_balance;
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
        return Form::make(new UserWalletLog(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('account_type');
            $form->text('coin_name');
            $form->text('rich_type');
            $form->text('amount');
            $form->text('log_type');
            $form->text('log_note');
            $form->text('before_balance');
            $form->text('after_balance');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
