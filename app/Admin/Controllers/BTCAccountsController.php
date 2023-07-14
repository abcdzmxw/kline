<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\BTCCollect;
use App\Models\User;
use App\Models\UserWallet;
use App\Services\CoinService\BitCoinService;
use App\Services\CoinService\GethService;
use App\Services\CoinService\GethTokenService;
use App\Services\CoinService\OmnicoreService;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Alert;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Widgets\Table;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Web3;

class BTCAccountsController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $builder = UserWallet::with(['user'])->where('coin_id',2)->where(function($q){
            $q->whereNotNull('wallet_address')->where('wallet_address','<>','');
        });
        return Grid::make($builder, function (Grid $grid) {
            $grid->model()->orderByDesc('user_id');

            #统计
            $grid->header(function ($query) {
                $amount = (new BitCoinService())->getBalance();
                $con = '<code>BTC：'.$amount.'</code>';
                return Alert::make($con, '总余额')->info();
            });

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
                $actions->disableEdit();
                $actions->disableView();

                $actions->append(new BTCCollect());
            });

            $grid->disableCreateButton();
            $grid->disableDeleteButton();
            $grid->disableRowSelector();

            // 这里的字段会自动使用翻译文件
            $grid->column('user_id','UID');
            $grid->column('user.username','用户名');
            $grid->column('coin_name','币种');
            $grid->column('wallet_address','地址');

            $grid->column('balance','余额')->display(function(){
                return (new BitCoinService())->getBalance($this->wallet_address);
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
