<?php

namespace App\Listeners;

use App\Events\TestTradeOrderEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use GatewayClient\Gateway;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class TestTradeOrderListener
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
     * @param  TestTradeOrderEvent  $event
     * @return void
     */
    public function handle(TestTradeOrderEvent $event)
    {
        $order = $event->order;
        if(blank($order)) return ;

        $symbol = strtolower(str_before($order['symbol'],'/') . str_after($order['symbol'],'/'));
        $periods = ['1min','5min','15min','30min','60min','1day','1week','1mon'];
        foreach ($periods as $period){
            if ($period == '1min'){
                $seconds = 60;
                $open_carbon = Carbon::now()->floorMinute();
                $close_carbon = Carbon::now()->floorMinute()->addSeconds($seconds);
            }elseif ($period == '5min'){
                $seconds = 300;
                $open_carbon = Carbon::now()->floorMinute();
                $close_carbon = Carbon::now()->floorMinute()->addSeconds($seconds);
            }elseif ($period == '15min'){
                $seconds = 900;
                $open_carbon = Carbon::now()->floorMinute();
                $close_carbon = Carbon::now()->floorMinute()->addSeconds($seconds);
            }elseif ($period == '30min'){
                $seconds = 1800;
                $open_carbon = Carbon::now()->floorMinute();
                $close_carbon = Carbon::now()->floorMinute()->addSeconds($seconds);
            }elseif ($period == '60min'){
                $seconds = 3600;
                $open_carbon = Carbon::now()->floorHour();
                $close_carbon = Carbon::now()->floorHour()->addSeconds($seconds);
            }elseif ($period == '4hour'){
                $seconds = 14400;
                $open_carbon = Carbon::now()->floorHour();
                $close_carbon = Carbon::now()->floorHour()->addSeconds($seconds);
            }elseif ($period == '1day'){
                $seconds = 86400;
                $open_carbon = Carbon::now()->floorDay();
                $close_carbon = Carbon::now()->floorDay()->addSeconds($seconds);
            }elseif ($period == '1week'){
                $seconds = 604800;
                $open_carbon = Carbon::now()->floorDay();
                $close_carbon = Carbon::now()->floorDay()->addSeconds($seconds);
            }elseif ($period == '1mon'){
                $seconds = 2592000;
                $open_carbon = Carbon::now()->floorMonth();
                $close_carbon = Carbon::now()->floorMonth()->addSeconds($seconds);
            }else{
                return;
            }
            $id = $close_carbon->timestamp;
            $open_time = $open_carbon->timestamp;
            $close_time = $close_carbon->timestamp;
            $data = [
                "id"=> $id,
                "amount"=> 1,
                "count"=> rand(10,55),
                "open"=> 2,
                "close"=> rand(10,55) / 1000,
                "low"=> 4,
                "high"=> 5,
                "vol"=> 6
            ];
            Cache::store('redis')->put('market:' . $symbol . '_kline_' . $period,$data);

//            $group_id = 'Kline_' . $symbol . '_' . $period;
//            if(Gateway::getClientIdCountByGroup($group_id) > 0){
//                Gateway::$registerAddress = '127.0.0.1:1236';
//                $message = json_encode(['code'=>0,'msg'=>'success','data'=>$data,'sub'=>$group_id,'type'=>'dynamic']);
//                Gateway::sendToGroup($group_id,$message);
//            }
        }

        info('TestTradeOrderListener');
    }
}
