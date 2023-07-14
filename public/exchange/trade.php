<?php
require "../index.php";

use App\Models\Coins;
use App\Models\InsideTradePair;
use App\Models\Mongodb\NewPriceBook;
use App\Models\OptionPair;
use App\Models\OptionScene;
use App\Models\OptionTime;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer;
use Workerman\Worker;
use GatewayWorker\Lib\Gateway;
use Illuminate\Support\Facades\Redis;

$worker = new Worker();
$worker->count = 1;
$worker->onWorkerStart = function($worker){

    Gateway::$registerAddress = '127.0.0.1:1236';

    $con = new AsyncTcpConnection('ws://api.huobi.pro/ws');

    // 设置以ssl加密方式访问，使之成为wss
    $con->transport = 'ssl';

    $con->onConnect = function($con) {

        //所有交易对
        $symbols = InsideTradePair::query()->where('status',1)->where('is_market',1)->orderBy('sort','asc')->pluck('symbol')->toArray();
        foreach ($symbols as $symbol){
            //最新成交
            $msg3 = ["sub"=> "market." . $symbol . ".trade.detail", "id"=> rand(100000,999999) . time()];
            $con->send(json_encode($msg3));
        }

    };

    $con->onMessage = function($con, $data) {
        $data =  json_decode(gzdecode($data),true);
        if(isset($data['ping'])){
            $msg = ["pong" => $data['ping']];
            $con->send(json_encode($msg));
        }
        if(isset($data['ch'])){
            $ch = $data['ch'];
//            $pattern_depth = '/^market\.(.*?)\.mbp\.refresh\.20$/'; //深度
            $pattern_depth = '/^market\.(.*?)\.depth\.step2$/'; //深度
//            $pattern_kline = '/^market\.(.*?)\.kline\.([\s\S]*)/'; //Kline
            $pattern_detail = '/^market\.(.*?)\.detail$/'; //市场概要
            if(preg_match($pattern_detail, $ch, $match_detail)){
                $match = $match_detail[1];
                $symbol = str_before($match,'.');
                $after = str_after($match,'.');
                if( $after == 'trade' ){
                    // 火币最新成交明细 期权最新价格
                    $new_price_key = 'market:' . $symbol . '_newPrice';
                    if(blank($data['tick'])){
                        $cache_data = [];
                    }else{
                        //最新成交价格数据

                        if(blank($data['tick']['data'])){
                            $cache_data = [];
                        }else{
                            $cache_data = $data['tick']['data'][0];
                            $cache_data['ts'] = Carbon::now()->getPreciseTimestamp(3);

                            // 获取风控任务
                            $risk_key = option_risk_key($symbol);
                            $risk = json_decode( Redis::get($risk_key) ,true);
                            $minUnit = $risk['minUnit'] ?? 0;
                            $count = $risk['count'] ?? 0;
                            $enabled = $risk['enabled'] ?? 0;
                            if(!blank($risk) && $enabled == 1){
                                $change = $minUnit * $count;
                                $cache_data['price'] = PriceCalculate($cache_data['price'] ,'+', $change,8);
                            }

                            // TODO 获取Kline数据 计算涨幅
                            $kline_key = 'market:' . $symbol . '_kline_1day';
                            $last_cache_data = Cache::store('redis')->get($kline_key);
                            if(!blank($last_cache_data) && $last_cache_data['open']){
                                $increase = PriceCalculate(custom_number_format($cache_data['price'] - $last_cache_data['open'],8) ,'/', custom_number_format($last_cache_data['open'],8),4);
                                $cache_data['increase'] = $increase;
                                $flag = $increase >= 0 ? '+' : '';
                                $cache_data['increaseStr'] = $increase == 0 ? '+0.00%' : $flag . $increase * 100 . '%';
                            }else{
                                $cache_data['increase'] = 0;
                                $cache_data['increaseStr'] = '+0.00%';
                            }
                        }
                    }

                    if(!blank($cache_data)){
                        Cache::store('redis')->put($new_price_key,$cache_data);
                        //缓存历史价格数据book
                        $new_price_book_key = 'market:' . $symbol . '_newPriceBook';
                        $new_price_book = Cache::store('redis')->get($new_price_book_key);
                        if(blank($new_price_book)){
                            Cache::store('redis')->put($new_price_book_key,[$cache_data]);
                        }else{
                            array_push($new_price_book,$cache_data);
                            if(count($new_price_book) > 200){
                                array_shift($new_price_book);
                            }
                            Cache::store('redis')->put($new_price_book_key,$new_price_book);
                        }

                        // 缓存历史价格数据book到mongodb
//                        $cache_data['symbol'] = $symbol;
//                        $cache_data['time'] = time();
//                        NewPriceBook::query()->create($cache_data);

                        $group_id2 = 'tradeList_' . $symbol; //最近成交明细
                        if(Gateway::getClientIdCountByGroup($group_id2) > 0){
                            Gateway::sendToGroup($group_id2, json_encode(['code'=>0,'msg'=>'success','data'=>$cache_data,'sub'=>$group_id2,'type'=>'dynamic']));
                        }
                    }
                }
            }

        }
    };

    $con->onClose = function ($con) {
        if(isset($con->timer_id)){
            //删除定时器
            Timer::del($con->timer_id);
        }
        //这个是延迟断线重连，当服务端那边出现不确定因素，比如宕机，那么相对应的socket客户端这边也链接不上，那么可以吧1改成适当值，则会在多少秒内重新，我也是1，也就是断线1秒重新链接
        $con->reConnect(1);
    };

    $con->onError = function ($con, $code, $msg) {
        echo "error $code $msg\n";
    };

    $con->connect();
};

Worker::runAll();
