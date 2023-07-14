<?php

return [
    // HTTP 请求的超时时间（秒）
    'timeout' => 5.0,

    // 默认发送配置
    'default' => [
        // 网关调用策略，默认：顺序调用
        'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

        // 默认可用的发送网关
        'gateways' => [
            // 'aliyun',
//            'yunpian',
//            'chuanglan',
//            'moduyun',
//             'unnameable',
            'smsbao',
        ],
    ],
    // 可用的网关配置
    'gateways' => [
        'errorlog' => [
            'file' => '/tmp/easy-sms.log',
        ],
//        'yunpian' => [
//            'api_key' => env('YUNPIAN_API_KEY'),
//        ],

        'smsbao' => [
            'user'  => env('DXB_ACCOUNT'),    //账号
            'password'   => env('DXB_PASSWORD')   //密码
        ],

        'chuanglan' => [
            'account' => env('MSG_ACCOUNT'),
            'password' => env('MSG_PASSWORD'),

            'intel_account' => env('INTEL_MSG_ACCOUNT',''),
            'intel_password' => env('INTEL_MSG_PASSWORD',''),

//             \Overtrue\EasySms\Gateways\ChuanglanGateway::CHANNEL_VALIDATE_CODE  => 验证码通道（默认）,
//             \Overtrue\EasySms\Gateways\ChuanglanGateway::CHANNEL_PROMOTION_CODE => 会员营销通道
            'channel'  => \Overtrue\EasySms\Gateways\ChuanglanGateway::CHANNEL_VALIDATE_CODE,

            // 会员营销通道 特定参数。创蓝规定：api提交营销短信的时候，需要自己加短信的签名及退订信息
            'sign' => env('MSG_SIGN'),
            'unsubscribe' => '回TD退订',
        ],

        'moduyun' => [
            'accesskey' => env('MSG_UID'),  //必填 ACCESS KEY
            'secretkey' => env('MSG_PW'),  //必填 SECRET KEY
            'signId'    => '6135e93831a03f41e25edc5d',  //选填 短信签名，如果使用默认签名，该字段可缺省
            'type'      => 0,   //选填 0:普通短信;1:营销短信
        ],

        // 'aliyun' => [
        //     'access_key_id' => env('MSG_ACCOUNT'),
        //     'access_key_secret' => env('MSG_PASSWORD'),
        //     'sign_name' => env('MSG_SIGN'),
        // ],


//         'unnameable' => [
//             'uid' => env('MSG_UID'),
//             'pw' => env('MSG_PW'),
//             'sign' => env('MSG_SIGN'),
// //            'unsubscribe' => '回TD退订',
//         ],
    ],
];
