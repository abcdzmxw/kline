<?php

namespace App\Jobs;

use App\Models\Recharge;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class UdunDeposit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $trade;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($trade)
    {
        $this->trade = $trade;
    }

    /**
     * 主币种编号	子币种编号	币种简称	币种英文名	    币种中文名称	精度
        0	    0	        BTC	    Bitcoin	          比特币	    8
        60	    60	        ETH 	Ethereum	      以太坊	    18
        0	    31	        USDT	Tether USD	      泰达币	    8
        520	    520	        CNT	    CNT	              测试币	    18
        5	    5	        DASH	DASH	          达世币	    8
        133	    133	        ZEC 	ZEC	              大零币	    8
        145	    145	        BCH	    Bitcoincash	      比特币现金	8
        61	    61	        ETC	    Ethereum Classic  以太坊经典	18
        2	    2	        LTC	    LTC	                莱特币	8
        2301	2301	    QTUM	QTUM	            量子链币	8
        502	    502	        GCC	    GalaxyChain		            8
        60	    合约地址	    eth代币	eth代币		根据代币具体情况而定
        144	    144	        XRP	    Ripple	            瑞波币	6
        194	    194	        EOS	    EOS	                柚子币	4
        194	    194	        EOS	    EOS	                柚子币	4
        2304	2304	    IOTE	IOTE	            IOTE	8
        2303	2303	    VDS	    Vollar	          Vollar币	8
     */
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (blank($this->trade)) {
            return;
        }
        $trade = $this->trade;

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
        }elseif ($trade['mainCoinType'] == 2 && $trade['coinType'] == 2){
            // LTC
            $coin_name = 'LTC';
            $wallet = UserWallet::query()->where(['coin_name'=>$coin_name,'wallet_address' => $trade['address']])->first();
            $note = $coin_name;
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
