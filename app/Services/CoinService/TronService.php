<?php


namespace App\Services\CoinService;

use App\Exceptions\ApiException;
use App\Services\CoinService\Interfaces\CoinServiceInterface;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use IEXBase\TronAPI\Exception\TronException;
use IEXBase\TronAPI\Support\Keccak;
use IEXBase\TronAPI\Tron;

class TronService implements CoinServiceInterface
{
    private $client;
    private $trongrid = 'https://api.trongrid.io';
    private $apiKey = '96062f83-74c7-4c3b-9361-2bae0d95eeb3';

    public function __construct()
    {
        $fullNode = new \IEXBase\TronAPI\Provider\HttpProvider(config('coin.tron_host'));
        $solidityNode = null;
        $eventServer = null;
        try {
            $this->client = new \IEXBase\TronAPI\Tron($fullNode, $solidityNode, $eventServer);
        } catch (\IEXBase\TronAPI\Exception\TronException $e) {
            throw new ApiException($e->getMessage());
        }
    }

    public function getTransactionInfoByBlockNum($blockNum)
    {
        return $this->client->getManager()->request("wallet/gettransactioninfobyblocknum", ['num'=>$blockNum], "post");
    }

    public function test()
    {
//        return $this->client->fromHex('41fe87f08b96f0953eb4cf54d4239b364c0b8549bd');
//        return $this->client->getManager()->request("wallet/getblockbylatestnum", ['num'=>2], "post");
        return $this->client->getCurrentBlock();
//        return $this->newAccount();
//        return $this->client->getEventResult('TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t', 0,'Transfer',29459734);
    }

    public function getCurrentBlock()
    {
        try {
//            return $this->client->getCurrentBlock();
            return $this->client->getCurrentBlock()['block_header']['raw_data']['number'] ?? 29752107;
        } catch (TronException $e) {
            info($e);
            return 0;
        }
    }

    public function getBlockByNumber($blockNum)
    {
        try {
            return $this->client->getBlockByNumber($blockNum);
        } catch (TronException $e) {
//            info($e);
            return false;
        }
    }

    public function validateAddress($address): bool
    {
        try {
            $rsp = $this->client->validateAddress($address);
            return $rsp['result'];
        } catch (TronException $e) {
            info($e);
            return false;
        }
    }

    public function getFee($address = '')
    {
        return 6;
    }

    public function sendFee($address)
    {
        $center_fee_wallet = \App\Models\CenterWallet::query()->where('center_wallet_account','trx_fee_account')->first();
        $amount = $this->getFee();
        return $this->sendTransaction($center_fee_wallet['center_wallet_address'],$center_fee_wallet['private_key'],$address,$amount);
    }

    /**
     * 获取TRX余额
     * @param $address
     * @return bool|float
     */
    public function getBalance($address)
    {
        try {
            return $this->client->getBalance($address, true);
        } catch (TronException $e) {
            return false;
        }
    }

    /**
     * 获取TRC20余额
     * @param string $address
     * @param string $contractAddress // 默认 USDT 智能合约地址：TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t
     * @param bool $fromTron
     * @return array|bool|int
     */
//    public function getTokenBalance(string $address,string $contractAddress = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t')
//    {
//        if($contractAddress == 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'){
//            $decimals = 6;
//        }else{
//            $decimals = 18;
//        }
//
//        try {
//            $url = $this->trongrid . '/v1/accounts/'. $address .'?only_confirmed=true';
//            $rsp = (new Client())->get($url);
//            $data = $data = json_decode($rsp->getBody(),true);
////            dd($data);
//            if ( isset($data['success']) && $data['success'] == true && !empty($data['data']) ){
//                $account = $data['data'][0];
//                if(isset($account['trc20']) and !empty($account['trc20']) ){
//                    $value = array_filter($account['trc20'], function($v) use ($contractAddress) {
//                        return key($v) == $contractAddress;
//                    });
//
//                    if(empty($value)) {
//                        throw new TronException('Token id not found');
//                    }
//
//                    $first = array_shift($value);
//                    return (float) bcdiv((string)$first[$contractAddress], (string)bcpow(10,$decimals), 8);
//                }
//            }
//
//            return 0;
//        } catch (\Exception | GuzzleException $e) {
//            info($e);
//            return 0;
//        }
//    }

    public function getTokenBalance(string $address,string $contractAddress = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t')
    {
        // https://apilist.tronscan.org/api/account?address=TDV8HSdvqc5LP71BvMcWgR4nnxwYuf77FY

        if($contractAddress == 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'){
            $decimals = 6;
        }else{
            $decimals = 18;
        }

        $balance = 0;

        try {
            $url = 'https://apilist.tronscan.org/api/account?address=' . $address;
            $rsp = (new Client())->get($url);
            $data = json_decode($rsp->getBody(),true);
//            dd($data);
            if ( !empty($data['trc20token_balances']) ){
                $value = array_first($data['trc20token_balances'], function($v) use ($contractAddress) {
                    return $v['tokenId'] == $contractAddress;
                });
//                dd($value);

                $balance = (float) bcdiv((string)$value['balance'], (string)bcpow(10,$decimals), 8);
            }

            return $balance;
        } catch (\Exception | GuzzleException $e) {
            info($e);
            return $balance;
        }
    }

    /**
     * 获取TRC10余额
     * @param $tokenId
     * @param string $address
     * @param bool $fromTron
     * @return array|bool|int
     */
    public function getToken10Balance(int $tokenId, string $address, bool $fromTron = false)
    {
        try {
            return $this->client->getTokenBalance($tokenId,$address, true);
        } catch (TronException $e) {
            return false;
        }
    }

    public function listAccounts()
    {
        // TODO: Implement listAccounts() method.
    }

    public function getTransaction($transactionId)
    {
        try {
            return $this->client->getTransaction($transactionId);
        } catch (TronException $e) {
            return false;
        }
    }

    /**
     * Trx转账
     * @param string $from
     * @param string $private_key
     * @param string $to
     * @param float $amount
     * @param string|null $message
     * @return array|false
     */
    public function sendTransaction(string $from,string $private_key, string $to, float $amount, string $message= null)
    {
        try {
            $this->client->setAddress($from);
            $this->client->setPrivateKey($private_key);
            $result = $this->client->sendTransaction($to,$amount,$message,$from);

            if ($result['result'] !== true) {
                throw new \Exception(json_encode($result));
            }

            // 返回交易hash
            return $result['txid'];
        } catch (TronException $e) {
            info($e);
            return false;
        }
    }

    /**
     * TRC10 Token转账
     * @param string $from
     * @param string $private_key
     * @param string $to
     * @param float $amount
     * @param int $tokenID
     * @return array|false
     */
    public function sendTokenTransaction(string $from,string $private_key,string $to, float $amount, int $tokenID)
    {
        try {
            $this->client->setPrivateKey($private_key);
            return $this->client->sendTokenTransaction($to,$amount,$tokenID,$from);
        } catch (TronException $e) {
            return false;
        }
    }

    /**
     * 发送TRC20交易
     * @param string $from
     * @param string $private_key
     * @param string $to
     * @param float $amount
     * @param string $contractAddress
     * @return array|false
     */
    public function sendTrc20Transaction(string $from,string $private_key,string $to, float $amount, string $contractAddress = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t')
    {
        try {
            $this->client->setPrivateKey($private_key);
            $tx = $this->createTrc20Tx($from, $private_key, $to,  $amount,  $contractAddress);
//            dd($tx);
            return $this->broadcastTrc20Tx($tx);
        } catch (TronException $e) {
            info($e);
            return false;
        }
    }

    /**
     * 构建TRC20交易
     * @param string $from
     * @param string $private_key
     * @param string $to
     * @param float $amount
     * @param string $contractAddress
     * @return array
     */
    public function createTrc20Tx(string $from,string $private_key,string $to, float $amount, string $contractAddress): array
    {
        $fee_decimals = 6; // 手续费 decimals
        if($contractAddress == 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'){
            $decimals = 6;
        }else{
            // Token decimals
            $decimals = 18;
        }

        $feeLimit = 100; // 手续费上限
        $feeLimitInSun = (int) bcmul($feeLimit, bcpow(10, $fee_decimals, 0),0);
        if (!is_numeric($feeLimit) OR $feeLimit <= 0) {
            throw new \Exception('fee_limit is required.');
        } else if($feeLimit > 1000) {
            throw new \Exception('fee_limit must not be greater than 1000 TRX.');
        }

        $tokenAmount = bcmul($amount, bcpow(10, $decimals, 0), 0);
        $function = "transfer";

        //get owner address from private key
        $privKeyFactory = new PrivateKeyFactory();
        $privateKey = $privKeyFactory->fromHexUncompressed($private_key);
        $publicKey  = $privateKey->getPublicKey();
        $publicKeyHex = substr($publicKey->getHex(), 2);

        $ownerAddressHex = Keccak::hash(hex2bin($publicKeyHex), 256);
        $ownerAddressHex = "41" . substr($ownerAddressHex, -40);

        $abi = '[{"constant":true,"inputs":[],"name":"name","outputs":[{"name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"spender","type":"address"},{"name":"value","type":"uint256"}],"name":"approve","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[],"name":"totalSupply","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"sender","type":"address"},{"name":"recipient","type":"address"},{"name":"amount","type":"uint256"}],"name":"transferFrom","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[],"name":"decimals","outputs":[{"name":"","type":"uint8"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"spender","type":"address"},{"name":"addedValue","type":"uint256"}],"name":"increaseAllowance","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[{"name":"account","type":"address"}],"name":"balanceOf","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":true,"inputs":[],"name":"symbol","outputs":[{"name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"},{"constant":false,"inputs":[{"name":"spender","type":"address"},{"name":"subtractedValue","type":"uint256"}],"name":"decreaseAllowance","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":false,"inputs":[{"name":"recipient","type":"address"},{"name":"amount","type":"uint256"}],"name":"transfer","outputs":[{"name":"","type":"bool"}],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[{"name":"owner","type":"address"},{"name":"spender","type":"address"}],"name":"allowance","outputs":[{"name":"","type":"uint256"}],"payable":false,"stateMutability":"view","type":"function"},{"inputs":[],"payable":false,"stateMutability":"nonpayable","type":"constructor"},{"anonymous":false,"inputs":[{"indexed":true,"name":"from","type":"address"},{"indexed":true,"name":"to","type":"address"},{"indexed":false,"name":"value","type":"uint256"}],"name":"Transfer","type":"event"},{"anonymous":false,"inputs":[{"indexed":true,"name":"owner","type":"address"},{"indexed":true,"name":"spender","type":"address"},{"indexed":false,"name":"value","type":"uint256"}],"name":"Approval","type":"event"}]';
        $abiAry = json_decode($abi, true);

        $tx = $this->client->getTransactionBuilder()->triggerSmartContract(
            $abiAry,
            base58check2HexString($contractAddress),
            $function,
            [base58check2HexString($to),$tokenAmount],
            $feeLimitInSun,
            $ownerAddressHex,
            0,
            0
        );

        $this->client->setPrivateKey($private_key);

        $mutatedTx = $this->client->signTransaction($tx);
        return $mutatedTx;
    }

    /**
     * 广播TRC20交易
     * @throws TronException
     */
    public function broadcastTrc20Tx($tx)
    {
        try{
//            $result = $this->client->getManager()->request("wallet/broadcasttransaction", $tx, "post");
            $result = $this->client->sendRawTransaction($tx);
//            dd($result);
            if ($result['result'] !== true) {
                throw new \Exception(json_encode($result));
            }

            // 返回交易hash
            return $result['txid'];
        }catch (\Exception | TronException $e){
            info($e);
            return false;
        }
    }

    /**
     * 创建地址
     * @param null $account
     * @param string $generate
     * @return false|\IEXBase\TronAPI\TronAddress
     */
    public function newAccount($account = '',$generate = 'local')
    {
        try {
            return $this->client->createAccount();
        } catch (TronException $e) {
            return false;
        }
    }

    /**
     * 激活地址 一个已经激活的账号创建一个新账号需要花费 0.1 TRX 或等值 Bandwidth
     * @param string $address
     * @param string $private_key
     * @param string $newAccountAddress
     * @return array|false
     */
    public function registerAccount(string $address,string $private_key, string $newAccountAddress)
    {
        try {
            $this->client->setPrivateKey($private_key);
            return $this->client->registerAccount($address, $newAccountAddress);
        } catch (TronException $e) {
            return false;
        }
    }

    public function getTransactionsToAddress(string $address)
    {
        $list = [];

        try {
            $url = $this->trongrid . '/v1/accounts/'. $address . '/transactions?only_confirmed=true&only_to=true&limit=100';
            $options = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'TRON-PRO-API-KEY'     => $this->apiKey,
                ]
            ];
            $rsp = (new Client())->get($url,$options);
            $data = $data = json_decode($rsp->getBody(),true);
//            dd($data);
            if ( isset($data['success']) && $data['success'] == true && !empty($data['data']) ){
                $list = $data['data'];
            }

            return $list;
        } catch (\Exception $e) {
            info($e);
            return $list;
        }
    }

    /**
     * 获取TRC20交易记录
     * @param string $address
     * @param string $contractAddress // 默认 USDT 智能合约地址：TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t
     * @return array
     * @throws GuzzleException
     */
    public function getTokenTransactions(string $address,string $contractAddress = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'): array
    {
        $list = [];

        try {
            $url = $this->trongrid . '/v1/accounts/'. $address . '/transactions/trc20?only_confirmed=true&only_to=true&limit=100&contract_address=' . $contractAddress;
            $options = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'TRON-PRO-API-KEY'     => $this->apiKey,
                ]
            ];
            $rsp = (new Client())->get($url,$options);
            $data = $data = json_decode($rsp->getBody(),true);
//            dd($data);
            if ( isset($data['success']) && $data['success'] == true && !empty($data['data']) ){
                $list = $data['data'];
            }

            return $list;
        } catch (\Exception $e) {
            //info($e);
            return $list;
        }
    }

    public function collectionUSDT($from,$to,$amount)
    {
        return false;
    }

}
