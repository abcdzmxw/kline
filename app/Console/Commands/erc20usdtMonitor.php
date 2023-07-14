<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Web3;
use App\Libs\Ethtool\Callback;
use App\Models\Mongodb\USDTTransactions;
use Web3\Utils;

class erc20usdtMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erc20usdtMonitor';

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
        $web3 = new Web3(new HttpProvider(new HttpRequestManager(config('coin.geth_host'), 60)));
        $cb = new Callback();

        while (true) {
            $fid = Cache::store('redis')->get('erc20usdt_monitor_fid');
            if(blank($fid)){
                $fid = $this->newFilter($web3,$cb);
            }

            try {
                $web3->eth->getFilterLogs($fid,$cb);
                $logs = $cb->result;
            } catch (\RuntimeException $e) {
                info($e);
                if($e->getCode() == 32000){
                    // 过滤器被卸载 重新创建
                    $fid = $this->newFilter($web3,$cb);
                    $web3->eth->getFilterLogs($fid,$cb);
                    $logs = $cb->result;
                }else{
                    break;
                }
            }

            echo count($logs) . '--' .datetime() . "\r\n";
            if (count($logs) > 0) {
                // 监听地址列表
                $address_list = \App\Models\UserWallet::query()->where('coin_id',1)->where(function($q){
                    $q->whereNotNull('wallet_address')->where('wallet_address','<>','');
                })->pluck('wallet_address')->toArray();

                foreach ($logs as $log) {
                    $log = get_object_vars($log);

                    if(! isset($log['topics'][2])){
                        info(json_encode($log));
                        continue;
                    }
                    $to = '0x' . substr($log['topics'][2],26);
                    if(in_array($to,$address_list)){
                        $txid = $log['transactionHash']; // 交易hash
                        $logIndex = $log['logIndex'];
                        // 确认交易是否已经存在;
                        $is_exist = USDTTransactions::query()->where('transactionHash',$txid)->exists();
                        if($is_exist){
                            // 已存在
                            continue;
                        }else{
                            if(isset($log['data']) && !blank($log['data'])){
                                $value = hexdec(ltrim(Utils::stripZero($log['data']),'0')) / pow(10,6);
                                $data = [
                                    'address' => $log['address'],
                                    'topics' => $log['topics'][0],
                                    'data' => $log['data'],
                                    'blockNumber' => $log['blockNumber'],
                                    'transactionHash' => $log['transactionHash'],
                                    'transactionIndex' => $log['transactionIndex'],
                                    'blockHash' => $log['blockHash'],
                                    'logIndex' => $log['logIndex'],
                                    'removed' => $log['removed'],
                                    'from' => '0x' . substr($log['topics'][1],26),
                                    'to' => $to,
                                ];
                                $data['amount'] = $value;
                                $data['symbol'] = 'ETH_USDT';
                                $data['txid'] = $log['transactionHash'];
                                $data['status'] = 0;
                                $data['type'] = 'deposit';
                                USDTTransactions::query()->create($data);

                                \App\Jobs\Deposit::dispatch($data)->onQueue('deposit');
                            }
                        }
                    }
                }
            }

            sleep(5);
        }
    }

    private function newFilter($web3,$cb)
    {
        $web3->eth->newFilter([
//        "fromBlock"=> Utils::toHex(10859780,true),
//        "toBlock"=> Utils::toHex(10859780,true),
            "fromBlock"=> "latest",
            "toBlock"=> "latest",
            'address'=>'0xdac17f958d2ee523a2206206994597c13d831ec7', // usdt合约地址
            [
                "topics"=>["0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef"]
            ]
        ],$cb);
        $fid = $cb->result;
        Cache::store('redis')->put('erc20usdt_monitor_fid',$fid,86400);
        return $fid;
    }

}
