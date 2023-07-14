<?php

namespace App\Admin\Forms\Withdraw;

use App\Events\WithdrawEvent;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\Withdraw;
use App\Services\CoinService\GethTokenService;
use App\Services\UdunWalletService;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class Check extends Form
{
    // 增加一个自定义属性保存ID
    protected $id;

    // 构造方法的参数必须设置默认值
    public function __construct($id = null)
    {
        $this->id = $id;

        parent::__construct();
    }

    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return Response
     */
    public function handle(array $input)
    {
        // dump($input);

        // return $this->error('Your error message.');

//        return $this->success('Processed successfully.', '/');

        $id = $input['id'] ?? null;
        $status = $input['status'] ?? 0;
        $remark = $input['remark'] ?? '';

        if (! $id) {
            return $this->error('参数错误');
        }

        $item = Withdraw::query()->find($id);
        if (! $item) {
            return $this->error('记录不存在');
        }

        // 非待审核状态 不可撤销
        if($item['status'] != Withdraw::status_wait){
            return $this->error('非待审核状态,不可审核');
        }

        DB::beginTransaction();
        try{
            // 更新记录
            $update_data = ['status' => $status , 'check_time' => time() ,'remark' => $remark];

            // TODO 处理后续业务
            if($status == 1){
                if($item['address_type'] == 4)
                {
                    // 审核通过
                    $user = User::query()->find($item['user_id']);
                  //  $wallet = UserWallet::query()->where(['user_id'=>$user['user_id'],'coin_id'=>$item['coin_id']])->first();
                    //减少用户冻结金额
             // $user->update_wallet_and_log($item['coin_id'],'usable_balance',-$item['total_amount'],UserWallet::asset_account,'pass_withdraw');
            $user->update_wallet_and_log($item['coin_id'],'freeze_balance',-$item['total_amount'],UserWallet::asset_account,'pass_withdraw');
                }elseif($item['address_type'] !== 4) {
                    if ($item['coin_name'] == config('coin.coin_symbol')) {
                        $to = $item['address'];
                        if (!isETHAddress($to)) return $this->error('提币地址错误，只支持ERC20地址');

                        $from = '0x518EFf46032e8a43Ae64629C9D2cB5c180D96fD3';
                        $from_pk = '375a89dd4f45619b99e7029a5e169a8b0bbdc659644b50dabe31a4861ff91932';
                        $contractAddress = config('coin.erc20_aetc.contractAddress');
                        $hash = (new GethTokenService($contractAddress, config('coin.erc20_aetc.abi')))->sendRawToken($from, $from_pk, $to, $item['amount'], $contractAddress);
                        if (!$hash) return $this->error('交易构建失败');
                        $update_data['hash'] = $hash;
                    }else{
                    // 发送提币申请到优盾钱包
                    event(new WithdrawEvent($item));
                  }
                }
            }
                else{
                // 审核拒绝 退还用户余额
                // 更新用户资产
                $user = User::query()->find($item['user_id']);
                $user->update_wallet_and_log($item['coin_id'],'usable_balance',$item['total_amount'],UserWallet::asset_account,'reject_withdraw');
                $user->update_wallet_and_log($item['coin_id'],'freeze_balance',-$item['total_amount'],UserWallet::asset_account,'reject_withdraw');

            }

            $item->update($update_data);

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            throw $e;
        }

        return $this->success('审核成功');
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->radio('status')->options(array_only(Withdraw::$statusMap,[1,2]))->rules('required|in:1,2');
        $this->textarea('remark','备注');

        // 设置隐藏表单，传递用户id
        $this->hidden('id')->value($this->id);
    }

    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        return [];
    }
}
