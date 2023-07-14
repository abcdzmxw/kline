<?php

namespace App\Console\Commands;

use App\Models\Mongodb\omniusdtTransactions;
use App\Services\CoinService\Libs\BitcoinClient;
use Illuminate\Console\Command;

class omniusdtMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'omniusdtMonitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        return false;
        /** 弃用，自建节点才需要 */
        /**
        $client = new BitcoinClient(env('OMNICORE_USERNAME'), env('OMNICORE_UPASSWORD'),env('OMNICORE_HOST'),env('OMNICORE_PORT'));

        while (true) {
            try {
                $transactions = $client->omni_listtransactions("*",30);
            } catch (\RuntimeException $e) {
                info($e);
                $transactions = [];
            }

            echo count($transactions) . '--' .datetime() . "\r\n";
            if (count($transactions) > 0) {
                // 监听地址列表
                $address_list = \App\Models\UserWallet::query()->where('coin_id',1)->where(function($q){
                    $q->whereNotNull('omni_wallet_address')->where('omni_wallet_address','<>','');
                })->pluck('omni_wallet_address')->toArray();

                foreach ($transactions as $transaction){
                    // 如果事务已经验证且确认数大于等于6，那么被认为是一条有效的充值记录
                    if($transaction['type'] == 'Simple Send' && $transaction['valid'] == true && $transaction['confirmations'] >= 6){
                        if($transaction['propertyid'] == 31 && in_array($transaction['referenceaddress'],$address_list)){
                            $txid = $transaction['txid']; // 交易hash
                            // 确认交易是否已经存在;
                            $is_exist = omniusdtTransactions::query()->where('txid',$txid)->first();
                            if($is_exist){
                                // 已存在
                                $data = $is_exist->toArray();
                                if($data['status'] == 0) \App\Jobs\Deposit::dispatch($data)->onQueue('deposit');
                                continue;
                            }else{
                                $data = $transaction;
                                $data['from'] = $transaction['sendingaddress'];
                                $data['to'] = $transaction['referenceaddress'];
                                $data['amount'] = custom_number_format($transaction['amount'],8);
                                $data['symbol'] = 'BTC_USDT';
                                $data['status'] = 0;
                                $data['type'] = 'deposit';
                                omniusdtTransactions::query()->create($data);

                                \App\Jobs\Deposit::dispatch($data)->onQueue('deposit');
                            }
                        }
                    }

                }
            }

            sleep(5);
        }
        */
    }
}
