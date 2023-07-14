<?php


namespace App\Services;


class UdunWalletService
{
    private $merchantId;
    private $gateway_address;
    private $api_key;

    public function __construct()
    {
        $this->merchantId = config('coin.merchant_number');
        $this->gateway_address = config('coin.gateway_address');
        $this->api_key = config('coin.api_key');
    }

    // 获取商户支持的币种信息
    public function supportCoins($showBalance = true)
    {
        $body = array(
            'merchantId' => $this->merchantId,
            'showBalance' => $showBalance
        );

        $body = json_encode($body);
        $timestamp = time();
        $nonce = rand(100000, 999999);

        $url = $this->gateway_address . '/mch/support-coins';
        $key = $this->api_key;

        $sign = md5($body.$key.$nonce.$timestamp);

        $data = array(
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'sign' => $sign,
            'body' => $body
        );

        $data_string = json_encode($data);

        return json_decode($this->http_post($url, $data_string),true);
    }

    // 生成地址
    public function createAddress($coinType)
    {
        // 回调
        //$callUrl = config('app.url') . '/api/udun/notify';
          $callUrl = env('NOTIFY_URL') . '/api/udun/notify';
         // $callUrl ='https://server.gtcoin.one/api/udun/notify';
        $body = array(
            'merchantId' => $this->merchantId,
            'coinType' => $coinType,
            'callUrl' => $callUrl,
            // 'walletId' => '',
        );

        $body = '['.json_encode($body).']';
        $timestamp = time();
        $nonce = rand(100000, 999999);

        $url = $this->gateway_address . '/mch/address/create';
        $key = $this->api_key;

        $sign = md5($body.$key.$nonce.$timestamp);

        $data = array(
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'sign' => $sign,
            'body' => $body
        );

        $data_string = json_encode($data);
        $res = $this->http_post($url, $data_string);
        file_put_contents('000001.txt',$res."\r\n",FILE_APPEND);
        return json_decode($res,true);
    }

    // 校验地址合法性
    public function checkAddress($mainCoinType, $address)
    {
        $body = array(
            'merchantId' => $this->merchantId,
            'mainCoinType' => $mainCoinType,
            'address' => $address,
        );

        $body = '['.json_encode($body).']';
        $timestamp = time();
        $nonce = rand(100000, 999999);

        $url = $this->gateway_address.'/mch/check/address';
        $key = $this->api_key;

        $sign = md5($body.$key.$nonce.$timestamp);

        $data = array(
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'sign' => $sign,
            'body' => $body
        );

        $data_string = json_encode($data);

        return json_decode($this->http_post($url, $data_string),true);
    }

    // 发送提币申请
    public function withdraw($mainCoinType, $coinType, $amount, $address, $callUrl, $businessId, $memo)
    {
        $body = array(
            'merchantId' => $this->merchantId,
            'mainCoinType' => $mainCoinType,
            'address' => $address,
            'amount' => $amount,
            'coinType' => $coinType,
            'callUrl' => $callUrl,
            'businessId' => $businessId,
            'memo' => $memo
        );

        $body = '['.json_encode($body).']';
        $timestamp = time();
        $nonce = rand(100000, 999999);

        $url = $this->gateway_address.'/mch/withdraw';
        $key = $this->api_key;

        $sign = md5($body.$key.$nonce.$timestamp);

        $data = array(
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'sign' => $sign,
            'body' => $body
        );

        $data_string = json_encode($data);

        return json_decode($this->http_post($url, $data_string),true);
    }

    // 代付
    public function proxypay($mainCoinType, $coinType, $amount, $address, $callUrl, $businessId, $memo)
    {
        $body = array(
            'merchantId' => $this->merchantId,
            'mainCoinType' => $mainCoinType,
            'address' => $address,
            'amount' => $amount,
            'coinType' => $coinType,
            'callUrl' => $callUrl,
            'businessId' => $businessId,
            'memo' => $memo
        );

        $body = '['.json_encode($body).']';
        $timestamp = time();
        $nonce = rand(100000, 999999);

        $url = $this->gateway_address.'/mch/withdraw/proxypay';
        $key = $this->api_key;

        $sign = md5($body.$key.$nonce.$timestamp);

        $data = array(
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'sign' => $sign,
            'body' => $body
        );

        $data_string = json_encode($data);

        return json_decode($this->http_post($url, $data_string),true);
    }

    // 校验地址是否存在
    public function checkExistAddress($mainCoinType, $address)
    {
        $body = array(
            'merchantId' => $this->merchantId,
            'mainCoinType' => $mainCoinType,
            'address' => $address,
        );

        $body = '['.json_encode($body).']';
        $timestamp = time();
        $nonce = rand(100000, 999999);

        $url = $this->gateway_address.'/mch/exist/address';
        $key = $this->api_key;

        $sign = md5($body.$key.$nonce.$timestamp);

        $data = array(
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'sign' => $sign,
            'body' => $body
        );

        $data_string = json_encode($data);

        return json_decode($this->http_post($url, $data_string),true);
    }

    private function http_post($url, $data_string)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'X-AjaxPro-Method:ShowList',
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($data_string))
        );

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        $data = curl_exec($ch);
        curl_close($ch);

        //var_dump(curl_error($ch));die;

        return $data;
    }

}
