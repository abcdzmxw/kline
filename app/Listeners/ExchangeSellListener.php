<?php

namespace App\Listeners;

use App\Events\ExchangeSellEvent;
use Carbon\Carbon;
use GatewayClient\Gateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class ExchangeSellListener
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
     * @param  ExchangeSellEvent  $event
     * @return void
     */
    public function handle(ExchangeSellEvent $event)
    {
        info('ExchangeSellListener');
        $entrust = $event->entrust;
        if(blank($entrust)) return ;

        $symbol = strtolower(str_before($entrust['symbol'],'/') . str_after($entrust['symbol'],'/'));
        $key = 'exchange_sellList_' . $symbol;
        $cache_data = [
            'amount'=> $entrust['amount'],
            'id'=>$entrust['id'],
            'price'=> $entrust['entrust_price'],
            'user_id'=> $entrust['user_id'],
            'ts'=> Carbon::now()->getPreciseTimestamp(3),
        ];
        Cache::store('redis')->put($key,$cache_data);
    }
}
