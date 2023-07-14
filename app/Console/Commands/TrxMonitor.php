<?php

namespace App\Console\Commands;

use App\Models\Mongodb\TrxusdtTransactions;
use App\Services\CoinService\TronService;
use IEXBase\TronAPI\Support\Utils;
use IEXBase\TronAPI\TronAwareTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class TrxMonitor extends Command
{
    use TronAwareTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'TrxMonitor';

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
        $tron = new TronService();
        $blockNumKey = 'trx:blockNum';
//        $address_list = ['TMuA6YqfCeX8EhbfYEg5y7S4DqzSJireY9'];
        while (true){
            $address_list = \App\Models\UserWallet::query()->where('coin_id',1)->where(function($q){
                $q->whereNotNull('trx_wallet_address')->where('trx_wallet_address','<>','');
            })->pluck('trx_wallet_address')->toArray();

            $blockNum = Redis::get($blockNumKey);
            if(empty($blockNum)){
                $blockNum = $tron->getCurrentBlock();
            }
            $block = $tron->getBlockByNumber($blockNum);
            if(empty($block)){
                sleep(3);
                continue;
            }
            $transactions = $block['transactions'] ?? [];
            echo 'blockNum: --- ' . $blockNum . ' --- trans: --- ' . count($transactions) . ' -- ' . date('Y-m-d H:i:s') . "\r\n";
            if (count($transactions) > 0) {
                foreach ($transactions as $transaction){
                    try {
                        $hash = $transaction['txID'];
                        //判断 数据库 txId 有 就不用往下继续了

//                        $tx = $tron->getTransaction($hash);
                        $tx = $transaction;
//                        dump($tx);
                        //交易成功
                        if ($tx['ret'][0]['contractRet'] == "SUCCESS") {
                            $type = $tx['raw_data']['contract'][0]['type'];
                            if ($type == "TriggerSmartContract") {
                                //合约地址转账
                                //方法参数
                                $data = $tx['raw_data']['contract'][0]['parameter']['value']['data'];
                                $func = substr($data,0,8);
                                if($func == 'a9059cbb'){
                                    //调用者地址
                                    $owner_address = $this->fromHex($tx['raw_data']['contract'][0]['parameter']['value']['owner_address']);
                                    //合约地址
                                    $contract_address = $this->fromHex($tx['raw_data']['contract'][0]['parameter']['value']['contract_address']);

                                    $dataStr = substr($data,8);
                                    $strList = str_split($dataStr,64);
                                    if(count($strList) != 2){
                                        continue;
                                    }

//                                    $to_address = ltrim($strList[0],'0');
                                    $to_address = substr($strList[0],24);
                                    if(!(strpos($to_address, "41") === 0)){
                                        $to_address = '41' . $to_address;
                                    }
//                                    dd($to_address);
                                    $to_address = $this->fromHex($to_address);

                                    $amountStr = ltrim($strList[1],'0');
//                                    dd($strList,$to_address,$amountStr);
                                    if($contract_address == 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'){
                                        $decimals = config('coin.trc20_usdt.decimals');
                                        $tokenName = config('coin.trc20_usdt.tokenName');
                                        $symbol = 'TRX_USDT';
                                    }else{
                                        continue;
                                    }
                                    $amount = custom_number_format(bcMath( hexdec($amountStr), bcpow(10,$decimals),'/'),8);

//                                    dd($amount,$owner_address,$to_address,$hash,$type,$contract_address);
                                    if(in_array($to_address,$address_list)){
                                        info('TrxMonitor--' . 'to_address' . $to_address . json_encode($transaction));

                                        // 确认交易是否已经存在;
                                        $is_exist = TrxusdtTransactions::query()->where(['address'=>$to_address,'hash'=>$hash])->first();
                                        if($is_exist){
                                            // 已存在
                                            $trans = $is_exist;
                                            if($trans['status'] == 0) \App\Jobs\Deposit::dispatch($trans)->onQueue('deposit');
                                            continue;
                                        }

                                        $data = [
                                            'from' => $owner_address,
                                            'to' => $to_address,
                                            'address' => $to_address,
                                            'address_chain' => 'TRX',
                                            'hash'    => $hash,
                                            'txid'    => $hash,
                                            'amount'  => $amount,
                                            'status'  => 0,
                                            'symbol'    => 'TRX_USDT',
                                            'note'    => 'USDT-TRC20',
                                        ];
                                        $trans = TrxusdtTransactions::query()->create($data);

                                        \App\Jobs\Deposit::dispatch($trans)->onQueue('deposit');
                                    }
                                }

                            }
//                            elseif ($type == "TransferContract") {
//                                //trx 转账
//                                // 数量
//                                $amount = custom_number_format($this->fromTron($tx['raw_data']['contract'][0]['parameter']['value']['amount']),8);
//                                //调用者地址
//                                $owner_address = $this->fromHex($tx['raw_data']['contract'][0]['parameter']['value']['owner_address']);
//                                //转入地址
//                                $to_address = $this->fromHex($tx['raw_data']['contract'][0]['parameter']['value']['to_address']);
////                                dd($amount,$owner_address,$to_address,$hash,$type);
//                                if(in_array($to_address,$address_list)){
//                                    $center_fee_address = \App\Models\CenterWallet::query()->where('center_wallet_account','trx_fee_account')->value('center_wallet_address');
//                                    if($owner_address == $center_fee_address){
//                                        // 发送者是手续费地址 该笔交易是中心钱包分发矿工费
//                                        continue;
//                                    }
//
//                                    // 确认交易是否已经存在;
//                                    $is_exist = TrxusdtTransactions::query()->where(['address'=>$to_address,'hash'=>$hash])->first();
//                                    if($is_exist){
//                                        // 已存在
//                                        $trans = $is_exist;
//                                        if($trans['status'] == 0) \App\Jobs\Deposit::dispatch($trans)->onQueue('deposit');
//                                        continue;
//                                    }
//
//                                    $data = [
//                                        'from' => $owner_address,
//                                        'address' => $to_address,
//                                        'address_chain' => 'TRX',
//                                        'hash'    => $hash,
//                                        'amount'  => $amount,
//                                        'status'  => 0,
//                                        'code'    => 'TRX',
//                                        'note'    => 'TRX',
//                                    ];
//                                    $trans = TrxusdtTransactions::query()->create($data);
//
//                                    \App\Jobs\Deposit::dispatch($trans)->onQueue('deposit');
//                                }
//                            }
                        }
                    }catch (\Exception $e){
//                        dd($tx);
//                        throw $e;
                        info($e);
                        continue;
                    }
                }
            }

            // 更新区块高度
            Redis::set($blockNumKey, $blockNum + 1);

            sleep(2);
        }
        */
    }

}
