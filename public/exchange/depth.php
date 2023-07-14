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
            //买卖盘深度数据
            $msg1 = ["sub"=> "market." . $symbol . ".depth.step2", "id"=> rand(100000,999999) . time()];
            $con->send(json_encode($msg1));
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
//            $pattern_detail = '/^market\.(.*?)\.detail$/'; //市场概要
            if (preg_match($pattern_depth, $ch, $match_depth)){
                //深度数据
                $symbol = $match_depth[1];

                // 获取风控任务
                $risk_key = option_risk_key($symbol);
                $risk = json_decode( Redis::get($risk_key) ,true);
                $minUnit = $risk['minUnit'] ?? 0;
                $count = $risk['count'] ?? 0;
                $enabled = $risk['enabled'] ?? 0;

                $buyList = $data['tick']['bids'] ?? [];
                $cacheBuyList = [];
                foreach ($buyList as $key1 => $item1){
                    $cacheBuyList[$key1]['id'] = Str::uuid();
                    $cacheBuyList[$key1]['amount'] = $item1[1];

                    if(!blank($risk) && $enabled == 1){
                        // 修改买盘价格
                        $original_price = $item1[0];
                        $tmp = explode('.',$original_price);
                        if(sizeof($tmp) > 1){
                            $size = strlen(end($tmp));
                        }else{
                            $size = 0;
                        }
                        $change = $minUnit * $count;
                        $cacheBuyList[$key1]['price'] = PriceCalculate($original_price ,'+', $change,8);
                    }else{
                        $cacheBuyList[$key1]['price'] = $item1[0];
                    }
                }

                $sellList = $data['tick']['asks'] ?? [];
                $cacheSellList = [];
                foreach ($sellList as $key2 => $item2){
                    $cacheSellList[$key2]['id'] = Str::uuid();
                    $cacheSellList[$key2]['amount'] = $item2[1];

                    if(!blank($risk) && $enabled == 1){
                        // 修改卖盘价格
                        $original_price = $item2[0];
                        $tmp = explode('.',$original_price);
                        if(sizeof($tmp) > 1){
                            $size = strlen(end($tmp));
                        }else{
                            $size = 0;
                        }
                        $change = $minUnit * $count;
                        $cacheSellList[$key2]['price'] = PriceCalculate($original_price ,'+', $change,8);
                    }else{
                        $cacheSellList[$key2]['price'] = $item2[0];
                    }
                }
                Cache::store('redis')->put('market:' . $symbol . '_depth_buy',$cacheBuyList);
                Cache::store('redis')->put('market:' . $symbol . '_depth_sell',$cacheSellList);

                if($exchange_buy = Cache::store('redis')->get('exchange_buyList_' . $symbol)){
                    Cache::store('redis')->forget('exchange_buyList_' . $symbol);
                    array_unshift($cacheBuyList,$exchange_buy);
                }
                if($exchange_sell = Cache::store('redis')->get('exchange_sellList_' . $symbol)){
                    Cache::store('redis')->forget('exchange_sellList_' . $symbol);
                    array_unshift($cacheSellList,$exchange_sell);
                }

                $group_id1 = 'buyList_' . $symbol;
                $group_id2 = 'sellList_' . $symbol;
                if(Gateway::getClientIdCountByGroup($group_id1) > 0){
                    Gateway::sendToGroup($group_id1, json_encode(['code'=>0,'msg'=>'success','data'=>$cacheBuyList,'sub'=>$group_id1]));
                    Gateway::sendToGroup($group_id2, json_encode(['code'=>0,'msg'=>'success','data'=>$cacheSellList,'sub'=>$group_id2]));
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
