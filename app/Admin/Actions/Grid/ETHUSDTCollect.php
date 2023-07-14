<?php

namespace App\Admin\Actions\Grid;

use App\Jobs\CoinCollection;
use App\Models\UserWallet;
use App\Models\WalletCollection;
use App\Services\CoinService\GethService;
use App\Services\CoinService\GethTokenService;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use phpseclib\Math\BigInteger as BigNumber;
use Web3\Utils;

class ETHUSDTCollect extends RowAction
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
        $wallet_address = UserWallet::query()->where('wallet_id',$id)->value('wallet_address');
        if(blank($wallet_address)) return $this->response()->error('地址为空');

        $min_amount = config('coin.collect_min_amount.usdt');
        $to = \App\Models\CenterWallet::query()->where('center_wallet_account','eth_collection_account')->value('center_wallet_address');
        $contractAddress = config('coin.erc20_usdt.contractAddress');
        $abi = config('coin.erc20_usdt.abi');
        $balance = (new GethTokenService($contractAddress,$abi))->getBalance($wallet_address);
//        if($balance < $min_amount) return $this->response()->error('余额小于最小额度');

        // 判断用户地址有没有可用的ETH手续费
        $gasPrice = Utils::toHex(Utils::toWei((new GethService())->getEthGasPrice('fast'),'Gwei'),true);
        $gas = (new GethService())->getGasUse();
        $collect_fee = new BigNumber((hexdec($gasPrice) * hexdec($gas)));
        $min_fee = (new GethService())->weiToEther($collect_fee);
        $ether = (new GethService())->getBalance($wallet_address);
        if($ether < $min_fee){
            $fee_res = (new GethService())->sendFee($wallet_address);
            if($fee_res){
                $txid = $fee_res;
                $status = 0;
                WalletCollection::query()->create([
                    'symbol' => 'ETH_USDT',
                    'from' => $wallet_address,
                    'to' => $to,
                    'amount' => $balance,
                    'txid' => $txid,
                    'datetime' => time(),
                    'note' => '',
                    'status' => $status,
                ]);
                return $this->response()->warning('已发送归集手续费，等待归集中');
            }else{
                return $this->response()->warning('发送手续费失败，手续费账户余额不足');
            }
        }else{
            $res = (new GethTokenService($contractAddress,$abi))->collection($wallet_address,$to,$balance);
            if($res){
                $txid = $res;
                $status = 1;
                WalletCollection::query()->create([
                    'symbol' => 'ETH_USDT',
                    'from' => $wallet_address,
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
                $txid = '';
                $status = 0;
                WalletCollection::query()->create([
                    'symbol' => 'ETH_USDT',
                    'from' => $wallet_address,
                    'to' => $to,
                    'amount' => $balance,
                    'txid' => $txid,
                    'datetime' => time(),
                    'note' => '',
                    'status' => $status,
                ]);
                return $this->response()->error('归集失败，已加入待归集任务列表');
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
