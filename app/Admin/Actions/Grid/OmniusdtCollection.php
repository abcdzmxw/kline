<?php

namespace App\Admin\Actions\Grid;

use App\Models\UserWallet;
use App\Models\WalletCollection;
use App\Services\CoinService\OmnicoreService;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class OmniusdtCollection extends RowAction
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
        $wallet_address = UserWallet::query()->where('wallet_id',$id)->value('omni_wallet_address');
        if(blank($wallet_address)) return $this->response()->error('地址为空');

        $min_amount = config('coin.collect_min_amount.usdt');
        $to = \App\Models\CenterWallet::query()->where('center_wallet_account','btc_collection_account')->value('center_wallet_address');
        $balance = (new OmnicoreService())->getBalance($wallet_address);
//        if($balance < $min_amount) return $this->response()->error('余额小于最小额度');

        $res = (new OmnicoreService())->collection($wallet_address,$to,$balance);
        if($res === false){
            $fee_res = (new OmnicoreService())->sendFee($wallet_address);
            if($fee_res){
                $txid = $fee_res;
                $status = 0;
                WalletCollection::query()->create([
                    'symbol' => 'BTC_USDT',
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
            $txid = $res;
            $status = 1;
            WalletCollection::query()->create([
                'symbol' => 'BTC_USDT',
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
        }
//        if($res){
//            $txid = $res;
//            $status = 1;
//            WalletCollection::query()->create([
//                'symbol' => 'BTC_USDT',
//                'from' => $wallet_address,
//                'to' => $to,
//                'amount' => $balance,
//                'txid' => $txid,
//                'datetime' => time(),
//                'note' => '',
//                'status' => $status,
//            ]);
//            return $this->response()
//                ->success('归集成功，等待区块网络确认')
//                ->refresh();
//        }else{
//            $txid = '';
//            $status = 0;
//            WalletCollection::query()->create([
//                'symbol' => 'BTC_USDT',
//                'from' => $wallet_address,
//                'to' => $to,
//                'amount' => $balance,
//                'txid' => $txid,
//                'datetime' => time(),
//                'note' => '',
//                'status' => $status,
//            ]);
//            return $this->response()->error('归集失败，已加入待归集任务列表');
//        }
    }

    /**
	 * @return string|array|void
	 */
	public function confirm()
	{
		 return ['Confirm?', 'contents'];
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
