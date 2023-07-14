<?php

namespace App\Console\Commands;

use App\Models\Mongodb\USDTTransactions;
use Illuminate\Console\Command;
use App\Jobs\CoinCollection;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Utils;
use Web3\Web3;
use App\Libs\Ethtool\Callback;

class ethMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ethMonitor';

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
        /** 弃用，自建节点才需要*/
        /**
        $web3 = new Web3(new HttpProvider(new HttpRequestManager(config('coin.geth_host'), 30)));
        $cb = new Callback();

        $web3->eth->newBlockFilter($cb);
        $fid = $cb->result;

        while (true) {
            // ETH地址列表
            $address_list = \App\Models\UserWallet::query()->where('coin_id',3)->where(function($q){
                $q->whereNotNull('wallet_address')->where('wallet_address','<>','');
            })->pluck('wallet_address')->toArray();

            // 手续费地址 系统ETH手续费账户转到用户钱包 （一般用作代币归集时先转一点ETH到用户账户作为手续费） 监听到from地址为手续费地址
            $eth_fee_account = \App\Models\CenterWallet::query()->where('center_wallet_account','eth_fee_account')->value('center_wallet_address');

            $web3->eth->getFilterChanges($fid, $cb);
            $blocks = $cb->result;
            echo count($blocks) . '--' .datetime() . "\r\n";
            if (count($blocks) > 0) {
                foreach ($blocks as $hash) {
                    try {
                        $web3->eth->getBlockByHash($hash, true, $cb);
                        $block = $cb->result;
                    } catch (\Exception $e) {
                        info('getBlockByHash:' . json_encode($hash) . '===' . $e->getCode() . '--' . $e->getMessage());
                        info($e);
                    }

//                  echo json_encode($block) . "\r\n";
                    foreach ($block->transactions as $tx){
                        $data = get_object_vars($tx);
                        if(in_array($data['to'],$address_list)){
                            // ETH交易
                            if($data['from'] == $eth_fee_account){
                                // TODO 手续费转账成功 触发用户账户代币归集
//                                CoinCollection::dispatch(['symbol'=>'ETH_USDT','address'=>$data['to']])->onQueue('coinCollection');

                                list($bnq, $bnr) = Utils::fromWei($data['value'], 'ether');
                                $data['amount'] = $bnq->toString() + ($bnr->toString() / pow(10,18));
                                $data['symbol'] = 'ETH';
                                $data['txid'] = $data['hash'];
                                $data['status'] = 1;
                                $data['type'] = 'erc20_fee';
                                \App\Models\Mongodb\Transactions::query()->create($data);
                            }else{
                                list($bnq, $bnr) = Utils::fromWei($data['value'], 'ether');
                                $data['amount'] = $bnq->toString() + ($bnr->toString() / pow(10,18));
                                $data['symbol'] = 'ETH';
                                $data['txid'] = $data['hash'];
                                $data['status'] = 0;
                                $data['type'] = 'deposit';
                                \App\Models\Mongodb\Transactions::query()->create($data);

                                \App\Jobs\Deposit::dispatch($data)->onQueue('deposit');
                            }
                        }elseif($data['to'] == '0xdac17f958d2ee523a2206206994597c13d831ec7'){
                            // erc20 USDT代币交易
                            $func = substr($data['input'],0,10);
                            if($func == '0xa9059cbb'){
                                $input_str = substr($data['input'],10);
                                $input_data = str_split($input_str,64);
                                $to = '0x' . ltrim($input_data[0],'0');
                                $value = hexdec(ltrim($input_data[1],'0')) / pow(10,6);
                                if(in_array($to,$address_list)){
                                    $txid = $data['hash']; // 交易hash

                                    // 确认交易是否已经存在;
                                    $is_exist = USDTTransactions::query()->where('transactionHash',$txid)->exists();
                                    if($is_exist){
                                        // 已存在
                                        continue;
                                    }else{
                                        $data = [
                                            'address' => '0xdac17f958d2ee523a2206206994597c13d831ec7',
                                            'topics' => '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef',
                                            'data' => $data['input'],
                                            'blockNumber' => $data['blockNumber'],
                                            'transactionHash' => $txid,
                                            'transactionIndex' => $data['transactionIndex'],
                                            'blockHash' => $data['blockHash'],
                                            'logIndex' => $data['nonce'],
                                            'removed' => false,
                                            'from' => $data['from'],
                                            'to' => $to,
                                        ];
                                        $data['amount'] = $value;
                                        $data['symbol'] = 'ETH_USDT';
                                        $data['txid'] = $txid;
                                        $data['status'] = 0;
                                        $data['type'] = 'deposit';
                                        USDTTransactions::query()->create($data);

                                        \App\Jobs\Deposit::dispatch($data)->onQueue('deposit');
                                    }
                                }
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
