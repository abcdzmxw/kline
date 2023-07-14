<?php
require "../index.php";

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer;
use Workerman\Worker;
use GatewayWorker\Lib\Gateway;

$worker = new Worker();
$worker->count = 1;
$worker->onWorkerStart = function($worker){

    Gateway::$registerAddress = '127.0.0.1:1238';

    $periods = ['1min'=>60,'5min'=>300,'15min'=>900,'30min'=>1800,'60min'=>3600,'1day'=>86400,'1week'=>604800,'1mon'=>2592000];
    foreach ($periods as $period => $seconds){
        $con = new AsyncTcpConnection('ws://api.btcgateway.pro/swap-ws');

        // 设置以ssl加密方式访问，使之成为wss
        $con->transport = 'ssl';

        $con->onConnect = function($con) use ($period,$seconds){
            //所有交易对
            $symbols = \App\Models\ContractPair::query()->where('status',1)->pluck('symbol');
            foreach ($symbols as $symbol){
                // Kline数据
                $symbol = $symbol . '-USD';
                $ch = "market." . $symbol . ".kline." . $period;
                $sub_msg = ["sub"=> $ch, 'id' => $ch . '_sub_' . time()];
                $req_msg = ["req"=> $ch, 'id' => $ch . '_req_' . time(), 'from' => time() - $seconds*1000 ,'to' => time()];
                $con->send(json_encode($req_msg));
                $con->send(json_encode($sub_msg));
            }
        };

        $con->onMessage = function($con, $data) use ($seconds){
            $data =  json_decode(gzdecode($data),true);
            if(isset($data['ping'])){
                $msg = ["pong" => $data['ping']];
                $con->send(json_encode($msg));
            }else{
                if(isset($data['rep'])){
                    $ch = $data['rep'];
                    $pattern_kline = '/^market\.(.*?)\.kline\.([\s\S]*)/'; //Kline
                    if (preg_match($pattern_kline, $ch, $match_kline)){
                        $symbol = $match_kline[1];
                        $symbol = str_before($symbol,'-');
                        $period = $match_kline[2];
                        $cache_data = $data['data'];
                        $kline_book_key = 'swap:' . $symbol . '_kline_book_' . $period;
                        Cache::store('redis')->put($kline_book_key, $cache_data);
                    }
                }elseif(isset($data['ch'])){
                    $ch = $data['ch'];
                    $pattern_kline = '/^market\.(.*?)\.kline\.([\s\S]*)/'; //Kline
                    if (preg_match($pattern_kline, $ch, $match_kline)){
                        $symbol = $match_kline[1];
                        $symbol = str_before($symbol,'-');
                        $period = $match_kline[2];
                        $cache_data = $data['tick'];
                        $cache_data['time'] = time();

                        $kline_book_key = 'swap:' . $symbol . '_kline_book_' . $period;
                        $kline_book = Cache::store('redis')->get($kline_book_key);

                        // 获取风控任务
                        $risk_key = contract_risk_key($symbol);
                        $risk = json_decode( Redis::get($risk_key) ,true);
                        $minUnit = $risk['minUnit'] ?? 0;
                        $count = $risk['count'] ?? 0;
                        $enabled = $risk['enabled'] ?? 0;
                        if(!blank($risk) && $enabled == 1){
                            // 修改价格
                            $change = $minUnit * $count;
                            $cache_data['close']    = PriceCalculate($cache_data['close'] ,'+', $change,8);
                            $cache_data['open']     = PriceCalculate($cache_data['open'] ,'+', $change,8);
                            $cache_data['high']     = PriceCalculate($cache_data['high'] ,'+', $change,8);
                            $cache_data['low']      = PriceCalculate($cache_data['low'] ,'+', $change,8);
                        }

                        if($period == '1min'){
                            // 1分钟基线
                            if(!blank($kline_book)){
                                $prev_id = $cache_data['id'] - $seconds;
                                $prev_item = array_last($kline_book,function ($value,$key)use($prev_id){
                                    return $value['id'] == $prev_id;
                                });
                                $cache_data['open'] = $prev_item['close'];
                            }

                            if(blank($kline_book)){
                                Cache::store('redis')->put($kline_book_key,[$cache_data]);
                            }else{
                                $last_item1 = array_pop($kline_book);
                                if($last_item1['id'] == $cache_data['id']){
                                    array_push($kline_book,$cache_data);
                                }else{
                                    array_push($kline_book,$last_item1,$cache_data);
                                }

                                if(count($kline_book) > 3000){
                                    array_shift($kline_book);
                                }
                                Cache::store('redis')->put($kline_book_key,$kline_book);
                            }
                        }else{
                            // 其他长周期K线都以前一周期作为参考 比如5minK线以1min为基础
                            $periodMap = [
                                '5min' => ['period'=>'1min','seconds'=>60],
                                '15min' => ['period'=>'5min','seconds'=>300],
                                '30min' => ['period'=>'5min','seconds'=>300],
                                '60min' => ['period'=>'5min','seconds'=>300],
                                '1day' => ['period'=>'60min','seconds'=>3600],
                                '1week' => ['period'=>'1day','seconds'=>86400],
                                '1mon' => ['period'=>'1day','seconds'=>86400],
                            ];
                            $map = $periodMap[$period] ?? null;
                            $kline_base_book = Cache::store('redis')->get('swap:' . $symbol . '_kline_book_' . $map['period']);
                            if(!blank($kline_base_book)){
                                // 以5min周期为例 这里一次性取出1min周期前后10个
                                $first_item_id = $cache_data['id'];
                                $last_item_id = $cache_data['id'] + $seconds - $map['seconds'];
                                $items1 = array_where($kline_base_book,function ($value,$key)use($first_item_id,$last_item_id){
                                    return $value['id'] >= $first_item_id && $value['id'] <= $last_item_id;
                                });

                                if(!blank($items1)){
                                    $cache_data['open']     = array_first($items1)['open'] ?? $cache_data['open'];
                                    $cache_data['close']    = array_last($items1)['close'] ?? $cache_data['close'];
                                    $cache_data['high']     = max(array_pluck($items1,'high')) ?? $cache_data['high'];
                                    $cache_data['low']      = min(array_pluck($items1,'low')) ?? $cache_data['low'];
                                }

                                if(blank($kline_book)){
                                    Cache::store('redis')->put($kline_book_key,[$cache_data]);
                                }else{
                                    $last_item1 = array_pop($kline_book);
                                    if($last_item1['id'] == $cache_data['id']){
                                        array_push($kline_book,$cache_data);
                                    }else{
                                        $update_last_item1 = $last_item1;
                                        // 有新的周期K线生成 此时尝试更新$last_item1
                                        $first_item_id2 = $cache_data['id'] - $seconds;
                                        $last_item_id2 = $cache_data['id'] - $map['seconds'];
                                        $items2 = array_where($kline_base_book,function ($value,$key)use($first_item_id2,$last_item_id2){
                                            return $value['id'] >= $first_item_id2 && $value['id'] <= $last_item_id2;
                                        });
                                        if(!blank($items2)){
                                            $update_last_item1['open']     = array_first($items2)['open'] ?? $update_last_item1['open'];
                                            $update_last_item1['close']    = array_last($items2)['close'] ?? $update_last_item1['close'];
                                            $update_last_item1['high']     = max(array_pluck($items2,'high')) ?? $update_last_item1['high'];
                                            $update_last_item1['low']      = min(array_pluck($items2,'low')) ?? $update_last_item1['low'];
                                        }
                                        array_push($kline_book,$update_last_item1,$cache_data);
                                    }

                                    if(count($kline_book) > 3000){
                                        array_shift($kline_book);
                                    }
                                    Cache::store('redis')->put($kline_book_key,$kline_book);
                                }
                            }
                        }

                        Cache::store('redis')->put('swap:' . $symbol . '_kline_' . $period,$cache_data);

                        $group_id2 = 'swapKline_' . $symbol . '_' . $period;
                        if(Gateway::getClientIdCountByGroup($group_id2) > 0){
                            Gateway::sendToGroup($group_id2, json_encode(['code'=>0,'msg'=>'success','data'=>$cache_data,'sub'=>$group_id2,'type'=>'dynamic']));
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
    }

};

Worker::runAll();
