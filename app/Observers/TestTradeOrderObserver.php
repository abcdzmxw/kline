<?php

namespace App\Observers;

use App\Handlers\Kline;
use App\Models\TestTradeOrder;
use Carbon\Carbon;
use GatewayClient\Gateway;
use Illuminate\Support\Facades\Cache;

class TestTradeOrderObserver
{
    /**
     * Handle the test trade order "created" event.
     *
     * @param  \App\Models\TestTradeOrder  $testTradeOrder
     * @return void
     */
    public function created(TestTradeOrder $testTradeOrder)
    {
        $unit_price = $testTradeOrder['unit_price'];
        $periods = ['1min','5min','15min','30min','60min','1day','1week','1mon'];
        foreach ($periods as $period){
            (new Kline())->cacheKline($period,$unit_price);
        }
        info('TestTradeOrderObserver');
    }

    /**
     * Handle the test trade order "updated" event.
     *
     * @param  \App\Models\TestTradeOrder  $testTradeOrder
     * @return void
     */
    public function updated(TestTradeOrder $testTradeOrder)
    {
        //
    }

    /**
     * Handle the test trade order "deleted" event.
     *
     * @param  \App\Models\TestTradeOrder  $testTradeOrder
     * @return void
     */
    public function deleted(TestTradeOrder $testTradeOrder)
    {
        //
    }

    /**
     * Handle the test trade order "restored" event.
     *
     * @param  \App\Models\TestTradeOrder  $testTradeOrder
     * @return void
     */
    public function restored(TestTradeOrder $testTradeOrder)
    {
        //
    }

    /**
     * Handle the test trade order "force deleted" event.
     *
     * @param  \App\Models\TestTradeOrder  $testTradeOrder
     * @return void
     */
    public function forceDeleted(TestTradeOrder $testTradeOrder)
    {
        //
    }
}
