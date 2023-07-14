<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class UpdateExchangeRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updateExchangeRate';

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
//        $url = 'https://api.exchangerate-api.com/v4/latest/USD';
        $url = 'https://v6.exchangerate-api.com/v6/122ce32619f3971f7d8ee426/latest/USD';

        $data = $this->curl($url);
        $data = json_decode($data,true);
        if(!blank($data) && isset($data['conversion_rates'])){
            $rate = $data['conversion_rates']['CNY'] ?? '6.836';
            if(!blank($rate)) Cache::store('redis')->put('usd2cny',$rate);
        }else{
            $rate = '6.836';
            if(!blank($rate)) Cache::store('redis')->put('usd2cny',$rate);
        }
    }

    private function curl($url,$req_method = 'GET',$postdata=[])
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        if ($req_method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
        }
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_TIMEOUT,60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
        ]);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        return $output;
    }
}
