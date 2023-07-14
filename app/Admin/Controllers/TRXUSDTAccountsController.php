<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\TRXUSDTCollect;
use App\Models\UserWallet;
use App\Services\CoinService\TronService;
use Dcat\Admin\Grid;
use Dcat\Admin\Controllers\AdminController;

class TRXUSDTAccountsController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $builder = UserWallet::with(['user'])->where('coin_id',1)->where(function($q){
            $q->whereNotNull('trx_wallet_address')->where('trx_wallet_address','<>','');
        });
        return Grid::make($builder, function (Grid $grid) {
            $grid->model()->orderByDesc('user_id');

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
                $actions->disableEdit();
                $actions->disableView();

                $actions->append(new TRXUSDTCollect());
            });

            $grid->disableCreateButton();
            $grid->disableDeleteButton();
            $grid->disableRowSelector();

            // 这里的字段会自动使用翻译文件
            $grid->column('user_id','UID');
            $grid->column('user.username','用户名');
            $grid->column('coin_name','币种');
            $grid->column('trx_wallet_address','地址');

            $grid->column('balance','余额')->display(function(){
                return (new TronService())->getTokenBalance($this->trx_wallet_address);
            });
            $grid->filter(function(Grid\Filter $filter){
                $filter->equal('user_id', '会员ID')->width(2);
                $filter->where('username',function($q){
                    $username = $this->input;
                    $q->whereHas('user',function($q)use($username){
                        $q->where('username',$username)->orWhere('phone',$username)->orWhere('email',$username);
                    });
                },"用户名/手机/邮箱")->width(3);
            });
        });
    }

}
