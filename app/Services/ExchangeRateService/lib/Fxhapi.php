<?php


namespace App\Services\ExchangeRateService\lib;


class Fxhapi
{
    private $url = 'https://fxhapi.feixiaohao.com/public/v1/';
    public $api_method = '';
    public $req_method = '';

    private function create_url($append_param = [])
    {
        $param = [];
        if ($append_param) {
            foreach($append_param as $k=>$ap) {
                $param[$k] = $ap;
            }
        }
        return $this->url.$this->api_method.'?'.$this->bind_param($param);
    }

    // 组合参数
    private function bind_param($param)
    {
        $u = [];
        $sort_rank = [];
        foreach($param as $k=>$v) {
            $u[] = $k."=".urlencode($v);
            $sort_rank[] = ord($k);
        }
        asort($u);
        return implode('&', $u);
    }

    private function curl($url,$postdata=[])
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        if ($this->req_method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
        }
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_TIMEOUT,60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
        ]);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        return $output;
    }

    public function getTickers($currency)
    {
        $this->api_method = "ticker";
        $this->req_method = 'GET';
        $param = [
//            'start' => '',
//            'limit' => '',
            'convert' => $currency,
        ];
        $url = $this->create_url($param);
        return json_decode($this->curl($url),true);
    }

}
