<?php

namespace App\Admin\Controllers;

use App\Models\CenterWallet;
use App\Services\CoinService\BitCoinService;
use App\Services\CoinService\GethService;
use App\Services\CoinService\GethTokenService;
use App\Services\CoinService\OmnicoreService;
use App\Services\CoinService\TronService;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class CenterWalletController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new CenterWallet(), function (Grid $grid) {
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
//                $actions->disableEdit();
                $actions->disableView();
            });
//            $grid->disableActions();
            $grid->disableCreateButton();
            $grid->disableDeleteButton();
            $grid->disableRowSelector();

            $grid->column('center_wallet_id')->sortable();
            $grid->column('center_wallet_name');
            $grid->column('center_wallet_account');
            $grid->column('center_wallet_address');

            $grid->column('balance','余额')->display(function(){
                if($this->coin_id == 2){
                    return (new BitCoinService())->getBTCBalance($this->center_wallet_address) . '(BTC)';
                }elseif($this->coin_id == 3){
                    return (new GethService())->getBalance($this->center_wallet_address) . '(ETH)';
                }elseif ($this->coin_id == 13){
                    return (new TronService())->getBalance($this->center_wallet_address) . '(TRX)';
                }
            });

            $grid->column('token_balance','代币余额')->display(function(){
                if($this->coin_id == 2){
                    return (new OmnicoreService())->getBalance($this->center_wallet_address) . '(USDT)';
                }elseif($this->coin_id == 3){
                    $contractAddress = config('coin.erc20_usdt.contractAddress');
                    $abi = config('coin.erc20_usdt.abi');
                    return (new GethTokenService($contractAddress,$abi))->getBalance($this->center_wallet_address) . '(USDT)';
                }elseif ($this->coin_id == 13){
                    return (new TronService())->getTokenBalance($this->center_wallet_address) . '(USDT)';
                }
            });

            $grid->filter(function (Grid\Filter $filter) {

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
        return Show::make($id, new CenterWallet(), function (Show $show) {
            $show->field('center_wallet_id');
            $show->field('center_wallet_name');
            $show->field('center_wallet_account');
            $show->field('center_wallet_address');
            $show->field('center_wallet_balance');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new CenterWallet(), function (Form $form) {
            $form->display('center_wallet_id');
            $form->text('center_wallet_name');
            $form->text('center_wallet_account');
            $form->text('center_wallet_address');
            $form->text('center_wallet_balance');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
