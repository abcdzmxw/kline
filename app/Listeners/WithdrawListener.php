<?php

namespace App\Listeners;

use App\Events\WithdrawEvent;
use App\Services\UdunWalletService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class WithdrawListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  WithdrawEvent  $event
     * @return void
     */
    public function handle(WithdrawEvent $event)
    {
        $withdraw = $event->withdraw;
        if(blank($withdraw)){
            info('===uduncloud提币发送参数错误1===');
            return ;
        }

        info('===uduncloud发送提币审核===',$withdraw->toArray());

        try {
            if(config('coin.udun_switch') === true){
                if($withdraw['coin_name'] == 'USDT'){
                    $map1 = [
                        1 => 0,
                        2 => 60,
                        3 => 195,
                    ];
                    $map2 = [
                        1 => '31',
                        2 => '0xdac17f958d2ee523a2206206994597c13d831ec7',
                        3 => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
                    ];
                    $mainCoinType = $map1[$withdraw['address_type']] ?? null;
                    $coinType = $map2[$withdraw['address_type']] ?? null;
                }else{
                    $map = [
                        'BTC' => 0,
                        'ETH' => 60,
                        'TRX' => 195,
                    ];
                    $mainCoinType = $coinType = $map[$withdraw['coin_name']] ?? null;
                }

                if(blank($mainCoinType) || blank($coinType)){
                    info('===uduncloud提币发送参数错误2===');
                    return ;
                }

                $businessId = $withdraw['id'] . '-' . $withdraw['datetime'];
                $callUrl = config('app.url') . '/api/udun/notify';
                //$callUrl = env('NOTIFY_URL') . '/api/udun/notify';
                $memo = 'uid:' . $withdraw['user_id'];
                $res = (new UdunWalletService())->withdraw($mainCoinType,$coinType,$withdraw['amount'],$withdraw['address'],$callUrl,$businessId,$memo);
                info('===提交优盾钱包提币审核===',$res);
            }
        }catch (\Exception $e){
            info($e);
        }
    }
}
