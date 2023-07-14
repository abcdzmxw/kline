<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;

class ethtx extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ethtx';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'eth 交易监测';

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
        // ETH地址列表
//        $address_list = \App\Models\UserWallet::query()->where('coin_id',3)->where(function($q){
//            $q->whereNotNull('wallet_address')->where('wallet_address','<>','');
//        })->select('wallet_address')->cursor();

        $address_list = [['wallet_address'=>'0x0b569acf6a283cb8a79939106aee7a270d2cf9be']];

        foreach ($address_list as $item){
            $address = $item['wallet_address'];
            $txs = $this->getTokentx($address);
            if(blank($txs)) continue;
            foreach ($txs as $tx){
                if($tx['to'] == $address){
                    $txid = $tx['hash']; // 交易hash
                    // TODO
                }
            }
        }
    }

    private function getTxlist($address)
    {
        $apikey = 'QJKBXS5HZTWPEYWUZHDCSZUR9TJA3EH5Z8';
        $url = 'https://api.etherscan.io/api?module=account&action=txlist&address='.$address.'&startblock=0&endblock=999999999&sort=desc&apikey='.$apikey;

        $rsp = (new Client())->get($url);
        if (isset(\GuzzleHttp\json_decode($rsp->getBody())->error)) return [];

        $data = \GuzzleHttp\json_decode($rsp->getBody());
        return $data;
    }

}
