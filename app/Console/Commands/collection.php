<?php

namespace App\Console\Commands;

use App\Models\UserWallet;
use App\Models\WalletCollection;
use App\Services\CoinService\BitCoinService;
use App\Services\CoinService\GethService;
use App\Services\CoinService\GethTokenService;
use App\Services\CoinService\OmnicoreService;
use App\Services\CoinService\TronService;
use Illuminate\Console\Command;
use phpseclib\Math\BigInteger as BigNumber;
use Web3\Utils;

class collection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collection';

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
        $tasks = WalletCollection::query()->where('status',0)->get();
        foreach ($tasks as $task){
            switch ($task['symbol']) {
                case "BTC":
                    $this->btcCollection($task);
                    break;
                case "ETH":
                    $this->ethCollection($task);
                    break;
                case "ETH_USDT":
                    $this->erc20usdtCollection($task);
                    break;
                case "BTC_USDT":
                    $this->omniusdtCollection($task);
                    break;
                case "TRX_USDT":
                    $this->trxusdtCollection($task);
                    break;
            }
        }
        */
    }

    private function ethCollection($task)
    {
        $res = (new GethService())->collection($task['from'],$task['to'],$task['amount']);
        if($res){
            $task->update(['status'=>1,'txid'=>$res]);
        }
        return $res;
    }

    private function erc20usdtCollection($task)
    {
        $contractAddress = config('coin.erc20_usdt.contractAddress');
        $abi = config('coin.erc20_usdt.abi');

        // 判断用户地址有没有可用的ETH手续费
        $gasPrice = Utils::toHex(Utils::toWei((new GethService())->getEthGasPrice('fast'),'Gwei'),true);
        $gas = Utils::toHex(60000,true);
        $collect_fee = new BigNumber((hexdec($gasPrice) * hexdec($gas)));
        $min_fee = (new GethService())->weiToEther($collect_fee);
        $ether = (new GethService())->getBalance($task['from']);
        if($ether < $min_fee){
            $fee_res = (new GethService())->sendFee($task['from']);
            info('err="insufficient funds for gas * price + value"' . '---' . $task['from']);
            $task->update(['txid'=>$fee_res]);
            return $fee_res;
        }else{
            $res = (new GethTokenService($contractAddress,$abi))->collection($task['from'],$task['to'],$task['amount']);
            if($res){
                $task->update(['status'=>1,'txid'=>$res]);
            }
            return $res;
        }
    }

    private function btcCollection($task)
    {
        $res = (new BitCoinService())->collection($task['from'],$task['to'],$task['amount']);
        if($res){
            $task->update(['status'=>1,'txid'=>$res]);
        }
        return $res;
    }

    private function omniusdtCollection($task)
    {
        $res = (new OmnicoreService())->collection($task['from'],$task['to'],$task['amount']);
        if($res){
            $task->update(['status'=>1,'txid'=>$res]);
        }
        return $res;
    }

    private function trxusdtCollection($task)
    {
//        $res = (new TronService())->collectionUSDT($task['from'],$task['to'],$task['amount']);
        $wallet = UserWallet::query()->where('coin_id',1)->where('trx_wallet_address',$task['from'])->first();
        $address = $wallet['trx_wallet_address'];
        $private_key = $wallet['private_key'];
        if(blank($address)) return false;
        $to = $task['to'];
        $amount = $task['amount'];
        $res = (new TronService())->sendTrc20Transaction($address,$private_key,$to,$amount);
        if($res){
            $task->update(['status'=>1,'txid'=>$res]);
        }
        return $res;
    }

}
