<?php

namespace App\Jobs;

use App\Models\Mongodb\btcTransactions;
use App\Models\Mongodb\omniusdtTransactions;
use App\Models\Mongodb\Transactions;
use App\Models\Mongodb\TrxusdtTransactions;
use App\Models\Mongodb\USDTTransactions;
use App\Models\Recharge;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\WalletCollection;
use App\Services\CoinService\BitCoinService;
use App\Services\CoinService\GethService;
use App\Services\CoinService\GethTokenService;
use App\Services\CoinService\OmnicoreService;
use App\Services\CoinService\TronService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class Deposit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $transaction;

    /**
     * Create a new job instance.
     * @param $transaction
     * @return void
     */
    public function __construct($transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        return false;
        /** 弃用，自建节点才需要 */
        /**
        if (blank($this->transaction)) {
            return;
        }
        $transaction = $this->transactionDetail($this->transaction['symbol'],$this->transaction['txid']);
        if(blank($transaction)) return;

        if ($transaction['symbol'] == "BTC_USDT") {
            $wallet = UserWallet::query()->where(['coin_name'=>'USDT','omni_wallet_address' => $transaction['to']])->first();
            $note = 'omni_usdt';

            $is_exist = Recharge::query()->where(['txid' => $transaction['txid'],'coin_name'=>'USDT'])->exists();
        } elseif ($transaction['symbol'] == "ETH_USDT") {
            $wallet = UserWallet::query()->where(['coin_name'=>'USDT','wallet_address' => $transaction['to']])->first();
            $note = 'ERC20_USDT';

            $is_exist = Recharge::query()->where(['txid' => $transaction['txid'],'coin_name'=>'USDT'])->exists();
        }elseif ($transaction['symbol'] == "TRX_USDT") {
            $wallet = UserWallet::query()->where(['coin_name'=>'USDT','trx_wallet_address' => $transaction['to']])->first();
            $note = 'TRC20_USDT';

            $is_exist = Recharge::query()->where(['txid' => $transaction['txid'],'coin_name'=>'USDT'])->exists();
        } else {
            $wallet = UserWallet::query()->where(['coin_name'=>$transaction['symbol'],'wallet_address' => $transaction['to']])->first();
            $note = $transaction['symbol'];

            if(in_array($transaction['symbol'],['BTC','ETH','TRX'])){
                $is_exist = Recharge::query()->where('txid',$transaction['txid'])->whereIn('coin_name',[$transaction['symbol'],'USDT'])->exists();
            }else{
                $is_exist = Recharge::query()->where('txid',$transaction['txid'])->where('coin_name',$transaction['symbol'])->exists();
            }
        }

        if($is_exist) return;

        DB::beginTransaction();
        try {

            $transaction->update(['status' => 1]);

            // 更新用户余额
            $user = User::query()->findOrFail($wallet['user_id']);
            $user->update_wallet_and_log($wallet['coin_id'],'usable_balance',$transaction['amount'],UserWallet::asset_account,'recharge');

            // 记录日志
            Recharge::query()->create([
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'coin_id' => $wallet['coin_id'],
                'coin_name' => $wallet['coin_name'],
                'datetime' => time(),
                'address' => $transaction['to'],
                'txid' => $transaction['txid'],
                'amount' => $transaction['amount'],
                'status' => Recharge::status_pass,
                'note' => $note,
            ]);

            // 创建一条归集任务 定时归集
            switch ($transaction['symbol']){
                case 'BTC':
                    $min_amount = config('coin.collect_min_amount.btc');
                    $to = \App\Models\CenterWallet::query()->where('center_wallet_account','btc_collection_account')->value('center_wallet_address');
                    break;
                case 'ETH':
                    $min_amount = config('coin.collect_min_amount.eth');
                    $to = \App\Models\CenterWallet::query()->where('center_wallet_account','eth_collection_account')->value('center_wallet_address');
                    break;
                case 'ETH_USDT':
                    $min_amount = config('coin.collect_min_amount.usdt');
                    $to = \App\Models\CenterWallet::query()->where('center_wallet_account','eth_collection_account')->value('center_wallet_address');
                    break;
                case 'BTC_USDT':
                    $min_amount = config('coin.collect_min_amount.usdt');
                    $to = \App\Models\CenterWallet::query()->where('center_wallet_account','btc_collection_account')->value('center_wallet_address');
                    break;
                case 'TRX_USDT':
                    $min_amount = config('coin.collect_min_amount.usdt');
                    $to = \App\Models\CenterWallet::query()->where('center_wallet_account','trx_collection_account')->value('center_wallet_address');
                    break;
            }
            $balance = $this->getBalance($transaction['symbol'],$transaction['to']);
            if( !empty($to) && !empty($min_amount) && $balance >= $min_amount ){
                WalletCollection::query()->create([
                    'symbol' => $transaction['symbol'],
                    'from' => $transaction['to'],
                    'to' => $to,
                    'amount' => $balance,
                    'txid' => '',
                    'datetime' => time(),
                    'note' => '',
                    'status' => 0,
                ]);
            }

            DB::commit();

        } catch (\Exception $e) {
            info($e);
            DB::rollback();
        }
        */
    }

    private function transactionDetail($symbol,$txid)
    {
        switch ($symbol){
            case 'BTC':
                return btcTransactions::query()->where(['txid'=>$txid,'status'=>0,'symbol'=>$symbol])->first();
                break;
            case 'ETH':
                return Transactions::query()->where(['txid'=>$txid,'status'=>0,'symbol'=>$symbol])->first();
                break;
            case 'ETH_USDT':
                return USDTTransactions::query()->where(['txid'=>$txid,'status'=>0,'symbol'=>$symbol])->first();
                break;
            case 'BTC_USDT':
                return omniusdtTransactions::query()->where(['txid'=>$txid,'status'=>0,'symbol'=>$symbol])->first();
                break;
            case 'TRX_USDT':
                return TrxusdtTransactions::query()->where(['txid'=>$txid,'status'=>0,'symbol'=>$symbol])->first();
                break;
        }
    }

    private function getBalance($symbol,$address)
    {
        switch ($symbol){
            case 'BTC':
                return (new BitCoinService())->getBalance($address);
                break;
            case 'ETH':
                return (new GethService())->getBalance($address);
                break;
            case 'ETH_USDT':
                $contractAddress = config('coin.erc20_usdt.contractAddress');
                $abi = config('coin.erc20_usdt.abi');
                return (new GethTokenService($contractAddress,$abi))->getBalance($address);
                break;
            case 'BTC_USDT':
                return (new OmnicoreService())->getBalance($address);
                break;
            case 'TRX_USDT':
                return (new TronService())->getTokenBalance($address);
                break;
        }
    }

}
