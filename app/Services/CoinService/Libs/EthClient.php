<?php

namespace App\Services\CoinService\Libs;

use Elliptic\EC;
use App\Libs\Ethtool\KeyStore;
use kornrunner\Keccak;
use App\Libs\Ethtool\Credential;
use Web3\Web3;
use App\Libs\Ethtool\Callback;
use Web3\Utils;
use Web3\Contract;

class EthClient
{
    // const TOKEN = '147158169';
    // const HOST = 'http://134.175.119.64:8312'; //公司
    //  const HOST = 'http://47.93.53.153:8555';
    protected $host;
    protected $token;

    public function __construct($token = null)
    {
        $this->token = $token;
        $this->host = config('coin.geth_host');
    }

    public function getInfo($private)
    {
        $ec = new EC('secp256k1');
        $keyPair = $ec->keyFromPrivate($private);
        $privateKey = $keyPair->getPrivate()->toString(16, 2);
        $publicKey = $keyPair->getPublic()->encode('hex');
        $address = '0x' . substr(\kornrunner\Keccak::hash(substr(hex2bin($publicKey), 1), 256), 24);
        KeyStore::save($private, $this->token, app_path() . '/Libs/Ethtool/keystore');
        return ['private' => $privateKey, 'public' => $publicKey, 'address' => $address];
    }

    public function newAccount()
    {
        $wfn = Credential::newWallet($this->token, app_path() . '/Libs/Ethtool/keystore');
        $credential = Credential::fromWallet($this->token, $wfn);
        $data = [];
        $data['private'] = $credential->getPrivateKey();
        $data['public'] = $credential->getPublicKey();
        $data['address'] = $credential->getAddress();
        return $data;
    }

    public function getBalance($account, $block = 'latest')
    {
        $web3 = new Web3($this->host);
        $cb = new Callback;
        $web3->eth->getBalance($account, $block, $cb);
        return [
            'account' => $account,
            'balance' => $cb->result->toString() / pow(10, 16),
        ];
    }

    public function getTokenBalance($account, $tokenAddr)
    {
        $web3 = new Web3($this->host);
        $cb = new Callback;
        $opts = [];
        $contract = self::loadContract($web3, 'EzToken', $tokenAddr);
        $contract->call('balanceOf', $account, $opts, $cb);
        return [
            'account' => $account,
            'token' => $tokenAddr,
            'balance' => $cb->result['balance']->toString() / pow(10, 4)
        ];
    }

    public static function loadContract($web3, $artifact, $tokenAddr)
    {
        $dir = app_path() . '/Libs/Ethtool/contract/build/';
        $abi = file_get_contents($dir . $artifact . '.abi');
        $addr = file_get_contents($dir . $artifact . '.addr');
        $contract = new Contract($web3->provider, $abi);
        $contract->at($tokenAddr);
        return $contract;
    }

    /**
     * 发送以太坊
     * int chainId 当前连接网络的ID
     * string data input
     * @param $value 发送的以太币数量
     * @param $to     接收方
     */
    public function sendRawTransaction($account, $private, $to, $value, $gasPrice = '20', $gasLimit = '0x76c0', $chainId = 1)
    {
        $web3 = new Web3($this->host);
        $cb = new Callback;
        try {
            // account info
//            $wallet = app_path() . '/Libs/Ethtool/keystore/' . substr($account, 2) . '.json';
//            $credential = Credential::fromWallet($this->token, $wallet);
            $credential = Credential::fromKey($private);

            $walletAddress = $credential->getAddress();

            if ($private != $credential->getPrivateKey()) {
                return ['status' => '0', 'info' => 'error PrivateKey'];
            }

            $web3->eth->getBalance($walletAddress, $cb);
            $balance = $cb->result;
            // nonce
            $web3->eth->getTransactionCount($walletAddress, 'latest', $cb);
            $nonce = $cb->result;
            // data
            $raw = [
                'nonce' => Utils::toHex($nonce, true),
                'gasPrice' => '0x' . Utils::toWei('20', 'gwei')->toHex(),
                'gasLimit' => $gasLimit, // =30400
                'to' => $to,
                'value' => '0x' . Utils::toWei($value, 'ether')->toHex(),
                'data' => '0x' . bin2hex('hello'),
                'chainId' => $chainId ?: 1
            ];
            $signed = $credential->signTransaction($raw); // 进行离线签名
            $web3->eth->sendRawTransaction($signed, $cb);  // 发送裸交易
        } catch (\Exception $e) {
            return ['status' => '0', 'info' => $e->getMessage()];
        }
        return [
            'status' => 1,
            'account' => $walletAddress,
            'to' => $to,
            'value' => $value
        ];
    }

    //etc的裸交易sign
    public function getETCRawTran($from,$private, $to, $value,$nonce, $gasPrice = '9', $gasLimit = '30400', $chainId = 61)
    {

        try {
            $credential = Credential::fromKey($private);

            $raw = [
                'nonce' => Utils::toHex($nonce, true),
//                'from' => $account,
                'gasPrice' => '0x' . Utils::toWei($gasPrice, 'gwei')->toHex(),
                'gasLimit' => '0x'.dechex($gasLimit), // =30400
                'to' => $to,
                'value' => '0x' . Utils::toWei($value, 'ether')->toHex(),
                'data' => '0x' . bin2hex('hello'),
                'chainId' => $chainId
            ];
            $signed = $credential->signTransaction($raw); // 进行离线签名
            return $signed;
//            $web3->eth->sendRawTransaction($signed, $cb);  // 发送裸交易
        } catch (\Exception $e) {
            return false;
        }

    }

    /**
     * 发送代币
     */
    public function sendRawToken($account, $private, $tokenAddr, $to, $value, $gasPrice = '20', $gasLimit = '0x76c0', $chainId = 1)
    {
        $web3 = new Web3($this->host);
        $cb = new Callback;
        try {
            // account info
//            $wallet = app_path() . '/Libs/Ethtool/keystore/' . substr($account, 2) . '.json';
//            $credential = Credential::fromWallet($this->token, $wallet);
            $credential = Credential::fromKey($private);
            $walletAddress = $credential->getAddress();
            if ($private != $credential->getPrivateKey()) {
                return ['status' => '0', 'info' => 'error PrivateKey'];
            }
            $web3->eth->getBalance($walletAddress, $cb);
            $balance = $cb->result;
            // nonce
            $web3->eth->getTransactionCount($walletAddress, 'latest', $cb);
            $nonce = $cb->result;
            // data
            $bet = 10000; // 代币发布时小数点位数
            $value = base_convert($value * $bet, 10, 16);

            $raw = [
                'nonce' => Utils::toHex($nonce, true),
                'gasPrice' => '0x' . Utils::toWei('20', 'gwei')->toHex(),
                'gasLimit' => '0xea60', //16进制
                'to' => $tokenAddr, //代币地址
                'value' => '0x0',
                //8位方法名 64位对方地址 64位金额
                'data' => '0x' . 'a9059cbb' . str_pad(substr($to, 2), 64, "0", STR_PAD_LEFT) . str_pad($value, 64, "0", STR_PAD_LEFT),
                'chainId' => $chainId ?: 1
            ];
            $signed = $credential->signTransaction($raw); // 进行离线签名
            $web3->eth->sendRawTransaction($signed, $cb);  // 发送裸交易
        } catch (\Exception $e) {
            return ['status' => '0', 'info' => $e->getMessage()];
        }
        return [
            'status' => 1,
            'account' => $walletAddress,
            'to' => $to,
            'value' => $value,
            'hash' => $signed,
        ];
    }
}
