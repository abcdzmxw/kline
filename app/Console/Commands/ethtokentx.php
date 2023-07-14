<?php

namespace App\Console\Commands;

use App\Models\Mongodb\USDTTransactions;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class ethtokentx extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ethtokentx';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'erc20 Token 交易监测';

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

        // ETH地址列表
        $address_list = \App\Models\UserWallet::query()->where('coin_id',1)->where(function($q){
            $q->whereNotNull('wallet_address')->where('wallet_address','<>','');
        })->select('wallet_address')->cursor();

//        $address_list = [['wallet_address'=>'0x0b569acf6a283cb8a79939106aee7a270d2cf9be']];

        foreach ($address_list as $item){
            $address = $item['wallet_address'];
            $res = $this->getTokentx($address);
            if(blank($res)) continue;
            if($res->status == 0) continue;
            $txs = $res->result;
            foreach ($txs as $tx){
                $tx = get_object_vars($tx);
                if($tx['contractAddress'] == '0xdac17f958d2ee523a2206206994597c13d831ec7'){
                    if($tx['to'] == $address){
                        $txid = $tx['hash']; // 交易hash
                        $value = $tx['value'] / pow(10,6);

                        // 确认交易是否已经存在;
                        $is_exist = USDTTransactions::query()->where('transactionHash',$txid)->first();
                        if($is_exist){
                            // 已存在
                            $data = $is_exist->toArray();
                            if($data['status'] == 0) \App\Jobs\Deposit::dispatch($data)->onQueue('deposit');
                            continue;
                        }else{
                            $data = [
                                'address' => '0xdac17f958d2ee523a2206206994597c13d831ec7',
                                'topics' => '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef',
                                'data' => $tx['input'] ?? null,
                                'blockNumber' => $tx['blockNumber'],
                                'transactionHash' => $txid,
                                'transactionIndex' => $tx['transactionIndex'],
                                'blockHash' => $tx['blockHash'],
                                'logIndex' => $tx['nonce'],
                                'removed' => false,
                                'from' => $tx['from'],
                                'to' => $tx['to'],
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
        */
    }

    private function getTokentx($address)
    {
        $apikey = 'QJKBXS5HZTWPEYWUZHDCSZUR9TJA3EH5Z8';
        $url = 'https://api.etherscan.io/api?module=account&action=tokentx&address='.$address.'&startblock=0&endblock=999999999&sort=desc&apikey='.$apikey;

        try {
            $rsp = (new Client())->get($url);
            if (isset(\GuzzleHttp\json_decode($rsp->getBody())->error)) {
                return [];
            }

            $data = \GuzzleHttp\json_decode($rsp->getBody());
        }catch(\Exception $e){
            $data = null;
        }
        return $data;
    }

}
