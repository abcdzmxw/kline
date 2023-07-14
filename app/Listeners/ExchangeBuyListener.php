<?php

namespace App\Listeners;

use App\Events\ExchangeBuyEvent;
use Carbon\Carbon;
use GatewayClient\Gateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class ExchangeBuyListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
//        Gateway::$registerAddress = '127.0.0.1:1236';
    }

    /**
     * Handle the event.
     *
     * @param  ExchangeBuyEvent  $event
     * @return void
     */
    public function handle(ExchangeBuyEvent $event)
    {
        info('ExchangeBuyListener');
        $entrust = $event->entrust;
        if(blank($entrust)) return ;

        $symbol = strtolower(str_before($entrust['symbol'],'/') . str_after($entrust['symbol'],'/'));
        $key = 'exchange_buyList_' . $symbol;
        $cache_data = [
            'amount'=> $entrust['amount'],
            'id'=>$entrust['id'],
            'price'=> $entrust['entrust_price'],
            'user_id'=> $entrust['user_id'],
            'ts'=> Carbon::now()->getPreciseTimestamp(3),
        ];
        Cache::store('redis')->put($key,$cache_data);

        // websocket推送
//        $symbol = strtolower(str_before($entrust['symbol'],'/') . str_after($entrust['symbol'],'/'));
//        $group_id2 = 'buyList_' . $symbol; //最近成交明细
//        info($group_id2 . 'Gateway');
//        $cache_data = [
//            'amount'=> $entrust['amount'],
//            'id'=>$entrust['id'],
//            'price'=> $entrust['entrust_price'],
//            'user_id'=> $entrust['user_id'],
//            'ts'=> Carbon::now()->getPreciseTimestamp(3),
//        ];
//        Gateway::sendToGroup($group_id2, json_encode(['code'=>0,'msg'=>'success','data'=>$cache_data,'sub'=>$group_id2,'type'=>'dynamic']));
    }
}
