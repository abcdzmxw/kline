<?php
require "../index.php";

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer;
use Workerman\Worker;
use GatewayWorker\Lib\Gateway;

$worker = new Worker();
$worker->count = 1;
$worker->onWorkerStart = function($worker){

    Gateway::$registerAddress = '127.0.0.1:1238';

    $con = new AsyncTcpConnection('ws://api.btcgateway.pro/swap-ws');

    // 设置以ssl加密方式访问，使之成为wss
    $con->transport = 'ssl';

    $con->onConnect = function($con) {
        //所有交易对
        $symbols = \App\Models\ContractPair::query()->where('status',1)->pluck('symbol');
        foreach ($symbols as $symbol){
            //最新成交
            $symbol = symbolMap($symbol) . '-USD';
            $ch = "market." . $symbol . ".trade.detail";
            $sub_msg = ["sub"=> $ch, 'id' => $ch . '_sub_' . time()];
            $req_msg = ["req"=> $ch, 'id' => $ch . '_req_' . time(), 'size' => 30];
            $con->send(json_encode($req_msg));
            $con->send(json_encode($sub_msg));
        }
    };

    $con->onMessage = function($con, $data) {
        $data =  json_decode(gzdecode($data),true);
//        echo json_encode($data) . "\r\n";
        if(isset($data['ping'])){
            $msg = ["pong" => $data['ping']];
            $con->send(json_encode($msg));
        }else{
            if(isset($data['rep'])){
                $ch = $data['rep'];
                $pattern_detail = '/^market\.(.*?)\.detail$/'; //市场概要
                if(preg_match($pattern_detail, $ch, $match_detail)){
                    $match = $match_detail[1];
                    $symbol = str_before($match,'.');
                    $symbol = str_before($symbol,'-');
                    $symbol = symbolMap($symbol,false);
                    $after = str_after($match,'.');
                    if( $after == 'trade' ){
                        $cache_data = $data['data'];
                        $trade_list_key = 'swap:tradeList_' . $symbol;
                        Cache::store('redis')->put($trade_list_key, $cache_data);
                    }
                }
            }elseif(isset($data['ch'])){
                $ch = $data['ch'];
                $pattern_detail = '/^market\.(.*?)\.detail$/'; //市场概要
                if(preg_match($pattern_detail, $ch, $match_detail)){
                    $match = $match_detail[1];
                    $symbol = str_before($match,'.');
                    $symbol = str_before($symbol,'-');
                    $symbol = symbolMap($symbol,false);
                    $after = str_after($match,'.');
                    if( $after == 'trade' ){
                        // 火币最新成交明细
                        // 最新成交价格数据
                        $cache_data = $data['tick']['data'][0] ?? [];
                        if(blank($cache_data)) return;
                        $cache_data['ts'] = Carbon::now()->getPreciseTimestamp(3);

                        // 获取风控任务
                        $risk_key = contract_risk_key($symbol);
                        $risk = json_decode( Redis::get($risk_key) ,true);
                        $minUnit = $risk['minUnit'] ?? 0;
                        $count = $risk['count'] ?? 0;
                        $enabled = $risk['enabled'] ?? 0;
                        if(!blank($risk) && $enabled == 1){
                            $change = $minUnit * $count;
                            $cache_data['price'] = PriceCalculate($cache_data['price'] ,'+', $change,8);
                        }

                        // TODO 获取Kline数据 计算涨幅
                        $kline_key = 'swap:' . $symbol . '_kline_1day';
                        $last_cache_data = Cache::store('redis')->get($kline_key);
                        if($last_cache_data){
                            $increase = $last_cache_data['open'] <= 0 ? 0 : PriceCalculate(($cache_data['price'] - $last_cache_data['open']) ,'/', $last_cache_data['open'],4);
                            $cache_data['increase'] = $increase;
                            $flag = $increase >= 0 ? '+' : '';
                            $cache_data['increaseStr'] = $increase == 0 ? '+0.00%' : $flag . $increase * 100 . '%';
                        }else{
                            $cache_data['increase'] = 0;
                            $cache_data['increaseStr'] = '+0.00%';
                        }

                        $trade_detail_key = 'swap:trade_detail_' . $symbol;
                        Cache::store('redis')->put($trade_detail_key,$cache_data);

                        // 合约止盈止损
                        \App\Jobs\TriggerStrategy::dispatch(['symbol'=>$symbol,'realtime_price'=>$cache_data['price']])->onQueue('triggerStrategy');

                        //缓存历史数据book
                        $trade_list_key = 'swap:tradeList_' . $symbol;
                        $trade_list = Cache::store('redis')->get($trade_list_key);
                        if(blank($trade_list)){
                            Cache::store('redis')->put($trade_list_key,[$cache_data]);
                        }else{
                            array_push($trade_list,$cache_data);
                            if(count($trade_list) > 30){
                                array_shift($trade_list);
                            }
                            Cache::store('redis')->put($trade_list_key,$trade_list);
                        }

                        $group_id2 = 'swapTradeList_' . $symbol; //最近成交明细
                        if(Gateway::getClientIdCountByGroup($group_id2) > 0){
                            Gateway::sendToGroup($group_id2, json_encode(['code'=>0,'msg'=>'success','data'=>$cache_data,'sub'=>$group_id2,'type'=>'dynamic']));
                        }
                    }
                }

            }
        }
    };

    $con->onClose = function ($con) {
        //这个是延迟断线重连，当服务端那边出现不确定因素，比如宕机，那么相对应的socket客户端这边也链接不上，那么可以吧1改成适当值，则会在多少秒内重新，我也是1，也就是断线1秒重新链接
        $con->reConnect(1);
    };

    $con->onError = function ($con, $code, $msg) {
        echo "error $code $msg\n";
    };

    $con->connect();
};

Worker::runAll();
