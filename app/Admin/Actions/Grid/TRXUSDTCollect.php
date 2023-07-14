<?php

namespace App\Admin\Actions\Grid;

use App\Jobs\CoinCollection;
use App\Models\UserWallet;
use App\Models\WalletCollection;
use App\Services\CoinService\GethService;
use App\Services\CoinService\GethTokenService;
use App\Services\CoinService\TronService;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use phpseclib\Math\BigInteger as BigNumber;
use Web3\Utils;

class TRXUSDTCollect extends RowAction
{
    /**
     * @return string
     */
    protected $title = '归集';

    /**
     * Handle the action request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        $id = $this->getKey();
        if(blank($id)){
            return $this->response()->error('Processed fail.');
        }
        $wallet = UserWallet::query()->where('wallet_id',$id)->first();
        $address = $wallet['trx_wallet_address'];
        $private_key = $wallet['private_key'];
        if(blank($address)) return $this->response()->error('地址为空');

        $tron = new TronService();
        $min_amount = config('coin.collect_min_amount.usdt');
        $to = \App\Models\CenterWallet::query()->where('center_wallet_account','trx_collection_account')->value('center_wallet_address');
        $balance = $tron->getTokenBalance($address);
        if($balance <= 0) return $this->response()->error('余额为0');

        // TODO 判断用户地址有没有可用的手续费
        $trx_balance = $tron->getBalance($address);
        $min_fee = $tron->getFee($address); // TODO 这里暂且自定义手续费
        if($trx_balance < $min_fee){
            $fee_res = $tron->sendFee($address);
            if($fee_res){
                $txid = $fee_res;
                $status = 0;
                WalletCollection::query()->create([
                    'symbol' => 'TRX_USDT',
                    'from' => $address,
                    'to' => $to,
                    'amount' => $balance,
                    'txid' => $txid,
                    'datetime' => time(),
                    'note' => '',
                    'status' => $status,
                ]);

                return $this->response()->warning('已发送归集手续费，等待归集中');
            }else{
                return $this->response()->warning('发送手续费失败');
            }
        }else{
            $res = $tron->sendTrc20Transaction($address,$private_key,$to,$balance);
            if($res){
                $txid = $res;
                $status = 1;
                WalletCollection::query()->create([
                    'symbol' => 'TRX_USDT',
                    'from' => $address,
                    'to' => $to,
                    'amount' => $balance,
                    'txid' => $txid,
                    'datetime' => time(),
                    'note' => '',
                    'status' => $status,
                ]);

                return $this->response()
                    ->success('归集成功，等待区块网络确认')
                    ->refresh();
            }else{
                return $this->response()->error('归集失败');
            }
        }
    }

    /**
     * @return string|array|void
     */
    public function confirm()
    {
        return ['Confirm?', '确认？'];
    }

    /**
     * @param Model|Authenticatable|HasPermissions|null $user
     *
     * @return bool
     */
    protected function authorize($user): bool
    {
        return true;
    }

    /**
     * @return array
     */
    protected function parameters()
    {
        return [];
    }
}
