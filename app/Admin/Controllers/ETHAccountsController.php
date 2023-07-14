<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\ETHCollect;
use App\Models\User;
use App\Models\UserWallet;
use App\Services\CoinService\GethService;
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

class ETHAccountsController extends AdminController
{
//    public function index(Content $content)
//    {
//        $web3 = new Web3(new HttpProvider(new HttpRequestManager(env('GETH_HOST'), 60)));
//        $accounts = UserWallet::query()->where('coin_id',3)->where(function($q){
//            $q->whereNotNull('wallet_address')->where('wallet_address','<>','');
//        })->pluck('wallet_address','user_id')->toArray();
//        $data = [];
//        $kk = 0;
//        foreach ($accounts as $uid => $account){
//            $data[$kk]['user_id'] = $uid;
//            $data[$kk]['wallet_address'] = $account;
//            $web3->eth->getBalance($account, function ($err, $balance) use (&$result){
//                if ($err !== null) {
//                    echo 'Error: ' . $err->getMessage();
//                    $result = 0;
//                }
//                $result = $balance;
//            });
//            $data[$kk]['balance'] = $result;
//            $kk++;
//        }
//        $table = Table::make(['UID','地址','余额'],$data);
//
//        return $content
//            ->title('网站设置')
//            ->body($table);
//    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $builder = UserWallet::with(['user'])->where('coin_id',3)->where(function($q){
            $q->whereNotNull('wallet_address')->where('wallet_address','<>','');
        })->orderBy('user_id','asc');
        return Grid::make($builder, function (Grid $grid) {
            $grid->model()->orderByDesc('user_id');

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableQuickEdit();
                $actions->disableEdit();
                $actions->disableView();

                $actions->append(new ETHCollect());
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
                return (new GethService())->getBalance($this->wallet_address);
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
