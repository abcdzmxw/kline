<?php

namespace App\Admin\Controllers;

use App\Models\WalletCollection;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class WalletCollectionController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new WalletCollection(), function (Grid $grid) {
            $grid->model()->orderByDesc('id');

            $grid->disableCreateButton();
            $grid->disableDeleteButton();
            $grid->disableRowSelector();
            $grid->disableActions();

            $grid->column('id')->sortable();
            $grid->column('symbol')->display(function($v){
                if($v == 'ETH_USDT'){
                    return 'erc20_usdt';
                }elseif($v == 'BTC_USDT'){
                    return 'omni_usdt';
                }else{
                    return $v;
                }
            });
            $grid->column('from')->limit(20)->responsive();
            $grid->column('to')->limit(20)->responsive();
            $grid->column('amount');
            $grid->column('txid')->link(function ($value) {
                if(in_array($this->symbol,['ETH','ETH_USDT'])){
                    return 'https://etherscan.io/tx/' . $value;
                }elseif(in_array($this->symbol,['TRX','TRX_USDT'])){
                    return 'https://tronscan.org/#/transaction/' . $value;
                }else{
                    return 'https://btc.com/' . $value;
                }
            });
//            $grid->column('datetime');
//            $grid->column('note');
            $grid->column('status')->using([0=>'待广播交易',1=>'已广播交易'])->dot([0=>'danger',1=>'success']);
            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
//                $filter->equal('id');

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
        return Show::make($id, new WalletCollection(), function (Show $show) {
            $show->field('id');
            $show->field('symbol');
            $show->field('from');
            $show->field('amount');
            $show->field('to');
            $show->field('txid');
            $show->field('datetime');
            $show->field('note');
            $show->field('status');
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
        return Form::make(new WalletCollection(), function (Form $form) {
            $form->display('id');
            $form->text('symbol');
            $form->text('from');
            $form->text('amount');
            $form->text('to');
            $form->text('txid');
            $form->text('datetime');
            $form->text('note');
            $form->text('status');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
