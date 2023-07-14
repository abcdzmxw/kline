<?php
return [
    'labels' => [
        'InsideTradeBuy' => '买入委托',
    ],
    'fields' => [
        'order_no' => '订单号',
        'user_id' => '用户',
        'symbol' => '币对',
        'type' => '委托方式',
        'entrust_price' => '委托价格',
        'trigger_price' => '触发价',
        'quote_coin_id' => '报价币种',
        'base_coin_id' => '交易币种',
        'amount' => '委托数量',
        'traded_amount' => '已成交数量',
        'money' => '预期交易额',
        'traded_money' => '已成交额',
        'status' => '交易进度',
        'cancel_time' => '撤单时间',
        'hang_status' => '挂单状态 0未挂单 1已挂单',
    ],
    'options' => [
    ],
];
