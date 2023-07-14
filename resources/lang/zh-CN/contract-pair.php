<?php
return [
    'labels' => [
        'ContractPair' => '合约',
    ],
    'fields' => [
        'symbol' => 'symbol',
        'contract_coin_id' => '合约币种ID',
        'contract_coin_name' => '合约币种名称',
        'type' => '结算货币',
        'unit_amount' => '单张面值（USDT）',
        'maker_fee_rate' => 'Maker手续费率',
        'taker_fee_rate' => 'Taker手续费率',
        'status' => '状态',
        'trade_status' => '交易状态',
        'lever_rage' => '杠杆倍数',
        'default_lever' => '默认杠杆',
        'min_qty' => '单笔最小下单（张）',
        'max_qty' => '单笔最大下单（张）',
        'total_max_qty' => '最大持仓量（张）',
        'buy_spread' => '买单滑点',
        'sell_spread' => '卖单滑点',
        'settle_spread' => '结算滑点',
    ],
    'options' => [
    ],
];
