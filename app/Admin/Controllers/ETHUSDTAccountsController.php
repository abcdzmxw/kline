<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\ETHUSDTCollect;
use App\Models\User;
use App\Models\UserWallet;
use App\Services\CoinService\GethService;
use App\Services\CoinService\GethTokenService;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Widgets\Table;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Web3;

class ETHUSDTAccountsController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $builder = UserWallet::with(['user'])->where('coin_id',1)->where(function($q){
            $q->whereNotNull('wallet_address')->where('wallet_address','<>','');
        });
        return Grid::make($builder, function (Grid $grid) {
            $grid->model()->orderByDesc('user_id');

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
                $actions->disableEdit();
                $actions->disableView();

                $actions->append(new ETHUSDTCollect());
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
                $contractAddress = config('coin.erc20_usdt.contractAddress');
                $abi = config('coin.erc20_usdt.abi');
                return (new GethTokenService($contractAddress,$abi))->getBalance($this->wallet_address);
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
