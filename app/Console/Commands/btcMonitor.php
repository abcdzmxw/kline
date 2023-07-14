<?php

namespace App\Console\Commands;

use App\Models\Mongodb\btcTransactions;
use App\Services\CoinService\Libs\BitcoinClient;
use Illuminate\Console\Command;

class btcMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btcMonitor';

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
                $transactions = $client->listtransactions("*",30);
            } catch (\RuntimeException $e) {
                info($e);
                $transactions = [];
            }

            echo count($transactions) . '--' .datetime() . "\r\n";
            if (count($transactions) > 0) {
                // 监听地址列表
                $address_list = \App\Models\UserWallet::query()->where('coin_id',2)->where(function($q){
                    $q->whereNotNull('wallet_address')->where('wallet_address','<>','');
                })->pluck('wallet_address')->toArray();

                foreach ($transactions as $transaction){
                    if($transaction['category'] == 'receive' && $transaction['confirmations'] >= 6 && isset($transaction['address'])){
                        if(in_array($transaction['address'],$address_list)){
                            $txid = $transaction['txid']; // 交易hash
                            // 确认交易是否已经存在;
                            $is_exist = btcTransactions::query()->where('txid',$txid)->first();
                            if($is_exist){
                                // 已存在
                                $data = $is_exist->toArray();
                                if($data['status'] == 0) \App\Jobs\Deposit::dispatch($data)->onQueue('deposit');
                                continue;
                            }else{
                                $amount = custom_number_format($transaction['amount'],8);
                                $data = $transaction;
                                $data['to'] = $transaction['address'];
                                $data['amount'] = $amount;
                                $data['symbol'] = 'BTC';
                                $data['type'] = 'deposit';
                                if($amount >= 0.0001){
                                    // 交易金额太小 则被认为是交易手续费 不作为充值记录
                                    $data['status'] = 0;
                                    \App\Jobs\Deposit::dispatch($data)->onQueue('deposit');
                                }else{
                                    $data['status'] = 1;
                                }
                                btcTransactions::query()->create($data);
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
