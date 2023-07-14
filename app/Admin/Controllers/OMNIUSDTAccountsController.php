<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\OmniusdtCollection;
use App\Models\User;
use App\Models\UserWallet;
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

class OMNIUSDTAccountsController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $builder = UserWallet::with(['user'])->where('coin_id',1)->where(function($q){
            $q->whereNotNull('omni_wallet_address')->where('omni_wallet_address','<>','');
        });
        return Grid::make($builder, function (Grid $grid) {
            $grid->model()->orderByDesc('user_id');

            #统计
            $grid->header(function ($query) {
                $usdt_amount = (new OmnicoreService())->getwalletbalances();
                $con = '<code>USDT：'.$usdt_amount.'</code>';
                return Alert::make($con, '统计')->info();
            });

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
                $actions->disableEdit();
                $actions->disableView();

                $actions->append(new OmniusdtCollection());
            });

            $grid->disableCreateButton();
            $grid->disableDeleteButton();
            $grid->disableRowSelector();

            $grid->column('user_id','UID');
            $grid->column('user.username','用户名');
            $grid->column('coin_name','币种');
            $grid->column('omni_wallet_address','地址');

            $grid->column('balance','余额')->display(function(){
                return (new OmnicoreService())->getBalance($this->omni_wallet_address);
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
