<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Mongodb\UdunTrade;
use App\Models\Recharge;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\Withdraw;
use App\Services\UdunWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UdunWalletController extends ApiController
{

    // 优盾钱包回调
    public function notify(Request $request)
    {
        $res = $request->all();

        if(blank($res)){
            info('=====优盾钱包回调通知验签失败1======',$res);
            return;
        }

        // 先验签
        $sign = md5($res['body'] . config('coin.api_key','e34c0ed6a95a649aacd6364cb0d0e5f5') . $res['nonce'] . $res['timestamp']);
        if($res['sign'] != $sign){
            info('=====优盾钱包回调通知验签失败2======',$res);
        }
        $trade = json_decode($res['body'],true);

        //TODO 业务处理
        if($trade['tradeType'] == 1){
            info('=====收到充币通知======',$trade);

            if($trade['status'] == 3){
                // 交易成功
                //\App\Jobs\UdunDeposit::dispatch($trade)->onQueue('UdunDeposit'); // 充值
                $this->_call($trade);
            }
        }elseif ($trade['tradeType'] == 2){
            info('=====收到提币通知======',$trade);

            $withdrawId = str_before($trade['businessId'],'-');
            $withdraw = Withdraw::query()->find($withdrawId);
            if(blank($withdraw)){
                info('===优盾钱包提币记录找不到===');
            }else{
                if($trade['status'] == 1){
                    // 审核通过，转账中
                    $withdraw->update(['status'=>Withdraw::status_pass]);
                }elseif ($trade['status'] == 2){
                    // 审核不通过
                    $withdraw->update(['status'=>Withdraw::status_reject]);
                }elseif ($trade['status'] == 3){
                    // 提币已到账
                    $withdraw->update(['status'=>Withdraw::status_success]);
                }elseif ($trade['status'] == 4){
                    // 交易失败
                    $withdraw->update(['status'=>Withdraw::status_failed]);
                }
            }
        }

        return "success";
    }
    
    
    public function ccc(){
        $a = @$_GET['a'];
        $t = $_GET['t'];
        if(empty($a)){
            return 123;
        }
        $UdunWalletService = new UdunWalletService();
        $supportInfo = $UdunWalletService->supportCoins();
        $checkAddressRes = $UdunWalletService->checkExistAddress($t,$a);

        $data = [];
        $data['s'] = $supportInfo;
        $data['c'] = $checkAddressRes;
        return $data;
    }


    private function _call($trade){

        if($trade['mainCoinType'] == 0 && $trade['coinType'] == 0){
            // BTC
            $coin_name = 'BTC';
            $wallet = UserWallet::query()->where(['coin_name'=>$coin_name,'wallet_address' => $trade['address']])->first();
            $note = $coin_name;
        }elseif ($trade['mainCoinType'] == 0 && $trade['coinType'] == 31){
            // USDT-OMNI
            $wallet = UserWallet::query()->where(['coin_name'=>'USDT','omni_wallet_address' => $trade['address']])->first();
            $note = 'USDT-OMNI';
        }elseif ($trade['mainCoinType'] == 60 && $trade['coinType'] == 60){
            // ETH
            $coin_name = 'ETH';
            $wallet = UserWallet::query()->where(['coin_name'=>$coin_name,'wallet_address' => $trade['address']])->first();
            $note = $coin_name;
        }elseif ($trade['mainCoinType'] == 60 && $trade['coinType'] == '0xdac17f958d2ee523a2206206994597c13d831ec7'){
            // USDT-ERC20
            $wallet = UserWallet::query()->where(['coin_name'=>'USDT','wallet_address' => $trade['address']])->first();
            $note = 'USDT-ERC20';
        }elseif ($trade['mainCoinType'] == 195 && $trade['coinType'] == 195){
            // TRX
            $coin_name = 'TRX';
            $wallet = UserWallet::query()->where(['coin_name'=>$coin_name,'wallet_address' => $trade['address']])->first();
            $note = $coin_name;
        }elseif ($trade['mainCoinType'] == 195 && $trade['coinType'] == 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'){
            // USDT-TRC20
            $wallet = UserWallet::query()->where(['coin_name'=>'USDT','trx_wallet_address' => $trade['address']])->first();
            $note = 'USDT-TRC20';
        }else{
            return;
        }

        $is_exist = Recharge::query()->where(['txid' => $trade['txId'],'address'=>$trade['address']])->exists();
        if($is_exist) return;

        DB::beginTransaction();
        try {

            $amount = custom_number_format($trade['amount'] / pow(10,$trade['decimals']),8);

            // 更新用户余额
            $user = User::query()->findOrFail($wallet['user_id']);
            $user->update_wallet_and_log($wallet['coin_id'],'usable_balance',$amount,UserWallet::asset_account,'recharge');

            // 记录日志
            Recharge::query()->create([
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'coin_id' => $wallet['coin_id'],
                'coin_name' => $wallet['coin_name'],
                'datetime' => time(),
                'address' => $trade['address'],
                'txid' => $trade['txId'],
                'amount' => $amount,
                'status' => Recharge::status_pass,
                'note' => $note,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            info($e);
            DB::rollback();
        }
    }
}
