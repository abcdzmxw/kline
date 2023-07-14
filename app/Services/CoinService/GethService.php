<?php


namespace App\Services\CoinService;


use App\Libs\Ethtool\Callback;
use App\Services\CoinService\Interfaces\CoinServiceInterface;
use GuzzleHttp\Client;
use phpseclib\Math\BigInteger as BigNumber;
use Web3\Eth;
use Web3\Personal;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Utils;
use Web3\Web3;

class GethService implements CoinServiceInterface
{
    public $web3;
    public $eth;
    public $personal;
    private $provider;

    private $apikey = 'cfaf58162bea2da0b03e76e2dd64207a06b27233779d5cc25176a92e0973';
    private $gwei = '400';
    private $gas = 80000;
    private $gas2 = 21656;

    public function __construct()
    {
        $this->password = config('coin.geth_pwd');
        $this->provider = config('coin.geth_host');
        $this->web3 = new Web3(new HttpProvider(new HttpRequestManager($this->provider, 30)));
        $this->personal = new Personal($this->provider);
        $this->eth = new Eth($this->provider);
    }

    public function weiToEther($value)
    {
        list($bnq, $bnr) = Utils::fromWei($value, 'ether');

        // 后面部分舍去
        $power = pow(10,5); // 保留5位小数
        $bnr_num = floor($bnr->toString() / pow(10,18) * $power) / $power;
        return $result = $bnq->toString() + $bnr_num;
    }

    public function getBalance($account)
    {
        $this->web3->eth->getBalance($account,function ($err,$data) use (&$result){
            if ($data){
                return $result = $this->weiToEther($data);
            }
            return $result = -1;
        });
        return $result;
    }

    public function listAccounts()
    {
        $this->web3->personal->listAccounts(function ($err, $account) use (&$accountList) {
            if ($err !== null) {
                // do something
                $accountList = 0;
                return 0;
            }
            $accountList = $account;
        });
        return $accountList;
    }

    public function getTransaction($transactionId)
    {
        // TODO: Implement getTransaction() method.
    }

    public function newAccount()
    {
        $password = $this->password;
        return $this->interactiveEth('personal_newAccount',[$password]);
    }

    /*直接用guzzlehttp与以太坊交互*/
    public function interactiveEth($method,array $params)
    {
        $opts = [
            'json' => [
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => $params,
                'id' => time()
            ]
        ];
        $rsp = (new Client())->post($this->provider,$opts);
        if (isset(\GuzzleHttp\json_decode($rsp->getBody())->error)) return 0;

        return \GuzzleHttp\json_decode($rsp->getBody())->result;
    }

    /*转账交易
    参数1:转出账户
    参数2:转入账户
    参数3:数量  单位为eth
    参数4:密码
    参数5:gaslimit      参数6:gasprice 单价 单位为gwei
    */
    public function sendTransaction($from,$to,$value,$gas=null,$gasPrice=null)
    {
        if(blank($gasPrice)){
//            $gasPrice = $this->interactiveEth('eth_gasPrice',[]);
            $gasPrice = Utils::toHex(Utils::toWei($this->getEthGasPrice('fast'),'Gwei'),true);
        }
        if(blank($gas)){
            $gas = $this->getGasUse();
        }

        $password = $this->password;
        $value = "0x" . base_convert(bcmul($value,'1000000000000000000',0),10,16);

        // nonce
        $cb = new Callback;
        $this->eth->getTransactionCount($from, 'latest', $cb);
        $nonce = $cb->result;
        $nonce = $nonce->toString();

        $transaction = [[
            "from"=>$from,
            "to"=>$to,
            "gas"=>$gas,
            "gasPrice"=>$gasPrice,
            "value"=>$value,
            "data"=>"0xd46e8dd67c5d32be8d46e8dd67c5d32be8058bb8eb970870f072445675058bb8eb970870f072445675",
            'nonce' => $nonce == 0 ? '0x0' : Utils::toHex($nonce, true),
        ],"{$password}"];
//        $transaction = [[
//            "from"=>"0x7b8172e885fba4f0fd593ede603c067a7fb17971",
//            "to"=>"0x3c119f11ea139cc9432cc07d79d239c31acbb857",
//            "gas"=>"0x76c0",
//            "gasPrice"=>"0x9184e72a000",
//            "value"=>"0x1",
//            "data"=>"0xd46e8dd67c5d32be8d46e8dd67c5d32be8058bb8eb970870f072445675058bb8eb970870f072445675",
//        ],"123456"];
        return $this->interactiveEth('personal_sendTransaction',$transaction);
    }

    // 代币归集时 发送代币交易手续费
    public function sendFee($address)
    {
        $eth_fee_account = \App\Models\CenterWallet::query()->where('center_wallet_account','eth_fee_account')->value('center_wallet_address');
        if(blank($eth_fee_account)) return false;
//        $gasPrice = $this->interactiveEth('eth_gasPrice',[]);
        $gasPrice = Utils::toHex(Utils::toWei($this->getEthGasPrice('fast'),'Gwei'),true);
        $gas = $this->getGasUse();
        $gas2 = Utils::toHex(21656,true);

        $password = $this->password;
        $fee = new BigNumber((hexdec($gasPrice) * hexdec($gas)));
        $send_value = Utils::toHex($fee,true);

        // nonce
        $cb = new Callback;
        $this->eth->getTransactionCount($eth_fee_account, 'latest', $cb);
        $nonce = $cb->result;
        $nonce = $nonce->toString();

        $transaction = [[
            "from"=>$eth_fee_account,
            "to"=>$address,
            "gas"=>$gas2,
            "gasPrice"=>$gasPrice,
            "value"=>$send_value,
            "data"=>"0xd46e8dd67c5d32be8d46e8dd67c5d32be8058bb8eb970870f072445675058bb8eb970870f072445675",
            'nonce' => $nonce == 0 ? '0x0' : Utils::toHex($nonce, true),
        ],"{$password}"];
        return $this->interactiveEth('personal_sendTransaction',$transaction);
    }

    // 归集
    public function collection($from,$to,$value)
    {
//        $gasPrice = $this->interactiveEth('eth_gasPrice',[]);
        $gasPrice = Utils::toHex(Utils::toWei($this->getEthGasPrice('fast'),'Gwei'),true);
        $gas = Utils::toHex(30400,true);
        // $gas = $this->getGasUse();

        $password = $this->password;
        $a = new BigNumber(Utils::toWei((string)$value,'ether')->toString());
        $b = new BigNumber((hexdec($gasPrice) * hexdec($gas)));
        $send_value = Utils::toHex($a->subtract($b),true);

        // nonce
        $cb = new Callback;
        $this->eth->getTransactionCount($from, 'latest', $cb);
        $nonce = $cb->result;
        $nonce = $nonce->toString();

        $transaction = [[
            "from"=>$from,
            "to"=>$to,
            "gas"=>$gas,
            "gasPrice"=>$gasPrice,
            "value"=>$send_value,
            "data"=>"0xd46e8dd67c5d32be8d46e8dd67c5d32be8058bb8eb970870f072445675058bb8eb970870f072445675",
            'nonce' => $nonce == 0 ? '0x0' : Utils::toHex($nonce, true),
        ],"{$password}"];
        return $this->interactiveEth('personal_sendTransaction',$transaction);
    }

    /**
     * 以太坊网络上的快速，标准和安全的低汽油价格
     */
    public function getEthGasPrice($t = 'average')
    {
//        $url = 'https://data-api.defipulse.com/api/v1/egs/api/ethgasAPI.json?api-key=' . $this->apikey;
        $url = 'https://ethgasstation.info/api/ethgasAPI.json?api-key=' . $this->apikey;
        $rsp = (new Client())->get($url);
        if (isset(\GuzzleHttp\json_decode($rsp->getBody())->error)) return $this->gwei;

        $data = \GuzzleHttp\json_decode($rsp->getBody());
        if($t == 'average'){
            if(!isset($data->average)) return $this->gwei;
            $average = $data->average / 10;
            return (string)$average;
//            return $average > $this->gwei ? $this->gwei : (string)$average;
        }else{
            if(isset($data->fast)){
                $fast = $data->fast / 10;
                return $fast > $this->gwei ? $this->gwei : (string)$fast;
            }
            return $this->gwei;
        }
    }

    // 获取交易预估gas用量
    public function getGasUse($estimate = false,$transaction = [])
    {
        if($estimate){
            return Utils::toHex($this->gas,true);
        }else{
            $gas = $this->interactiveEth('eth_estimateGas',$transaction);
            return $gas ?: Utils::toHex($this->gas,true);
        }
    }

}
