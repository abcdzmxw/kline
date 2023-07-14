<?php

namespace App\SMSGateways;

use Overtrue\EasySms\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Gateways\Gateway;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Traits\HasHttpRequest;

class UnnameableGateway extends Gateway
{
    use HasHttpRequest;

    // 网建
    const ENDPOINT_URL = 'http://utf8.api.smschinese.cn/';

    protected $uid;

    protected $pw;

    protected $sign = null;

    /**
     * Send a short message.
     *
     * @param \Overtrue\EasySms\Contracts\PhoneNumberInterface $to
     * @param \Overtrue\EasySms\Contracts\MessageInterface $message
     * @param \Overtrue\EasySms\Support\Config $config
     *
     * @return array
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $params = [
            'Uid' => $config->get('uid'),
            'Key' => $config->get('pw'),
            'smsMob' => $to->getNumber(),
            'smsText' => $message->getContent(),
        ];
        $dataStr     = '';
        foreach ($params as $key => $val) {
            $dataStr .= $key . '=' . $val . '&';
        }
        return $this->get(self::ENDPOINT_URL, $dataStr);
    }
}
