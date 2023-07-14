<?php

namespace App\Services\CoinService\Libs;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Etherscan API Wrapper.
 *
 */
class Etherscan {

    /**
     * Etherscan API key token.
     *
     * @var string
     */
    private $api_url = 'https://api.etherscan.io/api';
    private $apiKey;

    /**
     * Instantiate Etherscan API object.
     *
     * @param null $apiKey
     */
    public function __construct() {
        $this->api_url = config('coin.geth_etherscan_host');
        $this->apiKey = config('coin.geth_etherscan_apikey');
    }

    private function request($params)
    {
        $params['apikey'] = $this->apiKey;
        $urlData = http_build_query($params, '', '&');
        $url = $this->api_url .'?'. $urlData;
        try{
            $client = new Client();
            $rsp = $client->get($url);
            $data = json_decode($rsp->getBody(),true);
            if(isset($data['status']) && $data['status'] == 0){
                return null;
            }else{
                return $data['result'] ?? null;
            }
//            if (isset($data['status']) && $data['status'] == 1){
//                return $data['result'];
//            }else{
//                return null;
//            }
        }catch (\Exception | GuzzleException $e){
            info($e);
            return null;
        }
    }

    // === Account APIs ========================================================

    /**
     * Get Ether Balance for a single Address.
     *
     * @param string $address Ether address.
     * @param string $tag
     *
     * @return array
     */
    public function balance(string $address, $tag = 'latest') {
        return $this->request([
            'module' => "account",
            'action' => "balance",
            'address' => $address,
            'tag' => $tag
        ]);
    }

    /**
     * Get Ether Balance for multiple Addresses in a single call.
     *
     * @param string $addresses Ether address.
     * @param string $tag
     *
     * @return array
     */
    public function balanceMulti(string $addresses, $tag = 'latest') {
        if (is_array($addresses)) {
            $addresses = implode(",", $addresses);
        }

        return $this->request([
            'module' => "account",
            'action' => "balancemulti",
            'address' => $addresses,
            'tag' => $tag
        ]);
    }

    /**
     * ETH交易
     *
     * @param string $address Ether address.
     * @param int $startBlock Starting blockNo to retrieve results
     * @param int $endBlock Ending blockNo to retrieve results
     * @param string $sort 'asc' or 'desc'
     * @param null $page Page number
     * @param null $offset Offset
     *
     * @return array
     */
    public function txList(string $address, $startBlock = 0, $endBlock = 99999999, $sort = "desc", $page = null, $offset = null) {
        $params = [
            'module' => "account",
            'action' => "txlist",
            'address' => $address,
            'startblock' => $startBlock,
            'endblock' => $endBlock,
            'sort' => $sort
        ];

        if (!is_null($page)) {
            $params['page'] = (int)$page;
        }

        if (!is_null($offset)) {
            $params['offset'] = (int)$offset;
        }

        return $this->request($params);
    }

    /**
     * 内部ETH交易
     *
     * @param string $address Ether address.
     * @param int $startBlock Starting blockNo to retrieve results
     * @param int $endBlock Ending blockNo to retrieve results
     * @param string $sort 'asc' or 'desc'
     * @param null $page Page number
     * @param null $offset Offset
     *
     * @return array
     */
    public function txListInternal(string $address, $startBlock = 0, $endBlock = 99999999, $sort = "desc", $page = null, $offset = null) {
        $params = [
            'module' => "account",
            'action' => "txlistinternal",
            'address' => $address,
            'startblock' => $startBlock,
            'endblock' => $endBlock,
            'sort' => $sort
        ];

        if (!is_null($page)) {
            $params['page'] = (int)$page;
        }

        if (!is_null($offset)) {
            $params['offset'] = (int)$offset;
        }

        return $this->request($params);
    }

    /**
     * ERC20 - Token代币交易记录
     *
     * @param string $address
     * @param int $startBlock
     * @param int $endBlock
     * @param string $sort
     * @param null $page
     * @param null $offset
     * @return false|mixed
     */
    public function tokentxList(string $address, $startBlock = 0, $endBlock = 99999999, $sort = "desc", $page = null, $offset = null) {
        $params = [
            'module' => "account",
            'action' => "tokentx",
            'address' => $address,
            'startblock' => $startBlock,
            'endblock' => $endBlock,
            'sort' => $sort
        ];

        if (!is_null($page)) {
            $params['page'] = (int)$page;
        }

        if (!is_null($offset)) {
            $params['offset'] = (int)$offset;
        }

        return $this->request($params);
    }

    /**
     * Get Token Account Balance by known TokenName (Supported TokenNames: DGD,
     * MKR, FirstBlood, HackerGold, ICONOMI, Pluton, REP, SNGLS).
     *
     * or
     *
     * for TokenContractAddress.
     *
     * @param string $tokenIdentifier Token name from the list or contract address.
     * @param string $address Ether address.
     * @param string $tag
     *
     * @return array
     */
    public function tokenBalance(string $tokenIdentifier, string $address, $tag = 'latest') {
        $params = [
            'module' => "account",
            'action' => "tokenbalance",
            'address' => $address,
            'tag' => $tag
        ];

        if (strlen($tokenIdentifier) === 42) {
            $params['contractaddress'] = $tokenIdentifier;
        } else {
            $params['tokenname'] = $tokenIdentifier;
        }

        return $this->request($params);
    }

    /**
     * Get Ether LastPrice Price.
     *
     * @return float
     */
    public function ethPrice() {
        return $this->request([
            'module' => "stats",
            'action' => "ethprice",
        ]);
    }

    public function getTransactionCount($address,$tag = 'latest')
    {
        $params = [
            'module' => "proxy",
            'action' => "eth_getTransactionCount",
            'address' => $address,
            'tag' => 'latest',
        ];

        return $this->request($params);
    }

    /**
     * Creates new message call transaction
     * @param $tx_hex
     * @return mixed|null
     */
    public function sendRawTransaction($tx_hex)
    {
        $params = [
            'module' => "proxy",
            'action' => "eth_sendRawTransaction",
            'hex' => $tx_hex,
        ];

        return $this->request($params);
    }

    // === Utility methods =====================================================

    /**
     * Converts Wei value to the Ether float value.
     *
     * @param int $amount
     *
     * @return float
     */
    public static function convertEtherAmount(int $amount) {
        return (float)$amount / pow(10, 18);
    }

    /**
     * Checks if transaction is input transaction.
     *
     * @param string $address Ether address.
     * @param array $transactionData Transaction data.
     *
     * @return bool
     */
    public static function isInputTransaction(string $address, array $transactionData): bool
    {
        return (strtolower($transactionData['to']) === strtolower($address));
    }

    /**
     * Checks if transaction is output transaction.
     *
     * @param string $address Ether address.
     * @param array $transactionData Transaction data.
     *
     * @return bool
     */
    public static function isOutputTransaction(string $address, array $transactionData): bool
    {
        return (strtolower($transactionData['from']) === strtolower($address));
    }

}
