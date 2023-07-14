<?php

namespace App\Observers;

use App\Models\InsideTradeOrder;
use Carbon\Carbon;
use GatewayClient\Gateway;

class InsideTradeOrderObserver
{
    /**
     * Handle the inside trade order "created" event.
     *
     * @param  \App\Models\InsideTradeOrder  $insideTradeOrder
     * @return void
     */
    public function created(InsideTradeOrder $insideTradeOrder)
    {
        $symbol = strtolower(str_before($insideTradeOrder['symbol'],'/') . str_after($insideTradeOrder['symbol'],'/'));
        $group_id2 = 'tradeList_' . $symbol; //最近成交明细
        $cache_data = [
            'amount'=> $insideTradeOrder['trade_amount'],
            'direction'=> "buy",
            'id'=>$insideTradeOrder['order_id'],
            'increase'=> "0",
            'increaseStr'=> "+0.00%",
            'price'=> $insideTradeOrder['unit_price'],
            'tradeId'=> $insideTradeOrder['order_id'],
            'buy_user_id'=> $insideTradeOrder['buy_user_id'],
            'sell_user_id'=> $insideTradeOrder['sell_user_id'],
            'ts'=> Carbon::now()->getPreciseTimestamp(3),
        ];

        Gateway::$registerAddress = '127.0.0.1:1236';
        if(Gateway::getClientIdCountByGroup($group_id2) > 0){
            Gateway::sendToGroup($group_id2, json_encode(['code'=>0,'msg'=>'success','data'=>$cache_data,'sub'=>$group_id2,'type'=>'dynamic']));
        }
    }

    /**
     * Handle the inside trade order "updated" event.
     *
     * @param  \App\Models\InsideTradeOrder  $insideTradeOrder
     * @return void
     */
    public function updated(InsideTradeOrder $insideTradeOrder)
    {
        //
    }

    /**
     * Handle the inside trade order "deleted" event.
     *
     * @param  \App\Models\InsideTradeOrder  $insideTradeOrder
     * @return void
     */
    public function deleted(InsideTradeOrder $insideTradeOrder)
    {
        //
    }

    /**
     * Handle the inside trade order "restored" event.
     *
     * @param  \App\Models\InsideTradeOrder  $insideTradeOrder
     * @return void
     */
    public function restored(InsideTradeOrder $insideTradeOrder)
    {
        //
    }

    /**
     * Handle the inside trade order "force deleted" event.
     *
     * @param  \App\Models\InsideTradeOrder  $insideTradeOrder
     * @return void
     */
    public function forceDeleted(InsideTradeOrder $insideTradeOrder)
    {
        //
    }
}
