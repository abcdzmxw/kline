<?php


namespace App\Services\CoinService;


use App\Libs\Ethtool\Callback;
use App\Services\CoinService\Interfaces\CoinServiceInterface;
use GuzzleHttp\Client;
use phpseclib\Math\BigInteger as BigNumber;
use Web3\Contract;
use Web3\Eth;
use Web3\Personal;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Utils;
use Web3\Web3;

class GethTokenService implements CoinServiceInterface
{
    private $web3;
    private $contract;
    public  $eth;
    private $personal;
    private $provider;
    private $contractAddress;

    private $apikey = 'cfaf58162bea2da0b03e76e2dd64207a06b27233779d5cc25176a92e0973';
    private $gwei = '400';
    private $gas = 80000;
    private $gas2 = 21656;

    public function __construct($contractAddress,$abi)
    {
        $this->password = config('coin.geth_pwd');
        $this->provider = config('coin.geth_host');
        $this->eth = new Eth($this->provider);
        $this->personal = new Personal($this->provider);
        $this->contractAddress = $contractAddress;
        $this->contract = new Contract(new HttpProvider(new HttpRequestManager($this->provider, 30)),$abi);
    }

    public function weiToUsdt($value)
    {
        list($bnq, $bnr) = Utils::fromWei($value, 'ether');
        $balance = $bnq->toString() + ($bnr->toString() / pow(10,6));
        return custom_number_format($balance,6);
    }

    public function getBalance($account)
    {
        $this->contract->at($this->contractAddress)->call('balanceOf',$account,function ($err,$data) use (&$balance){
            if ($data){
                return $balance = $this->weiToUsdt($data[0]);
            }
            return $balance = -1;
        });
        return $balance;
    }

    public function listAccounts()
    {
        $this->personal->listAccounts(function ($err, $account) use (&$accountList) {
            if ($err !== null) {
                // do something
                $accountList = 0;
                return 0;
            }
            $accountList = $account;
            dd($accountList);
        });
        return $accountList;
    }

    /*解锁账户*/
    public function unlockAccount($address,$password)
    {
        $param = [$address,$password,10];
        $result = $this->interactiveEth('personal_unlockAccount',$param);
        if ($result) return 1;return 0;
    }

    /*发起代币交易*/
    public function sendTransaction($from,$toAddress,$amount,$decimals = 18)
    {
        if ($this->unlockAccount($from,$this->password) != 1) return 0;

        // Token合约地址
        $contractAddress = $this->contractAddress;

        $bet = pow(10,$decimals); // 代币发布时小数点位数 decimals
        $value = base_convert(custom_number_format($amount * $bet,0), 10, 16);

        // nonce
        $cb = new Callback;
        $this->eth->getTransactionCount($from, 'latest', $cb);
        $nonce = $cb->result;
        $nonce = $nonce->toString();

        $gasPrice = Utils::toHex(Utils::toWei($this->getEthGasPrice('fast'),'Gwei'),true);
        $gas = $this->getGasUse();

        $this->contract->at($contractAddress)->send('transfer',$toAddress,$value,
            [
                "from"=>$from,
                "gas"=>$gas,
                "gasPrice"=>$gasPrice,
                'nonce' => $nonce == 0 ? '0x0' : Utils::toHex($nonce, true),
            ]
            ,function ($err,$data) use (&$result) {
                if ($err !== null){
                    return $result = 0;
                }
                if (strlen($data) <5) return 0;//成功但没有获取到hash
                return $result = $data;
            });
        return $result;
    }

    public function collection($from,$to,$value)
    {
        $contractAddress = config('coin.erc20_usdt.contractAddress');
        $password = $this->password;
        if ($this->unlockAccount($from,$password) != 1) return 0;

        $gasPrice = Utils::toHex(Utils::toWei($this->getEthGasPrice('fast'),'Gwei'),true);
        $gas = $this->getGasUse();

//        $value = "0x" . base_convert(bcmul($value,'1000000000000000000',0),10,16);
        $bet = 1000000; // 代币发布时小数点位数 decimals
        $value = base_convert(bcmul($value,$bet,0), 10, 16);

        // nonce
        $cb = new Callback;
        $this->eth->getTransactionCount($from, 'latest', $cb);
        $nonce = $cb->result;
        $nonce = $nonce->toString();

        $this->contract->at($contractAddress)->send('transfer',$to,$value,
            [
                "from"=>$from,
                "gas"=>$gas,
                "gasPrice"=>$gasPrice,
                'nonce' => $nonce == 0 ? '0x0' : Utils::toHex($nonce, true),
//                'value' => '0x0',
//                'data' => '0x' . 'a9059cbb' . str_pad(substr($to, 2), 64, "0", STR_PAD_LEFT) . str_pad($value, 64, "0", STR_PAD_LEFT),
            ]
            ,function ($err,$data) use (&$result) {
                if ($err !== null){
                    return $result = 0;
                }
                if (strlen($data) < 5) return 0;//成功但没有获取到hash
                return $result = $data;
            }
        );
        return $result;
    }

    /*获取symbol*/
    public function getSymbol()
    {
        $this->contract->at($this->contractAddress)->call('symbol',function ($err,$data) use (&$result) {
            if ($err){
                return $result = 0;
            }
            return $result = $data[0];
        });
        return $result;
    }

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
//        dd(\GuzzleHttp\json_decode($rsp->getBody()));
        return \GuzzleHttp\json_decode($rsp->getBody())->result;
    }

    public function getTransaction($transactionId)
    {
        // TODO: Implement getTransaction() method.
    }

    public function newAccount()
    {
        // TODO: Implement newAccount() method.
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
