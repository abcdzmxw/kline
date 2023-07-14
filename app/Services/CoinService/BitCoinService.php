<?php


namespace App\Services\CoinService;


use App\Services\CoinService\Interfaces\CoinServiceInterface;
use App\Services\CoinService\Libs\BitcoinClient;
use GuzzleHttp\Client;

class BitCoinService implements CoinServiceInterface
{
    private $bitcoinClient;
    private $walletPassword;

    public function __construct()
    {
        $this->walletPassword = config('coin.omnicore_pwd');
        $this->bitcoinClient = new BitcoinClient(config('coin.omnicore_user'), config('coin.omnicore_pwd'),config('coin.omnicore_host'),config('coin.omnicore_port'));
    }

    public function collection($from,$to,$amount)
    {
        if (($result = $this->bitcoinClient->sendtoaddress($to,$amount,'collection',$from,true)))
            return $result;return false;
    }

    /*获取钱包信息*/
    public function getWalletInfo()
    {
        if ($result = $this->bitcoinClient->getwalletinfo()) return $result;return false;
    }

    /*设置交易费率
    即每1000kb所需的手续费*/
    public function setTXFee($fee)
    {
        if ($this->bitcoinClient->settxfee($fee)) return true;return false;
    }

    /*交易费率估计
    参数:需要网络确认的节点数
    返回费率的估算值*/
    public function getEstimateFee(int $blocks)
    {
        if ($result = $this->bitcoinClient->estimatesmartfee($blocks))return custom_number_format($result['feerate'],8);return 0;
    }

    /*返回具有帐户名称作为键，帐户余额作为值的数组*/
    public function listAccounts()
    {
        if ($result = $this->bitcoinClient->listaccounts()) return $result;return 0;
    }

    /*返回账户的余额
    如果未指定[account]，则返回服务器的总可用余额。
    如果指定了[account]，则返回指定帐户中的余额*/
//    public function getBalance($account = null)
//    {
//        return $this->bitcoinClient->getbalance($account);
//    }
    public function getBalance($address = null)
    {
        if(blank($address)){
//            $list = $this->bitcoinClient->listunspent(6,9999999);
//            return custom_number_format(array_sum(array_column($list,'amount')),8);
            $balance = $this->bitcoinClient->getbalance();
            return custom_number_format($balance,8);
        }else{
            $list = $this->bitcoinClient->listunspent(6,9999999,[$address]);
            if(!$list) return 0;
//            dd($list);
            return custom_number_format(array_sum(array_column($list,'amount')),8);
        }
    }

    public function getBTCBalance($address)
    {
        $url = "https://blockchain.info/balance?active=" . $address;
        $rsp = (new Client())->get($url);
        if (isset(\GuzzleHttp\json_decode($rsp->getBody())->error)) return 0;
        $data = \GuzzleHttp\json_decode($rsp->getBody());
        $balance = get_object_vars($data->$address)['final_balance'];
        return custom_number_format($balance / pow(10,8),8);
    }

    public function newAccount()
    {
        if ($result = $this->bitcoinClient->getnewaddress()) return $result;return null;
    }

    /*发起交易*/
    public function sendFrom($account,$address,$amount,$confirm=1,$comment='')
    {
        $this->walletPassPhrase();
//        return $this->bitcoinClient->sendfrom($account,$address,$amount,$confirm,$comment);
        if (($result = $this->bitcoinClient->sendfrom($account,$address,$amount,$confirm,$comment)))
            return $result;return 0;
    }

    /*获取一笔交易的详细信息*/
    public function getTransaction($transactionId)
    {
        if ($result = $this->bitcoinClient->gettransaction($transactionId)) return $result;return 0;
    }

    /*加密钱包*/
    public function encryptWallet($password)
    {
        if ($result = $this->bitcoinClient->encryptwallet($password)) return $result;return 0;
    }

    /*解锁钱包*/
    public function walletPassPhrase($password='')
    {
//        dd($this->bitcoinClient->walletpassphrase($this->walletPassword,10));
        if ($password){
            if ($this->bitcoinClient->walletpassphrase($password,10))return 1;return 0;
        }else{
            if ($this->bitcoinClient->walletpassphrase($this->walletPassword,10)) return 1;return 0;
        }
    }

    /*创建一个事务*/
    public function createRawTransaction($data1,$data2)
    {
        return $this->bitcoinClient->createrawtransaction($data1,$data2);
    }

    /*查询事务详情,可用于观测费用*/
    public function fundRawTransaction($transaction)
    {
        return $this->bitcoinClient->fundrawtransaction($transaction);
    }

    /*查询交易费用*/
    public function checkTransactionFees($toAddress,$amount)
    {
        if (($result = $this->fundRawTransaction($this->createRawTransaction([$this->listUnspent()[0]],[$toAddress=>$amount]))['fee']) !== false)return $result;return 0;
    }

}
