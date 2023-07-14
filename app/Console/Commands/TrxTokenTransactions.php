<?php

namespace App\Console\Commands;

use App\Models\Mongodb\TrxusdtTransactions;
use App\Services\CoinService\TronService;
use Illuminate\Console\Command;

class TrxTokenTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'TrxTokenTransactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'TRC20代币交易';

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
        $tron = new TronService();
        \App\Models\UserWallet::query()->where('coin_id',1)->where(function($q){
            $q->whereNotNull('trx_wallet_address')->where('trx_wallet_address','<>','');
        })->select('trx_wallet_address', 'user_id', 'coin_id', 'coin_name')->chunkById(1000, function ($address_list) use ($tron) {
            foreach ($address_list as $item) {
                $address = $item['trx_wallet_address'];
                $symbol = $item['coin_name'];
                $contractAddress = config('coin.trc20_' . strtolower($symbol) . '.contractAddress');
                if ($contractAddress == 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t') {
                    $decimals = 6;
                } else {
                    $decimals = 18;
                }
                $txs = $tron->getTokenTransactions($address, $contractAddress);
                if (empty($txs)) continue;
                foreach ($txs as $tx) {
                    if (strtolower($tx['to']) == strtolower($address)) {
//                        dump($address);
                        $hash = $tx['transaction_id']; // 交易hash
                        $amount = $this->fromTron($tx['value'], $decimals);

                        // 确认交易是否已经存在;
                        $is_exist = TrxusdtTransactions::query()->where(['address'=>$address,'hash'=>$hash])->first();
                        if($is_exist){
                            // 已存在
                            $trans = $is_exist;
                            if($trans['status'] == 0) \App\Jobs\Deposit::dispatch($trans)->onQueue('deposit');
                            continue;
                        }

                        $data = [
                            'from' => $tx['from'] ?? '',
                            'to' => $address,
                            'address' => $address,
                            'address_chain' => 'TRX',
                            'hash'    => $hash,
                            'txid'    => $hash,
                            'amount'  => $amount,
                            'status'  => 0,
                            'symbol'    => 'TRX_USDT',
                            'note'    => $symbol . '-TRC20',
                        ];
                        $trans = TrxusdtTransactions::query()->create($data);

                        \App\Jobs\Deposit::dispatch($trans)->onQueue('deposit');
                    }
                }
            }
        });
        */
    }

    private function fromTron($amount, $decimals = 6): float
    {
        return (float)bcdiv((string)$amount, (string)bcpow(10, $decimals), 8);
    }

}
