<?php

namespace App\Console\Commands;

use App\Jobs\OptionOrderDelivery;
use App\Models\OptionPair;
use App\Models\OptionScene;
use App\Models\OptionSceneOrder;
use App\Services\HuobiService\HuobiapiService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class OptionDeliveryListen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'option:delivery';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '监听期权场景，指定周期时间后执行交割';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cachedb = config('database.redis.cache.database',1);
        $pattern = '__keyevent@'.$cachedb.'__:expired';
//        Redis::connection('publisher')->subscribe([$pattern],function ($channel){    // 订阅键过期事件
        Redis::psubscribe([$pattern],function ($channel){    // 订阅键过期事件
            $key = str_after($channel,':');
            $key_type = str_before($key,':');
            $scene_id = str_after($key,':');    // 取出场景 ID
//            echo $key_type . "\n";
//            echo $scene_id . "\n";
            switch ($key_type) {
                case 'option_delivery':
//                    info('订阅键过期事件：option_delivery' . $scene_id);
                    $this->option_delivery($scene_id);
                    break;
                case 'get_begin_price':
//                    info('订阅键过期事件：get_begin_price' . $scene_id);
                    $this->get_begin_price($scene_id);
                    break;
                default:
                    break;
            }
        });
    }

    private function option_delivery($scene_id)
    {
        $scene = OptionScene::query()->lockForUpdate()->find($scene_id);
        if ($scene) {
            // 执行期权交割
            //  根据市场实时价格获取交割结果 更新期权场景
            $pair_name = str_before($scene['pair_time_name'],'-');
            $symbol = strtolower(str_before($pair_name,'/') . str_after($pair_name,'/'));
//            $market_trade = (new HuobiapiService())->getMarketTrade($symbol);
//            $market_trade = [
//                "ch"=> "market.btcusdt.trade.detail",
//                "status"=> "ok",
//                "ts"=> 1593242272637,
//                "tick"=> [
//                    "id"=> 109242365299,
//                    "ts"=> 1593242272481,
//                    "data"=> [
//                        [
//                            "id"=> 1.0924236529944560206305051e+25,
//                            "ts"=> 1593242272481,
//                            "trade-id"=> 102152942444,
//                            "amount"=> 0.27,
//                            "price"=> 9663.96,
//                            "direction"=> "sell"
//                        ],
//                        [
//                            "id"=> 1.0924267871744560290174988e+25,
//                            "ts"=> 1593242836553,
//                            "trade-id"=> 102152946642,
//                            "amount"=> 0.02455,
//                            "price"=> 9663.58,
//                            "direction"=> "buy"
//                        ],
//                    ]
//                ]
//            ];
//            if(blank($market_trade)){
//                $scene->cancel_scene();
//                return;
//            }
//            $trade_data = $market_trade['tick']['data'];
//            $new_price = $trade_data[0]['price'] ?? null; //收盘价
            $new_price = Cache::store('redis')->get('market:' . $symbol . '_newPrice')['price'] ?? null;
            $begin_price = $scene['begin_price'] ?? null; //开盘价
            // 开盘价或者收盘价为空 流局
            if(blank($begin_price) || blank($new_price)){
                $scene->cancel_scene();
                return;
            }else{
                $scene->update(['end_price'=>$new_price]);

//                $min_range = 0.03;
//                $result = PriceCalculate(($new_price - $begin_price) ,'/', $begin_price,8) * 100;
//                $delivery_range = abs($result);
//                if($result > 0){
//                    $delivery_up_down = $delivery_range >= $min_range ? 1 : 3;
//                }else{
//                    $delivery_up_down = $delivery_range >= $min_range ? 2 : 3;
//                }
            //  $scene_orders = OptionSceneOrder::query()->where('scene_id',$scene['scene_id'])->get();
            //  if(!blank($scene_orders['end_price'])) {
//                echo '进入作弊'.PHP_EOL;
           //         $new_price = $scene_orders['end_price'];
           //     }
                
                $result = PriceCalculate(($new_price - $begin_price) ,'/', $begin_price,8) * 100;
                $delivery_range = abs($result);
                if($new_price > $begin_price){
                    $delivery_up_down = 1;
                }elseif($new_price == $begin_price){
                    $delivery_up_down = 3;
                }else{
                    $delivery_up_down = 2;
                }

                try {
                    DB::beginTransaction();

                    $scene->update([
                        'delivery_up_down' => $delivery_up_down,
                        'delivery_range' => $delivery_range,
                        'status' => OptionScene::status_delivered,
                        'delivery_time' => time(),
                    ]);

                    //结算所有期权订单
                    $scene_orders = OptionSceneOrder::query()->where('scene_id',$scene['scene_id'])->get();
                    $delivery_result = ['delivery_up_down'=>$delivery_up_down,'delivery_range'=>$delivery_range];
                    if( !blank($scene_orders) ){
                        foreach ($scene_orders as $scene_order){
                            $scene_order->option_order_delivery($delivery_result, $begin_price);
//                        OptionOrderDelivery::dispatch($scene_order,$delivery_result)->onConnection('database')->onQueue('option_order_delivery');
                        }
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    info($e);
                    DB::rollback();
                }
            }

        }
    }

    public function get_begin_price($scene_id)
    {
        $scene = OptionScene::query()->find($scene_id);
        if ($scene) {
            // 获取市场实时成交价格 更新到期权场景
            $pair_name = str_before($scene['pair_time_name'],'-');
            $symbol = strtolower(str_before($pair_name,'/') . str_after($pair_name,'/'));
//            $market_trade = (new HuobiapiService())->getMarketTrade($symbol);
//            $market_trade = [
//                "ch"=> "market.btcusdt.trade.detail",
//                "status"=> "ok",
//                "ts"=> 1593242272637,
//                "tick"=> [
//                    "id"=> 109242365299,
//                    "ts"=> 1593242272481,
//                    "data"=> [
//                        [
//                            "id"=> 1.0924236529944560206305051e+25,
//                            "ts"=> 1593242272481,
//                            "trade-id"=> 102152942444,
//                            "amount"=> 0.27,
//                            "price"=> 9181.96,
//                            "direction"=> "sell"
//                        ],
//                        [
//                            "id"=> 1.0924267871744560290174988e+25,
//                            "ts"=> 1593242836553,
//                            "trade-id"=> 102152946642,
//                            "amount"=> 0.02455,
//                            "price"=> 9163.48,
//                            "direction"=> "buy"
//                        ],
//                    ]
//                ]
//            ];

//            $trade_data = $market_trade['tick']['data'];
//            $new_price = $trade_data[0]['price']; //开盘价
            $new_price = Cache::store('redis')->get('market:' . $symbol . '_newPrice')['price'] ?? null;

            $scene->update(['begin_price'=>$new_price]);
        }
    }

}
