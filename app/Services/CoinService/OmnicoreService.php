<?php


namespace App\Services\CoinService;


use App\Models\CenterWallet;
use App\Services\CoinService\Interfaces\CoinServiceInterface;
use App\Services\CoinService\Libs\BitcoinClient;

class OmnicoreService implements CoinServiceInterface
{
    private $bitcoinClient;
    private $walletPassword;

    public function __construct()
    {
        $this->walletPassword = config('coin.omnicore_pwd');
        $this->bitcoinClient = new BitcoinClient(config('coin.omnicore_user'), config('coin.omnicore_pwd'),config('coin.omnicore_host'),config('coin.omnicore_port'));
    }

    // 代币归集时 发送代币交易手续费
    public function sendFee($address)
    {
//        $btc_fee_account = CenterWallet::query()->where('center_wallet_account','btc_fee_account')->value('center_wallet_address'); // 手续费账户
//        if(blank($btc_fee_account)) return false;
//        $amount = '0.00000546';
//        if (($result = $this->bitcoinClient->sendtoaddress($address,$amount,'sendFee',$btc_fee_account)))
//            return $result;return false;
    }

    public function collection($address,$to,$amount)
    {
        $btc_fee_account = CenterWallet::query()->where('center_wallet_account','btc_fee_account')->value('center_wallet_address'); // 手续费账户
        return $this->bitcoinClient->omni_funded_send($address,$to,31,$amount,$btc_fee_account);
//        return $this->bitcoinClient->omni_funded_sendall($address,$to,1,$btc_fee_account);
    }

    public function getwalletbalances()
    {
        $propertyid = 31;
        $result =  $this->bitcoinClient->omni_getwalletbalances();
        if(blank($result)) return 0;
        $property = array_first($result,function($value, $key) use ($propertyid){
            return $value['propertyid'] == $propertyid;
        });
        if (!blank($property)) return $property['balance'];return 0;
    }

    /*返回账户的余额
    默认property_id为31 usdt
    */
    public function getBalance($address,$propertyId = 31)
    {
        $result = $this->bitcoinClient->omni_getbalance($address,$propertyId);
        if ($result) return $result['balance'];return 0;
    }

    public function listAccounts()
    {
        if ($result = $this->bitcoinClient->listaccounts()) return $result;return 0;
    }

    /*获取一笔交易的详细信息*/
    public function getTransaction($transactionId)
    {
        if ($result = $this->bitcoinClient->gettransaction($transactionId)) return $result;return 0;
    }

    public function getWalletInfo()
    {
        if ($result = $this->bitcoinClient->getwalletinfo()) return $result;return 0;
    }

    public function newAccount()
    {
        // TODO: Implement newAccount() method.
    }

    /*设置交易费率
    即每1000kb所需的手续费*/
    public function setTXFee($fee)
    {
        if ($this->bitcoinClient->settxfee($fee)) return 1;return 0;
    }

    /*交易费率估计
    参数:需要网络确认的节点数
    返回费率的估算值*/
    public function getEstimateFee(int $blocks)
    {
        if ($result = $this->bitcoinClient->estimatesmartfee($blocks))return custom_number_format($result['feerate'],8);return 0;
    }

    /*返回与给定地址关联的帐户*/
    public function getAccount($address)
    {
        if ($result = $this->bitcoinClient->getaccount($address)) return $result;return 0;
    }

    /*返回用于接收此帐户付款的当前比特币地址。
    如果<account>不存在，它将与将返回的相关新地址一起创建*/
    public function getAccountAddress($account='')
    {
        if ($result = $this->bitcoinClient->getaccountaddress($account)){
            return $result;
        }else{
            $this->walletPassPhrase();
            if ($result = $this->bitcoinClient->getaccountaddress($account))
                return $result;
            return 0;
        }
    }

    /*返回给定帐户的地址列表*/
    public function getAddressByAccount($account='')
    {
        if ($result = $this->bitcoinClient->getaddressesbyaccount($account)) return $result;return 0;
    }

    /*发起交易*/
    public function send($fromAddress,$toAddress,$propertyId,$amount,$feeAddress='')
    {
        $feeAddress = $feeAddress ? $feeAddress : $fromAddress;
        $result = $this->walletPassPhrase();//dd($result);
//        return $this->bitcoinClient->sendfrom($account,$address,$amount,$confirm,$comment);
        if (($result = $this->bitcoinClient->omni_send($fromAddress,$toAddress,$propertyId,$amount)))
            return $result;//return 0;
    }

    /*用于在服务器内部的账户中进行转账,无需手续费*/
    public function move($fromAccount,$toAccount,$amount)
    {
        if (($this->getBalance($fromAccount)>=$amount) && $this->bitcoinClient->move($fromAccount,$toAccount,$amount)) return 1;return 0;
    }

    /*加密钱包*/
    public function encryptWallet($password)
    {
        if ($result = $this->bitcoinClient->encryptwallet($password)) return $result;return 0;
    }

    /*解锁钱包*/
    public function walletPassPhrase($password='')
    {
//        dd($this->bitcoinClient1->walletpassphrase('888888',10));
        if ($password){
            if ($this->bitcoinClient->walletpassphrase($password,10))return 1;return 0;
        }else{
            if ($this->bitcoinClient->walletpassphrase($this->walletPassword,10)) return 1;return 0;
        }
    }

    /*列出*/
    public function listreceivedbyaccount()
    {
        if ($result = $this->bitcoinClient->listreceivedbyaccount()) return $result;return 0;
    }

    /*列出钱包中可供交易的output*/
    public function listUnspent()
    {
        if ($result = $this->bitcoinClient->listunspent()) return $result;return 0;
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

}
