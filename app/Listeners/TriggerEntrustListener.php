<?php

namespace App\Listeners;

use Carbon\Carbon;
use GatewayClient\Gateway;
use App\Events\TriggerEntrustEvent;
use App\Jobs\HandleEntrust;
use App\Models\InsideTradeBuy;
use App\Models\InsideTradeSell;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class TriggerEntrustListener
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
     * @param  TriggerEntrustEvent  $event
     * @return void
     */
    public function handle(TriggerEntrustEvent $event)
    {
        info('TriggerEntrustListenerGateway');
        $order = $event->order;
        if(blank($order)) return ;

        // websocket推送
//        $symbol = strtolower(str_before($order['symbol'],'/') . str_after($order['symbol'],'/'));
//        $group_id2 = 'tradeList_' . $symbol; //最近成交明细
//        info($group_id2 . 'Gateway');
//        $cache_data = [
//            'amount'=> $order['trade_amount'],
//            'direction'=> "buy",
//            'id'=>$order['order_id'],
//            'increase'=> "0",
//            'increaseStr'=> "+0.00%",
//            'price'=> $order['unit_price'],
//            'tradeId'=> $order['order_id'],
//            'ts'=> Carbon::now()->getPreciseTimestamp(3),
//        ];
//        Gateway::sendToGroup($group_id2, json_encode(['code'=>0,'msg'=>'success','data'=>$cache_data,'sub'=>$group_id2,'type'=>'dynamic']));

        $buyBuilder = InsideTradeBuy::query()
            ->where('hang_status',0)
            ->where('type',3)
            ->where('status',InsideTradeBuy::status_wait);

        $sellBuilder = InsideTradeSell::query()
            ->where('hang_status',0)
            ->where('type',3)
            ->where('status',InsideTradeSell::status_wait);

        // 获取未挂单止盈止损委托列表
        $trigger_entrusts = $sellBuilder->union($buyBuilder)->get();
        foreach ($trigger_entrusts as $entrust){
            // 最新成交价达到触发价 挂单
            if($order['unit_price'] >= $entrust['trigger_price']){
                HandleEntrust::dispatch($entrust)->onQueue('handleEntrust');
            }
        }
    }
}
