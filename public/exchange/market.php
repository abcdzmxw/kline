<?php
require "../index.php";

use App\Models\Coins;
use App\Models\InsideTradePair;
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
            //市场概要
            $msg2 = ["sub"=> "market." . $symbol . ".detail", "id"=> rand(100000,999999) . time()];
            $con->send(json_encode($msg2));
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
            $pattern_detail = '/^market\.(.*?)\.detail$/'; //市场概要
            if(preg_match($pattern_detail, $ch, $match_detail)){
                $match = $match_detail[1];
                $symbol = str_before($match,'.');
                $after = str_after($match,'.');
                if( $after != 'trade' ){
                    //市场概况
                    $cache_data = $data['tick'];

                    // 获取风控任务
                    $risk_key = option_risk_key($symbol);
                    $risk = json_decode( Redis::get($risk_key) ,true);
                    $minUnit = $risk['minUnit'] ?? 0;
                    $count = $risk['count'] ?? 0;
                    $enabled = $risk['enabled'] ?? 0;
                    if(!blank($risk) && $enabled == 1){
                        $change = $minUnit * $count;
                        $cache_data['close'] = PriceCalculate($cache_data['close'] ,'+', $change,8);
                        $cache_data['open'] = PriceCalculate($cache_data['open'] ,'+', $change,8);
                        $cache_data['high'] = PriceCalculate($cache_data['high'] ,'+', $change,8);
                        $cache_data['low'] = PriceCalculate($cache_data['low'] ,'+', $change,8);
                    }

                    if(isset($cache_data['open']) && $cache_data['open'] != 0){
                        // 获取1dayK线 计算$increase
                        $day_kline = Cache::store('redis')->get('market:' . $symbol . '_kline_' . '1day');
                        if(blank($day_kline)){
                            $increase = round(($cache_data['close'] - $cache_data['open']) / $cache_data['open'],4);
                        }else{
                            $increase = round(($cache_data['close'] - $day_kline['open']) / $day_kline['open'],4);
                        }
                    }else{
                        $increase = 0;
                    }
                    $cache_data['increase'] = $increase;
                    $flag = $increase >= 0 ? '+' : '';
                    $cache_data['increaseStr'] = $increase == 0 ? '+0.00%' : $flag . $increase * 100 . '%';

                    // 取价格波动折线数据
                    $tmp = Cache::store('redis')->get('market:' . $symbol . '_newPriceBook');
                    if(blank($tmp)){
                        $prices = [];
                    }else{
                        $size = count($tmp) >= 10 ? 10 : count($tmp);
                        $prices = array_random($tmp,$size);
                        $prices = array_values(Arr::sort($prices, function ($value) {
                            return $value['ts'];
                        }));
                        $prices = Arr::pluck($prices, 'price');
                    }
                    $cache_data['prices'] = $prices;

                    $key = 'market:' . $symbol . '_detail';
                    Cache::store('redis')->put($key,$cache_data);
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
